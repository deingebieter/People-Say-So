<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People Say So 🎯 – Spiel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="game-page" data-theme="light">

<?php
// ----------------------------------------------------------------
// Local game setup via POST (no game_id needed, runs fully in-browser)
// ----------------------------------------------------------------
$localData    = 'null';
$gameId       = 0;
$playerNumber = 1;
$gameCode     = '';
$mode         = 'online';

if (isset($_POST['mode']) && $_POST['mode'] === 'local') {
    $mode  = 'local';
    $p1    = trim($_POST['p1'] ?? '');
    $p2    = trim($_POST['p2'] ?? '');
    $sizes = [5, 10, 25];
    $roundSize = in_array((int)($_POST['round_size'] ?? 5), $sizes) ? (int)$_POST['round_size'] : 5;

    if (!$p1 || !$p2) {
        header('Location: index.php');
        exit;
    }

    require_once __DIR__ . '/db.php';
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, question_text FROM questions ORDER BY RAND() LIMIT ' . (int)$roundSize);
        $stmt->execute();
        $questions = $stmt->fetchAll();

        foreach ($questions as &$q) {
            $aStmt = $db->prepare(
                'SELECT id, answer_text, points, display_order FROM answers WHERE question_id = ? ORDER BY display_order'
            );
            $aStmt->execute([$q['id']]);
            $q['answers'] = $aStmt->fetchAll();
        }
        unset($q);

        $localData = json_encode([
            'questions' => $questions,
            'roundSize' => $roundSize,
            'players'   => [
                ['player_number' => 1, 'player_name' => $p1],
                ['player_number' => 2, 'player_name' => $p2],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    } catch (Throwable $e) {
        error_log('[PeopleSaySo] Local setup failed: ' . $e->getMessage());
        header('Location: index.php?error=db');
        exit;
    }
} else {
    $gameId       = isset($_GET['game_id'])       ? (int)$_GET['game_id']       : 0;
    $playerNumber = isset($_GET['player_number']) ? (int)$_GET['player_number'] : 1;
    $gameCode     = isset($_GET['game_code'])     ? htmlspecialchars($_GET['game_code']) : '';
    $mode         = (isset($_GET['mode']) && $_GET['mode'] === 'local') ? 'local' : 'online';

    if (!$gameId) {
        echo '<script>window.location.href="index.php";</script>';
        exit;
    }
}
?>

<!-- Stars background -->
<div class="stars-container" aria-hidden="true">
    <?php for($i=0;$i<40;$i++): ?><span class="star"></span><?php endfor; ?>
</div>

<!-- TOP BAR -->
<header class="game-header">
    <div class="header-left">
        <a href="index.php" class="btn-icon" title="Hauptmenü">🏠</a>
        <span class="game-code-badge">🎯 <?= $gameCode ?></span>
    </div>
    <div class="header-center">
        <div class="energy-container">
            <div class="energy-label">⚡ Energie</div>
            <div class="energy-bar-wrap">
                <div class="energy-bar" id="energyBar" style="width:100%">
                    <span class="energy-shine"></span>
                </div>
            </div>
            <div class="energy-value" id="energyValue">100%</div>
        </div>
    </div>
    <div class="header-right">
        <div class="round-info" id="roundInfo">Runde 1</div>
        <button class="btn-icon" id="themeToggle" title="Design">🌙</button>
        <button class="btn-icon" id="musicToggle" title="Musik">🎵</button>
    </div>
</header>

<!-- SCOREBOARD -->
<section class="scoreboard" id="scoreboard">
    <div class="score-card player-1-card" id="scoreCard1">
        <div class="player-avatar">🟦</div>
        <div class="player-info">
            <div class="player-name-disp" id="player1Name">Spieler 1</div>
            <div class="player-score" id="player1Score">0</div>
        </div>
        <div class="turn-indicator" id="turnIndicator1">▶</div>
    </div>
    <div class="vs-badge">VS</div>
    <div class="score-card player-2-card" id="scoreCard2">
        <div class="turn-indicator flip" id="turnIndicator2">▶</div>
        <div class="player-info">
            <div class="player-name-disp" id="player2Name">Spieler 2</div>
            <div class="player-score" id="player2Score">0</div>
        </div>
        <div class="player-avatar">🟥</div>
    </div>
</section>

<!-- MAIN GAME AREA -->
<main class="game-main" id="gameMain">

    <!-- Current Question -->
    <div class="question-panel card" id="questionPanel">
        <div class="question-number" id="questionNumber">Frage 1 / 5</div>
        <div class="question-text" id="questionText">Lade Frage...</div>
        <div class="strikes-display" id="strikesDisplay">
            <span class="strike-slot" id="strike1">✕</span>
            <span class="strike-slot" id="strike2">✕</span>
            <span class="strike-slot" id="strike3">✕</span>
        </div>
    </div>

    <!-- Answer Board -->
    <div class="answer-board" id="answerBoard">
        <!-- Slots filled by JS -->
    </div>

    <!-- Turn Panel -->
    <div class="turn-panel card" id="turnPanel">
        <div class="whose-turn" id="whoseTurn">Dein Zug! 🎯</div>

        <!-- Answer Input -->
        <div class="answer-input-area" id="answerInputArea">
            <div class="input-row">
                <input type="text" id="answerInput" class="answer-input" placeholder="Deine Antwort..." autocomplete="off" autocorrect="off" spellcheck="false" maxlength="60">
                <button class="btn btn-gold" id="submitAnswer">
                    ✔ Senden
                </button>
            </div>
            <div class="answer-feedback hidden" id="answerFeedback"></div>
            <div class="action-row">
                <button class="btn btn-secondary" id="passBtn">
                    ⏭ Aussetzen
                </button>
            </div>
        </div>

        <!-- Waiting (online mode, other player's turn) -->
        <div class="waiting-turn hidden" id="waitingTurn">
            <div class="spinner"></div>
            <p id="waitingText">Warte auf anderen Spieler...</p>
        </div>
    </div>

</main>

<!-- ROUND END OVERLAY -->
<div class="overlay hidden" id="roundEndOverlay">
    <div class="overlay-card card">
        <div class="overlay-icon">🏆</div>
        <h2 class="overlay-title">Runde beendet!</h2>
        <div class="round-scores" id="roundScores"></div>
        <div class="overlay-actions">
            <button class="btn btn-gold" id="newRoundBtn">🎮 Neue Runde</button>
            <a href="index.php" class="btn btn-secondary">🏠 Hauptmenü</a>
        </div>
    </div>
</div>

<!-- GAME END OVERLAY -->
<div class="overlay hidden" id="gameEndOverlay">
    <div class="confetti-container" id="confettiContainer"></div>
    <div class="overlay-card card winner-card">
        <div class="overlay-icon winner-icon" id="winnerIcon">🏆</div>
        <h2 class="overlay-title winner-title" id="winnerTitle">Gewinner!</h2>
        <div class="winner-name" id="winnerName"></div>
        <div class="final-scores" id="finalScores"></div>
        <a href="index.php" class="btn btn-gold btn-full">🏠 Neues Spiel</a>
    </div>
</div>

<!-- Inject PHP values for JS -->
<script>
    window.GAME_CONFIG = {
        gameId:       <?= $gameId ?>,
        playerNumber: <?= $playerNumber ?>,
        gameCode:     <?= json_encode($gameCode) ?>,
        mode:         <?= json_encode($mode) ?>,
        apiUrl:       'api.php',
        localData:    <?= $localData ?>
    };
</script>
<script src="assets/app.js"></script>
<script>
// ================================================================
// Game page initialisation
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    initGame(window.GAME_CONFIG);
});
</script>
</body>
</html>
