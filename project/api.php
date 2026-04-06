<?php
/**
 * API Endpoint for AJAX requests
 * People Say So
 */

// Start session
session_start();

// Headers for JSON responses
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'game_logic.php';

// Get or create session ID
if (!isset($_SESSION['user_session'])) {
    $_SESSION['user_session'] = bin2hex(random_bytes(32));
}

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Get user
    $user = getOrCreateUser($_SESSION['user_session']);
    $userId = $user['id'];

    switch ($action) {
        // =====================
        // USER ACTIONS
        // =====================
        
        case 'get_user':
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'energy' => (int)$user['energy'],
                    'total_points' => (int)$user['total_points'],
                    'games_played' => (int)$user['games_played'],
                    'surveys_completed' => (int)$user['surveys_completed']
                ]
            ]);
            break;

        case 'get_stats':
            $stats = getUserStats($userId);
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        // =====================
        // SURVEY ACTIONS
        // =====================
        
        case 'get_survey':
            $survey = getNextSurvey($userId);
            if ($survey) {
                echo json_encode([
                    'success' => true,
                    'survey' => [
                        'id' => $survey['id'],
                        'question' => $survey['question'],
                        'total_responses' => (int)$survey['total_responses']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Keine weiteren Umfragen verfügbar'
                ]);
            }
            break;

        case 'get_surveys':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $surveys = getAvailableSurveys($userId, $limit);
            echo json_encode([
                'success' => true,
                'surveys' => $surveys,
                'count' => count($surveys)
            ]);
            break;

        case 'submit_survey':
            $surveyId = $_POST['survey_id'] ?? null;
            $answer = $_POST['answer'] ?? '';
            
            if (!$surveyId) {
                echo json_encode(['success' => false, 'message' => 'Umfrage-ID fehlt']);
                break;
            }
            
            $result = submitSurveyResponse($userId, (int)$surveyId, $answer);
            echo json_encode($result);
            break;

        // =====================
        // GAME ACTIONS
        // =====================
        
        case 'can_play':
            echo json_encode([
                'success' => true,
                'can_play' => canPlay($userId),
                'energy' => getUserEnergy($userId),
                'required_energy' => ENERGY_PER_GAME
            ]);
            break;

        case 'get_question':
            if (!canPlay($userId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nicht genug Energie. Beantworte Umfragen um Energie zu bekommen!',
                    'energy' => getUserEnergy($userId)
                ]);
                break;
            }
            
            $question = getRandomGameQuestion($userId);
            if ($question) {
                echo json_encode([
                    'success' => true,
                    'question' => [
                        'id' => $question['id'],
                        'question' => $question['question'],
                        'answer_count' => (int)$question['answer_count']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Keine Spielfragen verfügbar. Mehr Umfragen werden benötigt!'
                ]);
            }
            break;

        case 'start_game':
            $questionId = $_POST['question_id'] ?? null;
            
            if (!$questionId) {
                echo json_encode(['success' => false, 'message' => 'Frage-ID fehlt']);
                break;
            }
            
            $result = startGameSession($userId, (int)$questionId);
            echo json_encode($result);
            break;

        case 'check_answer':
            $questionId = $_POST['question_id'] ?? null;
            $answer = $_POST['answer'] ?? '';
            
            if (!$questionId) {
                echo json_encode(['success' => false, 'message' => 'Frage-ID fehlt']);
                break;
            }
            
            $result = checkAnswer((int)$questionId, $answer);
            echo json_encode([
                'success' => true,
                'result' => $result
            ]);
            break;

        case 'get_answers':
            $questionId = $_GET['question_id'] ?? null;
            
            if (!$questionId) {
                echo json_encode(['success' => false, 'message' => 'Frage-ID fehlt']);
                break;
            }
            
            $answers = getGameAnswers((int)$questionId);
            echo json_encode([
                'success' => true,
                'answers' => $answers
            ]);
            break;

        case 'complete_game':
            $sessionId = $_POST['session_id'] ?? null;
            $score = $_POST['score'] ?? 0;
            
            if (!$sessionId) {
                echo json_encode(['success' => false, 'message' => 'Session-ID fehlt']);
                break;
            }
            
            completeGameSession((int)$sessionId, (int)$score);
            $stats = getUserStats($userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Spiel abgeschlossen!',
                'stats' => $stats
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unbekannte Aktion: ' . htmlspecialchars($action)
            ]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()
    ]);
}
?>
