<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';

// CORS & content type headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(bool $success, $data = null, string $error = ''): void {
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $input = json_decode($raw, true) ?? [];
    }
    // Also merge $_POST for form submissions
    $input = array_merge($_POST, $input);
} else {
    $input = $_GET;
}

$action = trim($input['action'] ?? '');
if (!$action) {
    jsonResponse(false, null, 'Keine Aktion angegeben.');
}

$logic = new GameLogic();

try {
    switch ($action) {

        // -----------------------------------------------------------
        case 'create_game': {
            $playerName = trim($input['player_name'] ?? '');
            $roundSize  = (int)($input['round_size'] ?? 5);
            $mode       = in_array($input['mode'] ?? '', ['local', 'online']) ? $input['mode'] : 'online';
            if (!$playerName) {
                jsonResponse(false, null, 'Spielername erforderlich.');
            }
            $data = $logic->createGame($mode, $playerName, $roundSize);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'join_game': {
            $gameCode   = strtoupper(trim($input['game_code'] ?? ''));
            $playerName = trim($input['player_name'] ?? '');
            if (!$gameCode || !$playerName) {
                jsonResponse(false, null, 'Spielcode und Name erforderlich.');
            }
            $data = $logic->joinGame($gameCode, $playerName);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'start_round': {
            $gameId    = (int)($input['game_id'] ?? 0);
            $roundSize = (int)($input['round_size'] ?? 5);
            if (!$gameId) {
                jsonResponse(false, null, 'Spiel-ID erforderlich.');
            }
            $data = $logic->startRound($gameId, $roundSize);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'submit_answer': {
            $gameId       = (int)($input['game_id'] ?? 0);
            $playerNumber = (int)($input['player_number'] ?? 0);
            $answerText   = trim($input['answer_text'] ?? '');
            if (!$gameId || !$playerNumber || !$answerText) {
                jsonResponse(false, null, 'Pflichtfelder fehlen.');
            }
            $data = $logic->submitAnswer($gameId, $playerNumber, $answerText);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'get_state': {
            $gameId       = (int)($input['game_id'] ?? 0);
            $playerNumber = (int)($input['player_number'] ?? 0);
            if (!$gameId) {
                jsonResponse(false, null, 'Spiel-ID erforderlich.');
            }
            $data = $logic->getGameState($gameId, $playerNumber ?: null);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'pass_turn': {
            $gameId       = (int)($input['game_id'] ?? 0);
            $playerNumber = (int)($input['player_number'] ?? 0);
            if (!$gameId || !$playerNumber) {
                jsonResponse(false, null, 'Pflichtfelder fehlen.');
            }
            $data = $logic->passTurn($gameId, $playerNumber);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'next_question': {
            $gameId = (int)($input['game_id'] ?? 0);
            if (!$gameId) {
                jsonResponse(false, null, 'Spiel-ID erforderlich.');
            }
            $data = $logic->nextQuestion($gameId);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        case 'end_round': {
            $gameId = (int)($input['game_id'] ?? 0);
            if (!$gameId) {
                jsonResponse(false, null, 'Spiel-ID erforderlich.');
            }
            $data = $logic->endRound($gameId);
            jsonResponse(true, $data);
        }

        // -----------------------------------------------------------
        default:
            jsonResponse(false, null, 'Unbekannte Aktion: ' . htmlspecialchars($action));
    }
} catch (RuntimeException $e) {
    jsonResponse(false, null, $e->getMessage());
} catch (Throwable $e) {
    error_log('[PeopleSaySo] ' . $e->getMessage());
    jsonResponse(false, null, 'Interner Serverfehler.');
}
