<?php
/**
 * Game Logic - Core functions for the game and survey system
 * People Say So
 */

require_once 'db.php';

// Constants
define('MIN_RESPONSES_FOR_GAME', 100); // Minimum responses to convert survey to game

/**
 * Get or create user by session ID
 */
function getOrCreateUser($sessionId) {
    $db = getDB();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $db->prepare("INSERT INTO users (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        $userId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
    
    return $user;
}

// =====================
// SURVEY FUNCTIONS
// =====================

/**
 * Get available surveys for a user (ones they haven't answered yet)
 */
function getAvailableSurveys($userId, $limit = 5) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.* FROM surveys s
        WHERE s.is_active = 1 
        AND s.is_converted_to_game = 0
        AND s.id NOT IN (
            SELECT survey_id FROM survey_responses WHERE user_id = ?
        )
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get a single available survey for a user
 */
function getNextSurvey($userId) {
    $surveys = getAvailableSurveys($userId, 1);
    return $surveys ? $surveys[0] : null;
}

/**
 * Submit a survey response
 */
function submitSurveyResponse($userId, $surveyId, $answer) {
    $db = getDB();
    
    // Check if user already answered this survey
    $stmt = $db->prepare("SELECT id FROM survey_responses WHERE user_id = ? AND survey_id = ?");
    $stmt->execute([$userId, $surveyId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Du hast diese Umfrage bereits beantwortet'];
    }
    
    // Sanitize answer
    $answer = trim($answer);
    if (empty($answer)) {
        return ['success' => false, 'message' => 'Bitte gib eine Antwort ein'];
    }
    
    // Insert response
    $stmt = $db->prepare("INSERT INTO survey_responses (survey_id, user_id, answer) VALUES (?, ?, ?)");
    $stmt->execute([$surveyId, $userId, $answer]);
    
    // Update survey response count
    $stmt = $db->prepare("UPDATE surveys SET total_responses = total_responses + 1 WHERE id = ?");
    $stmt->execute([$surveyId]);
    
    // Update user stats
    $stmt = $db->prepare("UPDATE users SET surveys_completed = surveys_completed + 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Check if survey should be converted to game question
    checkAndConvertSurvey($surveyId);
    
    return [
        'success' => true, 
        'message' => 'Antwort gespeichert!'
    ];
}

/**
 * Check if survey has enough responses and convert to game question
 */
function checkAndConvertSurvey($surveyId) {
    $db = getDB();
    
    // Get survey
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ? AND is_converted_to_game = 0");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();
    
    if (!$survey || $survey['total_responses'] < MIN_RESPONSES_FOR_GAME) {
        return false;
    }
    
    // Convert to game question
    $stmt = $db->prepare("INSERT INTO game_questions (survey_id, question) VALUES (?, ?)");
    $stmt->execute([$surveyId, $survey['question']]);
    $questionId = $db->lastInsertId();
    
    // Aggregate answers and calculate points
    // Use subquery to get the most common casing for each answer
    $stmt = $db->prepare("
        SELECT sr.answer, subq.count 
        FROM survey_responses sr
        INNER JOIN (
            SELECT LOWER(answer) as lower_answer, COUNT(*) as count, MAX(id) as max_id
            FROM survey_responses 
            WHERE survey_id = ?
            GROUP BY LOWER(answer)
            ORDER BY count DESC
            LIMIT 10
        ) subq ON LOWER(sr.answer) = subq.lower_answer AND sr.id = subq.max_id
        ORDER BY subq.count DESC
    ");
    $stmt->execute([$surveyId]);
    $answers = $stmt->fetchAll();
    
    // Calculate points based on popularity rank
    $rank = 1;
    foreach ($answers as $answerData) {
        // Points decrease by rank (most popular = highest points)
        $points = max(1, 11 - $rank) * 10;
        
        $stmt = $db->prepare("
            INSERT INTO game_answers (question_id, answer, response_count, points, rank_position) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $questionId, 
            $answerData['answer'], 
            $answerData['count'],
            $points,
            $rank
        ]);
        $rank++;
    }
    
    // Mark survey as converted
    $stmt = $db->prepare("UPDATE surveys SET is_converted_to_game = 1, converted_at = NOW() WHERE id = ?");
    $stmt->execute([$surveyId]);
    
    return true;
}

// =====================
// GAME FUNCTIONS
// =====================

/**
 * Get a random game question
 */
function getRandomGameQuestion($userId = null) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT gq.*, 
               (SELECT COUNT(*) FROM game_answers WHERE question_id = gq.id) as answer_count
        FROM game_questions gq
        WHERE gq.is_active = 1
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Get answers for a game question
 */
function getGameAnswers($questionId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, answer, points, rank_position, response_count
        FROM game_answers 
        WHERE question_id = ?
        ORDER BY rank_position ASC
    ");
    $stmt->execute([$questionId]);
    return $stmt->fetchAll();
}

/**
 * Check if an answer matches any game answer
 */
function checkAnswer($questionId, $userAnswer) {
    $db = getDB();
    
    // Normalize the answer
    $normalizedAnswer = strtolower(trim($userAnswer));
    
    // Use exact match for answer checking
    $stmt = $db->prepare("
        SELECT * FROM game_answers 
        WHERE question_id = ? 
        AND LOWER(answer) = ?
    ");
    $stmt->execute([$questionId, $normalizedAnswer]);
    $match = $stmt->fetch();
    
    if ($match) {
        return [
            'correct' => true,
            'answer' => $match['answer'],
            'points' => $match['points'],
            'rank' => $match['rank_position']
        ];
    }
    
    return ['correct' => false, 'points' => 0];
}

/**
 * Start a new game session
 */
function startGameSession($userId, $questionId) {
    $db = getDB();
    
    // Create game session
    $stmt = $db->prepare("INSERT INTO game_sessions (user_id, question_id) VALUES (?, ?)");
    $stmt->execute([$userId, $questionId]);
    
    // Update games played counter
    $stmt = $db->prepare("UPDATE users SET games_played = games_played + 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Update question play count
    $stmt = $db->prepare("UPDATE game_questions SET times_played = times_played + 1 WHERE id = ?");
    $stmt->execute([$questionId]);
    
    return [
        'success' => true, 
        'session_id' => $db->lastInsertId()
    ];
}

/**
 * Complete a game session with final score
 */
function completeGameSession($sessionId, $score) {
    $db = getDB();
    
    $stmt = $db->prepare("
        UPDATE game_sessions 
        SET score = ?, is_completed = 1, completed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$score, $sessionId]);
    
    // Get user ID and update total points
    $stmt = $db->prepare("SELECT user_id FROM game_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if ($session) {
        $stmt = $db->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
        $stmt->execute([$score, $session['user_id']]);
    }
    
    return true;
}

/**
 * Get user statistics
 */
function getUserStats($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            total_points,
            games_played,
            surveys_completed
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
?>
