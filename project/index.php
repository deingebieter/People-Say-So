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
        <div class="divider">oder</div>
        <button class="btn btn-survey mode-btn" id="btnSurvey">
            <span class="btn-icon-inner">📝</span>
            <span>An Umfragen teilnehmen</span>
        </button>
        <p class="energy-hint">⚡ +10% Energie pro Umfrage!</p>
    </div>

    <!-- LOCAL GAME FORM (plain POST – no JS fetch needed) -->
    <form method="POST" action="game.php" class="form-panel card animate-in delay-2 hidden" id="localForm" novalidate>
        <button type="button" class="back-btn" data-back="mode">← Zurück</button>
        <h2 class="form-title">👫 Lokales Spiel</h2>
        <div class="form-group">
            <label for="localP1">Spieler 1 Name</label>
            <input type="text" id="localP1" name="p1" class="input-field" placeholder="Spieler 1..." maxlength="20">
        </div>
        <div class="form-group">
            <label for="localP2">Spieler 2 Name</label>
            <input type="text" id="localP2" name="p2" class="input-field" placeholder="Spieler 2..." maxlength="20">
        </div>
        <div class="form-group">
            <label>Rundengröße</label>
            <div class="round-size-picker" id="localRoundSizePicker">
                <button type="button" class="round-size-btn active" data-size="5">5 Fragen</button>
                <button type="button" class="round-size-btn" data-size="10">10 Fragen</button>
                <button type="button" class="round-size-btn" data-size="25">25 Fragen</button>
            </div>
            <input type="hidden" name="round_size" id="localRoundSizeInput" value="5">
            <input type="hidden" name="mode" value="local">
        </div>
        <button type="submit" class="btn btn-primary btn-full" id="startLocalGame">
            🎮 Spiel starten!
        </button>
        <div class="form-error hidden" id="localError"></div>
    </form>

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

    <!-- SURVEY PANEL -->
    <div class="form-panel card animate-in delay-2 hidden" id="surveyPanel">
        <button class="back-btn" data-back="mode">← Zurück</button>
        <h2 class="form-title">📝 Umfragen</h2>
        
        <!-- Energy Display -->
        <div class="survey-energy-display">
            <div class="energy-label">⚡ Deine Energie</div>
            <div class="energy-bar-wrap">
                <div class="energy-bar" id="surveyEnergyBar" style="width:50%">
                    <span class="energy-shine"></span>
                </div>
            </div>
            <div class="energy-value" id="surveyEnergyValue">50%</div>
        </div>
        
        <!-- Survey Question -->
        <div class="survey-question-container" id="surveyQuestionContainer">
            <div class="survey-question-text" id="surveyQuestionText">Lade Umfrage...</div>
            <div class="survey-progress" id="surveyProgress">
                <span class="progress-count">0/100</span> Antworten gesammelt
            </div>
        </div>
        
        <!-- Survey Answer Input -->
        <div class="form-group">
            <label for="surveyAnswerInput">Deine Antwort</label>
            <input type="text" id="surveyAnswerInput" class="input-field" placeholder="Deine Antwort eingeben..." maxlength="100">
        </div>
        
        <button class="btn btn-gold btn-full" id="submitSurveyBtn">
            ✔ Antwort absenden
        </button>
        
        <button class="btn btn-secondary btn-full" id="skipSurveyBtn" style="margin-top: 10px;">
            ⏭ Überspringen
        </button>
        
        <div class="form-success hidden" id="surveySuccess"></div>
        <div class="form-error hidden" id="surveyError"></div>
        
        <p class="survey-info">
            💡 Beantworte Umfragen, um Energie zu sammeln und neue Spiel-Fragen zu erstellen!
            <br>Nach 100 Antworten wird aus jeder Umfrage eine neue Spiel-Frage.
        </p>
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

    // ---- Round size picker (shared for local & online forms) ----
    document.querySelectorAll('.round-size-picker').forEach(picker => {
        picker.querySelectorAll('.round-size-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                picker.querySelectorAll('.round-size-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedRoundSize = parseInt(btn.dataset.size);
                // Sync hidden input for local form
                const hidden = document.getElementById('localRoundSizeInput');
                if (hidden && picker.id === 'localRoundSizePicker') hidden.value = selectedRoundSize;
            });
        });
    });

    // ---- Local game: validate before form POST ----
    document.getElementById('localForm').addEventListener('submit', (e) => {
        const p1 = document.getElementById('localP1').value.trim();
        const p2 = document.getElementById('localP2').value.trim();
        if (!p1 || !p2) {
            e.preventDefault();
            showError('localError', 'Bitte beide Namen eingeben.');
        }
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

    // ================================================================
    // SURVEY SYSTEM
    // ================================================================
    let currentSurvey = null;
    let userEnergy = 50; // Default starting energy
    const deviceToken = localStorage.getItem('pss_token') || generateDeviceToken();
    
    function generateDeviceToken() {
        const token = 'dev_' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
        localStorage.setItem('pss_token', token);
        return token;
    }
    
    function updateSurveyEnergy(energy) {
        userEnergy = Math.min(100, Math.max(0, energy));
        const bar = document.getElementById('surveyEnergyBar');
        const value = document.getElementById('surveyEnergyValue');
        if (bar) bar.style.width = userEnergy + '%';
        if (value) value.textContent = userEnergy + '%';
    }
    
    async function loadSurvey() {
        try {
            const res = await apiCall({ action: 'get_survey', device_token: deviceToken });
            if (res.success && res.data) {
                currentSurvey = res.data.survey;
                updateSurveyEnergy(res.data.energy || 50);
                
                if (currentSurvey) {
                    document.getElementById('surveyQuestionText').textContent = currentSurvey.question_text;
                    document.getElementById('surveyProgress').innerHTML = 
                        `<span class="progress-count">${currentSurvey.current_responses}/${currentSurvey.target_responses}</span> Antworten gesammelt`;
                    document.getElementById('surveyAnswerInput').value = '';
                    document.getElementById('surveyAnswerInput').focus();
                } else {
                    document.getElementById('surveyQuestionText').textContent = '🎉 Keine offenen Umfragen mehr! Spiele jetzt!';
                    document.getElementById('surveyProgress').textContent = 'Alle Umfragen wurden beantwortet.';
                    document.getElementById('submitSurveyBtn').disabled = true;
                }
            } else {
                showError('surveyError', res.error || 'Fehler beim Laden der Umfrage.');
            }
        } catch(e) {
            showError('surveyError', 'Verbindungsfehler.');
        }
    }
    
    // Show survey panel
    document.getElementById('btnSurvey').addEventListener('click', () => {
        showPanel('surveyPanel');
        loadSurvey();
    });
    
    // Submit survey answer
    document.getElementById('submitSurveyBtn').addEventListener('click', async () => {
        if (!currentSurvey) return;
        
        const answerText = document.getElementById('surveyAnswerInput').value.trim();
        if (!answerText) {
            showError('surveyError', 'Bitte gib eine Antwort ein.');
            return;
        }
        
        const btn = document.getElementById('submitSurveyBtn');
        btn.disabled = true;
        btn.textContent = 'Wird gesendet...';
        
        try {
            const res = await apiCall({
                action: 'submit_survey',
                survey_id: currentSurvey.id,
                answer_text: answerText,
                device_token: deviceToken
            });
            
            if (res.success) {
                updateSurveyEnergy(res.data.energy);
                showSuccess('surveySuccess', `✅ Danke! +10% Energie. Du hast jetzt ${res.data.energy}% Energie.`);
                
                // Load next survey after short delay
                setTimeout(() => {
                    document.getElementById('surveySuccess').classList.add('hidden');
                    loadSurvey();
                }, 1500);
            } else {
                showError('surveyError', res.error || 'Fehler beim Speichern.');
            }
        } catch(e) {
            showError('surveyError', 'Verbindungsfehler.');
        }
        
        btn.disabled = false;
        btn.textContent = '✔ Antwort absenden';
    });
    
    // Skip survey
    document.getElementById('skipSurveyBtn').addEventListener('click', () => {
        loadSurvey();
    });
    
    // Enter key to submit survey
    document.getElementById('surveyAnswerInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            document.getElementById('submitSurveyBtn').click();
        }
    });
    
    function showSuccess(elId, msg) {
        const el = document.getElementById(elId);
        if (el) {
            el.textContent = msg;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4000);
        }
    }

})();
</script>
</body>
</html>
