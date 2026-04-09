<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People Say So 🎯</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="index-page" data-theme="light">

<!-- Stars background -->
<div class="stars-container" aria-hidden="true">
    <?php for($i=0;$i<60;$i++): ?><span class="star"></span><?php endfor; ?>
</div>

<!-- Top controls -->
<div class="top-controls">
    <button class="btn-icon" id="themeToggle" title="Design wechseln" aria-label="Design wechseln">🌙</button>
    <button class="btn-icon" id="musicToggle" title="Musik ein/aus" aria-label="Musik ein/aus">🎵</button>
</div>

<main class="landing-container">

    <!-- Logo / Title -->
    <div class="hero animate-in">
        <div class="logo-badge">🎯</div>
        <h1 class="game-title">People<br><span class="title-say">Say So</span></h1>
        <p class="game-subtitle">Das Umfrage-Rätsel-Spiel!</p>
    </div>

    <!-- Mode Selection -->
    <div class="mode-selection animate-in delay-1" id="modeSelection">
        <button class="btn btn-primary mode-btn" id="btnLocal">
            <span class="btn-icon-inner">👫</span>
            <span>Auf einem Gerät spielen</span>
        </button>
        <button class="btn btn-gold mode-btn" id="btnOnline">
            <span class="btn-icon-inner">🌐</span>
            <span>Online spielen</span>
        </button>
    </div>

    <!-- LOCAL GAME FORM -->
    <div class="form-panel card animate-in delay-2 hidden" id="localForm">
        <button class="back-btn" data-back="mode">← Zurück</button>
        <h2 class="form-title">👫 Lokales Spiel</h2>
        <div class="form-group">
            <label for="localP1">Spieler 1 Name</label>
            <input type="text" id="localP1" class="input-field" placeholder="Spieler 1..." maxlength="20">
        </div>
        <div class="form-group">
            <label for="localP2">Spieler 2 Name</label>
            <input type="text" id="localP2" class="input-field" placeholder="Spieler 2..." maxlength="20">
        </div>
        <div class="form-group">
            <label>Rundengröße</label>
            <div class="round-size-picker">
                <button class="round-size-btn active" data-size="5">5 Fragen</button>
                <button class="round-size-btn" data-size="10">10 Fragen</button>
                <button class="round-size-btn" data-size="25">25 Fragen</button>
            </div>
        </div>
        <button class="btn btn-primary btn-full" id="startLocalGame">
            🎮 Spiel starten!
        </button>
        <div class="form-error hidden" id="localError"></div>
    </div>

    <!-- ONLINE SELECTION -->
    <div class="form-panel card animate-in delay-2 hidden" id="onlineSelection">
        <button class="back-btn" data-back="mode">← Zurück</button>
        <h2 class="form-title">🌐 Online spielen</h2>
        <div class="online-options">
            <button class="btn btn-gold btn-full" id="btnCreateGame">
                <span>✨ Neues Spiel erstellen</span>
            </button>
            <div class="divider">oder</div>
            <button class="btn btn-primary btn-full" id="btnJoinGame">
                <span>🔑 Spiel beitreten</span>
            </button>
        </div>
    </div>

    <!-- CREATE ONLINE GAME FORM -->
    <div class="form-panel card animate-in delay-2 hidden" id="createForm">
        <button class="back-btn" data-back="online">← Zurück</button>
        <h2 class="form-title">✨ Spiel erstellen</h2>
        <div class="form-group">
            <label for="createName">Dein Name</label>
            <input type="text" id="createName" class="input-field" placeholder="Dein Name..." maxlength="20">
        </div>
        <div class="form-group">
            <label>Rundengröße</label>
            <div class="round-size-picker">
                <button class="round-size-btn active" data-size="5">5 Fragen</button>
                <button class="round-size-btn" data-size="10">10 Fragen</button>
                <button class="round-size-btn" data-size="25">25 Fragen</button>
            </div>
        </div>
        <button class="btn btn-gold btn-full" id="createGameBtn">
            🚀 Spiel erstellen!
        </button>
        <div class="form-error hidden" id="createError"></div>

        <!-- Waiting for player 2 -->
        <div class="waiting-room hidden" id="waitingRoom">
            <div class="code-display">
                <div class="code-label">Spielcode:</div>
                <div class="code-value" id="displayCode">------</div>
                <button class="btn-copy" id="copyCode" title="Code kopieren">📋 Kopieren</button>
            </div>
            <div class="waiting-spinner">
                <div class="spinner"></div>
                <p>Warte auf Spieler 2...</p>
            </div>
        </div>
    </div>

    <!-- JOIN GAME FORM -->
    <div class="form-panel card animate-in delay-2 hidden" id="joinForm">
        <button class="back-btn" data-back="online">← Zurück</button>
        <h2 class="form-title">🔑 Spiel beitreten</h2>
        <div class="form-group">
            <label for="joinCode">Spielcode</label>
            <input type="text" id="joinCode" class="input-field code-input" placeholder="Z.B. AB12CD" maxlength="6" autocomplete="off" autocapitalize="characters">
        </div>
        <div class="form-group">
            <label for="joinName">Dein Name</label>
            <input type="text" id="joinName" class="input-field" placeholder="Dein Name..." maxlength="20">
        </div>
        <button class="btn btn-primary btn-full" id="joinGameBtn">
            🎯 Beitreten!
        </button>
        <div class="form-error hidden" id="joinError"></div>
    </div>

</main>

<footer class="landing-footer">
    <p>🎯 People Say So &mdash; Errate die beliebtesten Antworten!</p>
</footer>

<script src="assets/app.js"></script>
<script>
// ================================================================
// Landing page logic
// ================================================================
(function() {
    const API = 'api.php';
    let selectedRoundSize = 5;
    let waitingPollInterval = null;
    let pendingGameId = null;
    let pendingPlayerNumber = null;

    // Theme & Music are handled by app.js initTheme/initMusic

    // ---- Navigation ----
    function showPanel(id) {
        document.querySelectorAll('.form-panel, .mode-selection').forEach(el => el.classList.add('hidden'));
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('hidden');
            el.classList.add('animate-in');
        }
    }

    document.getElementById('btnLocal').addEventListener('click', () => showPanel('localForm'));
    document.getElementById('btnOnline').addEventListener('click', () => showPanel('onlineSelection'));
    document.getElementById('btnCreateGame').addEventListener('click', () => showPanel('createForm'));
    document.getElementById('btnJoinGame').addEventListener('click', () => showPanel('joinForm'));

    document.querySelectorAll('.back-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.back;
            if (target === 'mode') showPanel('modeSelection');
            else if (target === 'online') showPanel('onlineSelection');
            if (waitingPollInterval) {
                clearInterval(waitingPollInterval);
                waitingPollInterval = null;
            }
        });
    });

    // ---- Round size picker ----
    document.querySelectorAll('.round-size-picker').forEach(picker => {
        picker.querySelectorAll('.round-size-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                picker.querySelectorAll('.round-size-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedRoundSize = parseInt(btn.dataset.size);
            });
        });
    });

    // ---- Copy code ----
    document.getElementById('copyCode').addEventListener('click', () => {
        const code = document.getElementById('displayCode').textContent;
        navigator.clipboard.writeText(code).then(() => {
            document.getElementById('copyCode').textContent = '✅ Kopiert!';
            setTimeout(() => document.getElementById('copyCode').textContent = '📋 Kopieren', 2000);
        });
    });

    function showError(elId, msg) {
        const el = document.getElementById(elId);
        el.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }

    async function apiCall(payload) {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return res.json();
    }

    function goToGame(gameId, playerNumber, gameCode, mode) {
        const url = `game.php?game_id=${gameId}&player_number=${playerNumber}&game_code=${gameCode}&mode=${mode}`;
        window.location.href = url;
    }

    // ---- Local game ----
    document.getElementById('startLocalGame').addEventListener('click', async () => {
        const p1 = document.getElementById('localP1').value.trim();
        const p2 = document.getElementById('localP2').value.trim();
        if (!p1 || !p2) {
            showError('localError', 'Bitte beide Namen eingeben.');
            return;
        }
        const btn = document.getElementById('startLocalGame');
        btn.disabled = true;
        btn.textContent = 'Lade...';

        try {
            const res = await apiCall({ action: 'create_game', player_name: p1, round_size: selectedRoundSize, mode: 'local' });
            if (!res.success) { showError('localError', res.error); btn.disabled = false; btn.textContent = '🎮 Spiel starten!'; return; }

            const gameId = res.data.game_id;
            const gameCode = res.data.game_code;

            // Auto join player 2 in local mode
            const res2 = await apiCall({ action: 'join_game', game_code: gameCode, player_name: p2 });
            if (!res2.success) { showError('localError', res2.error); btn.disabled = false; btn.textContent = '🎮 Spiel starten!'; return; }

            // Start round immediately
            const res3 = await apiCall({ action: 'start_round', game_id: gameId, round_size: selectedRoundSize });
            if (!res3.success) { showError('localError', res3.error); btn.disabled = false; btn.textContent = '🎮 Spiel starten!'; return; }

            goToGame(gameId, 1, gameCode, 'local');
        } catch(e) {
            showError('localError', 'Verbindungsfehler. Bitte erneut versuchen.');
            btn.disabled = false;
            btn.textContent = '🎮 Spiel starten!';
        }
    });

    // ---- Create online game ----
    document.getElementById('createGameBtn').addEventListener('click', async () => {
        const name = document.getElementById('createName').value.trim();
        if (!name) { showError('createError', 'Bitte Namen eingeben.'); return; }

        const btn = document.getElementById('createGameBtn');
        btn.disabled = true;
        btn.textContent = 'Erstelle...';

        try {
            const res = await apiCall({ action: 'create_game', player_name: name, round_size: selectedRoundSize, mode: 'online' });
            if (!res.success) { showError('createError', res.error); btn.disabled = false; btn.textContent = '🚀 Spiel erstellen!'; return; }

            pendingGameId = res.data.game_id;
            pendingPlayerNumber = 1;
            const code = res.data.game_code;

            // Store token
            localStorage.setItem('pss_token', res.data.device_token);
            localStorage.setItem('pss_player_number', '1');

            document.getElementById('displayCode').textContent = code;
            document.getElementById('createGameBtn').classList.add('hidden');
            document.getElementById('waitingRoom').classList.remove('hidden');

            // Poll for player 2 joining
            waitingPollInterval = setInterval(async () => {
                try {
                    const state = await apiCall({ action: 'get_state', game_id: pendingGameId, player_number: 1 });
                    if (state.success && state.data.players && state.data.players.length >= 2) {
                        clearInterval(waitingPollInterval);
                        // Start the round
                        await apiCall({ action: 'start_round', game_id: pendingGameId, round_size: selectedRoundSize });
                        goToGame(pendingGameId, 1, code, 'online');
                    }
                } catch(e) { console.warn('Warte auf Spieler 2:', e); }
            }, 2000);

        } catch(e) {
            showError('createError', 'Verbindungsfehler.');
            btn.disabled = false;
            btn.textContent = '🚀 Spiel erstellen!';
        }
    });

    // ---- Join online game ----
    document.getElementById('joinGameBtn').addEventListener('click', async () => {
        const code = document.getElementById('joinCode').value.trim().toUpperCase();
        const name = document.getElementById('joinName').value.trim();
        if (!code || !name) { showError('joinError', 'Bitte Code und Namen eingeben.'); return; }

        const btn = document.getElementById('joinGameBtn');
        btn.disabled = true;
        btn.textContent = 'Beitreten...';

        try {
            const res = await apiCall({ action: 'join_game', game_code: code, player_name: name });
            if (!res.success) { showError('joinError', res.error); btn.disabled = false; btn.textContent = '🎯 Beitreten!'; return; }

            localStorage.setItem('pss_token', res.data.device_token);
            localStorage.setItem('pss_player_number', '2');

            // Wait for game to start (player 1 starts the round)
            const gameId = res.data.game_id;
            const playerNumber = res.data.player_number;

            const checkStart = setInterval(async () => {
                try {
                    const state = await apiCall({ action: 'get_state', game_id: gameId, player_number: playerNumber });
                    if (state.success && state.data.game && state.data.game.status === 'active') {
                        clearInterval(checkStart);
                        goToGame(gameId, playerNumber, code, 'online');
                    }
                } catch(e) { console.warn('Warte auf Spielstart:', e); }
            }, 1500);

            btn.textContent = 'Warte auf Start...';
        } catch(e) {
            showError('joinError', 'Verbindungsfehler.');
            btn.disabled = false;
            btn.textContent = '🎯 Beitreten!';
        }
    });

    // Auto-uppercase game code input
    document.getElementById('joinCode').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

})();
</script>
</body>
</html>
