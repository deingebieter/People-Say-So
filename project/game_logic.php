<?php
require_once __DIR__ . '/db.php';

class GameLogic {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ----------------------------------------------------------------
    // Create a new game
    // ----------------------------------------------------------------
    public function createGame(string $mode, string $player1Name, int $roundSize): array {
        $roundSize = $this->validRoundSize($roundSize);
        $gameCode  = $this->generateCode();

        $stmt = $this->db->prepare(
            'INSERT INTO games (game_code, mode, status, energy, current_question_index,
             current_round_size, questions_played_this_round, round_number)
             VALUES (?, ?, ?, 100, 0, ?, 0, 0)'
        );
        $stmt->execute([$gameCode, $mode, 'waiting', $roundSize]);
        $gameId = (int)$this->db->lastInsertId();

        $token    = $this->generateToken();
        $stmtP    = $this->db->prepare(
            'INSERT INTO game_players (game_id, player_number, player_name, total_score, is_current_turn, device_token)
             VALUES (?, 1, ?, 0, 1, ?)'
        );
        $stmtP->execute([$gameId, $player1Name, $token]);
        $playerId = (int)$this->db->lastInsertId();

        return [
            'game_id'       => $gameId,
            'game_code'     => $gameCode,
            'player_id'     => $playerId,
            'player_number' => 1,
            'device_token'  => $token,
        ];
    }

    // ----------------------------------------------------------------
    // Player 2 joins
    // ----------------------------------------------------------------
    public function joinGame(string $gameCode, string $player2Name): array {
        $game = $this->getGameByCode($gameCode);
        if (!$game) {
            throw new RuntimeException('Spielcode nicht gefunden.');
        }
        if ($game['status'] !== 'waiting') {
            throw new RuntimeException('Das Spiel läuft bereits oder ist beendet.');
        }

        // Check if player 2 already exists (reconnect)
        $stmtCheck = $this->db->prepare(
            'SELECT * FROM game_players WHERE game_id = ? AND player_number = 2'
        );
        $stmtCheck->execute([$game['id']]);
        $existing = $stmtCheck->fetch();
        if ($existing) {
            return [
                'game_id'       => $game['id'],
                'game_code'     => $gameCode,
                'player_id'     => $existing['id'],
                'player_number' => 2,
                'device_token'  => $existing['device_token'],
            ];
        }

        $token = $this->generateToken();
        $stmt  = $this->db->prepare(
            'INSERT INTO game_players (game_id, player_number, player_name, total_score, is_current_turn, device_token)
             VALUES (?, 2, ?, 0, 0, ?)'
        );
        $stmt->execute([$game['id'], $player2Name, $token]);
        $playerId = (int)$this->db->lastInsertId();

        return [
            'game_id'       => $game['id'],
            'game_code'     => $gameCode,
            'player_id'     => $playerId,
            'player_number' => 2,
            'device_token'  => $token,
        ];
    }

    // ----------------------------------------------------------------
    // Start a round
    // ----------------------------------------------------------------
    public function startRound(int $gameId, int $roundSize): array {
        $roundSize = $this->validRoundSize($roundSize);
        $game      = $this->getGameById($gameId);
        if (!$game) {
            throw new RuntimeException('Spiel nicht gefunden.');
        }
        if ($game['energy'] <= 0) {
            throw new RuntimeException('Keine Energie mehr. Das Spiel ist vorbei.');
        }

        // Remove any existing game_questions for this game
        $this->db->prepare('DELETE FROM game_questions WHERE game_id = ?')->execute([$gameId]);
        $this->db->prepare('DELETE FROM game_strikes WHERE game_id = ?')->execute([$gameId]);

        // Pick random questions not already used in this game session
        $usedIds = $this->getUsedQuestionIds($gameId);
        $placeholders = $usedIds ? implode(',', array_fill(0, count($usedIds), '?')) : '0';

        $sql = "SELECT id FROM questions WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT " . (int)$roundSize;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($usedIds);
        $questionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($questionIds) < $roundSize) {
            // Recycle: reset and pick any questions
            $stmt2 = $this->db->prepare("SELECT id FROM questions ORDER BY RAND() LIMIT " . (int)$roundSize);
            $stmt2->execute();
            $questionIds = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }

        if (empty($questionIds)) {
            throw new RuntimeException('Keine Fragen in der Datenbank gefunden. Bitte database.sql importieren.');
        }

        $insStmt = $this->db->prepare(
            'INSERT INTO game_questions (game_id, question_id, question_order) VALUES (?, ?, ?)'
        );
        foreach ($questionIds as $idx => $qid) {
            $insStmt->execute([$gameId, $qid, $idx]);
        }

        // Reset turn to player 1
        $this->db->prepare('UPDATE game_players SET is_current_turn = (player_number = 1) WHERE game_id = ?')
                 ->execute([$gameId]);

        $newRoundNumber = (int)$game['round_number'] + 1;

        $this->db->prepare(
            'UPDATE games SET status = ?, current_question_index = 0, current_round_size = ?,
             questions_played_this_round = 0, round_number = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['active', $roundSize, $newRoundNumber, $gameId]);

        return $this->getGameState($gameId);
    }

    // ----------------------------------------------------------------
    // Get full game state
    // ----------------------------------------------------------------
    public function getGameState(int $gameId, ?int $playerNumber = null): array {
        $game = $this->getGameById($gameId);
        if (!$game) {
            throw new RuntimeException('Spiel nicht gefunden.');
        }

        $players = $this->getPlayers($gameId);
        $currentQ = $this->getCurrentQuestion($gameId, (int)$game['current_question_index']);
        $strikes  = 0;
        if ($currentQ) {
            $strikeRow = $this->getStrikes($gameId, (int)$currentQ['id']);
            $strikes   = $strikeRow ? (int)$strikeRow['strike_count'] : 0;
        }

        $revealedAnswers = [];
        if ($currentQ) {
            $revealedAnswers = $this->getRevealedAnswers($gameId, (int)$currentQ['id']);
        }

        $totalQuestions = $this->db->prepare('SELECT COUNT(*) FROM game_questions WHERE game_id = ?');
        $totalQuestions->execute([$gameId]);
        $total = (int)$totalQuestions->fetchColumn();

        return [
            'game'             => $game,
            'players'          => $players,
            'current_question' => $currentQ,
            'revealed_answers' => $revealedAnswers,
            'strikes'          => $strikes,
            'total_questions'  => $total,
            'current_player'   => $playerNumber,
        ];
    }

    // ----------------------------------------------------------------
    // Submit an answer
    // ----------------------------------------------------------------
    public function submitAnswer(int $gameId, int $playerNumber, string $answerText): array {
        $game = $this->getGameById($gameId);
        if (!$game || $game['status'] !== 'active') {
            throw new RuntimeException('Spiel ist nicht aktiv.');
        }

        $player = $this->getPlayerByNumber($gameId, $playerNumber);
        if (!$player) {
            throw new RuntimeException('Spieler nicht gefunden.');
        }
        if (!$player['is_current_turn']) {
            throw new RuntimeException('Du bist gerade nicht am Zug.');
        }

        $currentQ = $this->getCurrentQuestion($gameId, (int)$game['current_question_index']);
        if (!$currentQ) {
            throw new RuntimeException('Keine aktuelle Frage.');
        }

        // Get all answers for this question
        $stmt = $this->db->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY display_order');
        $stmt->execute([$currentQ['id']]);
        $allAnswers = $stmt->fetchAll();

        // Get already revealed answers for this question
        $revealedAnswerIds = $this->getRevealedAnswerIds($gameId, (int)$currentQ['id']);

        // Try to match the input
        $matched = null;
        foreach ($allAnswers as $answer) {
            if (in_array((int)$answer['id'], $revealedAnswerIds)) {
                continue; // already found
            }
            if ($this->fuzzyMatch($answerText, $answer['answer_text'])) {
                $matched = $answer;
                break;
            }
        }

        $result = [
            'correct'         => false,
            'points'          => 0,
            'answer_revealed' => null,
            'game_state'      => null,
            'all_revealed'    => false,
            'round_ended'     => false,
        ];

        if ($matched) {
            // Award points
            $this->db->prepare(
                'UPDATE game_players SET total_score = total_score + ? WHERE game_id = ? AND player_number = ?'
            )->execute([$matched['points'], $gameId, $playerNumber]);

            // Record the answer
            $this->db->prepare(
                'INSERT INTO game_answers (game_id, question_id, answer_id, player_id) VALUES (?, ?, ?, ?)'
            )->execute([$gameId, $currentQ['id'], $matched['id'], $player['id']]);

            $result['correct']         = true;
            $result['points']          = (int)$matched['points'];
            $result['answer_revealed'] = $matched;

            // Check if all answers revealed
            $revealedNow = array_merge($revealedAnswerIds, [$matched['id']]);
            if (count($revealedNow) >= count($allAnswers)) {
                $result['all_revealed'] = true;
            }

            // Switch turn (after correct answer, other player gets a turn)
            $this->switchTurn($gameId, $playerNumber);

        } else {
            // Wrong answer - add strike
            $strikeCount = $this->addStrike($gameId, (int)$currentQ['id']);
            $result['strike_count'] = $strikeCount;

            if ($strikeCount >= 3) {
                // Reveal all remaining answers, move to next question
                $result['all_revealed'] = true;
            }

            // Switch turn on wrong answer too
            $this->switchTurn($gameId, $playerNumber);
        }

        // Advance question if all revealed or 3 strikes
        if ($result['all_revealed']) {
            $advanced = $this->advanceQuestion($gameId);
            $result['round_ended'] = $advanced['round_ended'];
        }

        $result['game_state'] = $this->getGameState($gameId, $playerNumber);
        return $result;
    }

    // ----------------------------------------------------------------
    // Pass turn
    // ----------------------------------------------------------------
    public function passTurn(int $gameId, int $playerNumber): array {
        $game = $this->getGameById($gameId);
        if (!$game || $game['status'] !== 'active') {
            throw new RuntimeException('Spiel ist nicht aktiv.');
        }
        $player = $this->getPlayerByNumber($gameId, $playerNumber);
        if (!$player || !$player['is_current_turn']) {
            throw new RuntimeException('Du bist gerade nicht am Zug.');
        }
        $this->switchTurn($gameId, $playerNumber);
        return $this->getGameState($gameId, $playerNumber);
    }

    // ----------------------------------------------------------------
    // Next question (manual advance)
    // ----------------------------------------------------------------
    public function nextQuestion(int $gameId): array {
        $advanced = $this->advanceQuestion($gameId);
        return array_merge($this->getGameState($gameId), ['round_ended' => $advanced['round_ended']]);
    }

    // ----------------------------------------------------------------
    // End round
    // ----------------------------------------------------------------
    public function endRound(int $gameId): array {
        $game = $this->getGameById($gameId);
        if (!$game) {
            throw new RuntimeException('Spiel nicht gefunden.');
        }

        // Energy is already deducted per question in advanceQuestion; just set final status.
        $newEnergy = max(0, (int)$game['energy']);
        $newStatus = $newEnergy <= 0 ? 'finished' : 'round_end';

        $this->db->prepare(
            'UPDATE games SET status = ?, energy = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$newStatus, $newEnergy, $gameId]);

        return $this->getGameState($gameId);
    }

    // ----------------------------------------------------------------
    // Leaderboard
    // ----------------------------------------------------------------
    public function getLeaderboard(int $gameId): array {
        $stmt = $this->db->prepare(
            'SELECT player_name, player_number, total_score FROM game_players
             WHERE game_id = ? ORDER BY total_score DESC'
        );
        $stmt->execute([$gameId]);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------------
    // Fuzzy match
    // ----------------------------------------------------------------
    public function fuzzyMatch(string $input, string $stored): bool {
        $normalize = function(string $s): string {
            $s = mb_strtolower(trim($s), 'UTF-8');
            // Accept umlauts directly OR their ascii equivalents
            $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return $s;
        };

        $normalizeKeepUmlauts = function(string $s): string {
            return mb_strtolower(trim($s), 'UTF-8');
        };

        $normInput  = $normalize($input);
        $normStored = $normalize($stored);

        // Exact match after normalization
        if ($normInput === $normStored) {
            return true;
        }

        // Also try direct lowercase comparison (keeps umlauts)
        if ($normalizeKeepUmlauts($input) === $normalizeKeepUmlauts($stored)) {
            return true;
        }

        // Partial: input contains stored or vice versa (for short compound words)
        if (mb_strlen($normInput, 'UTF-8') >= 3 && mb_strlen($normStored, 'UTF-8') >= 3) {
            if (strpos($normStored, $normInput) !== false || strpos($normInput, $normStored) !== false) {
                return true;
            }
        }

        // Levenshtein for longer words
        $lenInput  = mb_strlen($normInput, 'UTF-8');
        $lenStored = mb_strlen($normStored, 'UTF-8');
        if ($lenInput > 4 && $lenStored > 4) {
            $dist = levenshtein($normInput, $normStored);
            if ($dist <= 2) {
                return true;
            }
        }

        return false;
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function advanceQuestion(int $gameId): array {
        $game = $this->getGameById($gameId);
        if (!$game) {
            return ['round_ended' => false];
        }

        $nextIndex    = (int)$game['current_question_index'] + 1;
        $roundSize    = (int)$game['current_round_size'];
        $played       = (int)$game['questions_played_this_round'] + 1;
        $energyCost   = (int)round(100 / $roundSize);
        $newEnergy    = max(0, (int)$game['energy'] - $energyCost);

        if ($played >= $roundSize || $newEnergy <= 0) {
            // Round ended
            $newStatus = $newEnergy <= 0 ? 'finished' : 'round_end';
            $this->db->prepare(
                'UPDATE games SET status = ?, current_question_index = ?, energy = ?,
                 questions_played_this_round = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$newStatus, $nextIndex, $newEnergy, $played, $gameId]);

            return ['round_ended' => true];
        }

        // Reset strikes for next question
        $this->db->prepare(
            'UPDATE games SET current_question_index = ?, questions_played_this_round = ?,
             energy = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$nextIndex, $played, $newEnergy, $gameId]);

        // Switch turn back to player 1 for new question
        $this->db->prepare('UPDATE game_players SET is_current_turn = (player_number = 1) WHERE game_id = ?')
                 ->execute([$gameId]);

        return ['round_ended' => false];
    }

    private function switchTurn(int $gameId, int $currentPlayerNumber): void {
        $next = $currentPlayerNumber === 1 ? 2 : 1;
        $this->db->prepare(
            'UPDATE game_players SET is_current_turn = (player_number = ?) WHERE game_id = ?'
        )->execute([$next, $gameId]);
    }

    private function addStrike(int $gameId, int $questionId): int {
        $stmt = $this->db->prepare(
            'INSERT INTO game_strikes (game_id, question_id, strike_count) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE strike_count = strike_count + 1'
        );
        $stmt->execute([$gameId, $questionId]);

        $sel = $this->db->prepare('SELECT strike_count FROM game_strikes WHERE game_id = ? AND question_id = ?');
        $sel->execute([$gameId, $questionId]);
        return (int)($sel->fetchColumn() ?? 0);
    }

    private function getStrikes(int $gameId, int $questionId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM game_strikes WHERE game_id = ? AND question_id = ?');
        $stmt->execute([$gameId, $questionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function getRevealedAnswers(int $gameId, int $questionId): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT a.* FROM answers a
             INNER JOIN game_answers ga ON ga.answer_id = a.id
             WHERE ga.game_id = ? AND ga.question_id = ?
             ORDER BY a.display_order'
        );
        $stmt->execute([$gameId, $questionId]);
        return $stmt->fetchAll();
    }

    private function getRevealedAnswerIds(int $gameId, int $questionId): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT answer_id FROM game_answers WHERE game_id = ? AND question_id = ?'
        );
        $stmt->execute([$gameId, $questionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getCurrentQuestion(int $gameId, int $index): ?array {
        $stmt = $this->db->prepare(
            'SELECT q.*, gq.question_order FROM questions q
             INNER JOIN game_questions gq ON gq.question_id = q.id
             WHERE gq.game_id = ? AND gq.question_order = ?'
        );
        $stmt->execute([$gameId, $index]);
        $q = $stmt->fetch();
        if (!$q) {
            return null;
        }

        // Attach answers
        $aStmt = $this->db->prepare(
            'SELECT * FROM answers WHERE question_id = ? ORDER BY display_order'
        );
        $aStmt->execute([$q['id']]);
        $q['answers'] = $aStmt->fetchAll();
        return $q;
    }

    private function getPlayers(int $gameId): array {
        $stmt = $this->db->prepare(
            'SELECT id, game_id, player_number, player_name, total_score, is_current_turn
             FROM game_players WHERE game_id = ? ORDER BY player_number'
        );
        $stmt->execute([$gameId]);
        return $stmt->fetchAll();
    }

    private function getPlayerByNumber(int $gameId, int $playerNumber): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM game_players WHERE game_id = ? AND player_number = ?'
        );
        $stmt->execute([$gameId, $playerNumber]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function getGameById(int $gameId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM games WHERE id = ?');
        $stmt->execute([$gameId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function getGameByCode(string $code): ?array {
        $stmt = $this->db->prepare('SELECT * FROM games WHERE game_code = ?');
        $stmt->execute([strtoupper($code)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function getUsedQuestionIds(int $gameId): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT question_id FROM game_answers WHERE game_id = ?'
        );
        $stmt->execute([$gameId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function generateCode(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $stmt = $this->db->prepare('SELECT id FROM games WHERE game_code = ?');
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        return $code;
    }

    private function generateToken(): string {
        return bin2hex(random_bytes(16));
    }

    private function validRoundSize(int $size): int {
        return in_array($size, [5, 10, 25]) ? $size : 5;
    }
}
