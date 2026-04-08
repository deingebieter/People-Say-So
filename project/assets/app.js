/* ================================================================
   People Say So — app.js
   ================================================================ */

/* ----------------------------------------------------------------
   Theme & Music toggles — auto-init on load
   ---------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMusicToggle();
    initStars();
});

function initThemeToggle() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    const saved = localStorage.getItem('pss_theme') || 'light';
    applyTheme(saved);
    btn.addEventListener('click', () => {
        const current = document.body.dataset.theme || 'light';
        const next = current === 'light' ? 'dark' : 'light';
        applyTheme(next);
        localStorage.setItem('pss_theme', next);
    });
}

function applyTheme(theme) {
    document.body.dataset.theme = theme;
    const btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

function initMusicToggle() {
    const btn = document.getElementById('musicToggle');
    if (!btn) return;
    let musicOn = localStorage.getItem('pss_music') !== 'off';
    btn.textContent = musicOn ? '🎵' : '🔇';
    btn.addEventListener('click', () => {
        musicOn = !musicOn;
        btn.textContent = musicOn ? '🎵' : '🔇';
        localStorage.setItem('pss_music', musicOn ? 'on' : 'off');
    });
}

/* ----------------------------------------------------------------
   Stars animation
   ---------------------------------------------------------------- */
function initStars() {
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
        const size = Math.random() * 3 + 1.5;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const delay = Math.random() * 5;
        const duration = Math.random() * 4 + 2;
        const driftDuration = Math.random() * 18 + 12;
        star.style.cssText = [
            `width:${size}px`,
            `height:${size}px`,
            `left:${x}%`,
            `top:${y}%`,
            `animation-delay:${delay}s`,
            `animation-duration:${duration}s,${driftDuration}s`
        ].join(';');
    });
}

/* ----------------------------------------------------------------
   API helper
   ---------------------------------------------------------------- */
async function apiPost(url, payload) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const json = await res.json();
    return json;
}

/* ================================================================
   initGame(config) — main game logic
   ================================================================ */
function initGame(config) {
    const { gameId, playerNumber, gameCode, mode, apiUrl } = config;

    let pollInterval = null;
    let currentState = null;
    let gameFinished = false;

    /* -- DOM refs -- */
    const playerNameEls   = [document.getElementById('player1Name'), document.getElementById('player2Name')];
    const playerScoreEls  = [document.getElementById('player1Score'), document.getElementById('player2Score')];
    const turnIndicators  = [document.getElementById('turnIndicator1'), document.getElementById('turnIndicator2')];
    const scoreCards      = [document.getElementById('scoreCard1'), document.getElementById('scoreCard2')];
    const questionNumber  = document.getElementById('questionNumber');
    const questionText    = document.getElementById('questionText');
    const strikesDisplay  = document.getElementById('strikesDisplay');
    const strikeSlots     = [
        document.getElementById('strike1'),
        document.getElementById('strike2'),
        document.getElementById('strike3')
    ];
    const answerBoard     = document.getElementById('answerBoard');
    const whoseTurn       = document.getElementById('whoseTurn');
    const answerInputArea = document.getElementById('answerInputArea');
    const waitingTurn     = document.getElementById('waitingTurn');
    const waitingText     = document.getElementById('waitingText');
    const answerInput     = document.getElementById('answerInput');
    const submitAnswerBtn = document.getElementById('submitAnswer');
    const passBtn         = document.getElementById('passBtn');
    const answerFeedback  = document.getElementById('answerFeedback');
    const roundEndOverlay = document.getElementById('roundEndOverlay');
    const roundScores     = document.getElementById('roundScores');
    const newRoundBtn     = document.getElementById('newRoundBtn');
    const gameEndOverlay  = document.getElementById('gameEndOverlay');
    const winnerName      = document.getElementById('winnerName');
    const finalScores     = document.getElementById('finalScores');
    const energyBar       = document.getElementById('energyBar');
    const energyValue     = document.getElementById('energyValue');
    const roundInfo       = document.getElementById('roundInfo');
    const confettiContainer = document.getElementById('confettiContainer');

    /* ---- Initial load ---- */
    loadState();

    /* ---- Event listeners ---- */
    submitAnswerBtn.addEventListener('click', submitAnswer);
    answerInput.addEventListener('keydown', e => { if (e.key === 'Enter') submitAnswer(); });
    passBtn.addEventListener('click', passTurn);
    newRoundBtn.addEventListener('click', startNewRound);

    /* ================================================================
       State management
       ================================================================ */
    async function loadState() {
        try {
            const res = await apiPost(apiUrl, { action: 'get_state', game_id: gameId, player_number: playerNumber });
            if (!res.success) { console.error('get_state failed:', res.error); return; }
            currentState = res.data;
            renderState(currentState);
        } catch (e) {
            console.error('loadState error:', e);
        }
    }

    function renderState(state) {
        if (!state) return;

        // Energy bar — single solid color transitioning green→yellow→red as energy drops
        const energy = state.game ? Math.max(0, Math.min(100, state.game.energy || 0)) : 100;
        if (energyBar) {
            energyBar.style.width = energy + '%';
            const hue = Math.round(energy * 1.2); // 0=red(0), 50=yellow(60), 100=green(120)
            energyBar.style.background = `hsl(${hue}, 85%, 38%)`;
        }
        if (energyValue) energyValue.textContent = energy + '%';

        // Round info
        if (roundInfo && state.game) {
            roundInfo.textContent = 'Runde ' + (state.game.round_number || 1);
        }

        // Players
        if (state.players) {
            state.players.forEach(p => {
                const idx = p.player_number - 1;
                if (playerNameEls[idx]) playerNameEls[idx].textContent = p.player_name;
                if (playerScoreEls[idx]) playerScoreEls[idx].textContent = p.total_score;
            });
        }

        // Current question
        if (state.current_question) {
            const q = state.current_question;
            const totalQ = state.total_questions || state.game?.current_round_size || '?';
            const qIdx = (state.game?.current_question_index || 0) + 1;
            if (questionNumber) questionNumber.textContent = `Frage ${qIdx} / ${totalQ}`;
            if (questionText) questionText.textContent = q.question_text;
        }

        // Strikes
        const strikes = state.strikes || 0;
        strikeSlots.forEach((slot, i) => {
            slot.classList.toggle('active', i < strikes);
        });

        // Answer board
        buildAnswerBoard(state);

        // Game status
        const status = state.game ? state.game.status : 'active';

        if (status === 'finished' && !gameFinished) {
            gameFinished = true;
            stopPolling();
            showGameEnd(state);
            return;
        }

        if (status === 'round_end') {
            stopPolling();
            showRoundEnd(state);
            return;
        }

        // Turn handling
        updateTurnUI(state);
    }

    /* ----------------------------------------------------------------
       Answer board
       ---------------------------------------------------------------- */
    function buildAnswerBoard(state) {
        if (!answerBoard) return;

        const revealed = state.revealed_answers || [];
        // Determine total slots: max display_order seen, or at least 8
        let maxOrder = 8;
        revealed.forEach(a => { if (a.display_order > maxOrder) maxOrder = a.display_order; });

        // Build map of revealed answers by position
        const revealedMap = {};
        revealed.forEach(a => { revealedMap[a.display_order] = a; });

        // Check if board already matches (avoid full re-render on poll if same revealed count)
        const existingSlots = answerBoard.querySelectorAll('.answer-slot');
        const needsReveal = [];
        if (existingSlots.length === maxOrder) {
            // Only update newly revealed slots
            revealed.forEach(a => {
                const existing = answerBoard.querySelector(`[data-order="${a.display_order}"]`);
                if (existing && !existing.classList.contains('revealed')) {
                    needsReveal.push({ el: existing, answer: a });
                }
            });
            needsReveal.forEach(({ el, answer }) => revealSlotEl(el, answer));
            return;
        }

        // Full render
        answerBoard.innerHTML = '';
        for (let i = 1; i <= maxOrder; i++) {
            const slot = document.createElement('div');
            slot.className = 'answer-slot';
            slot.dataset.order = i;

            const rank = document.createElement('span');
            rank.className = 'slot-rank';
            rank.textContent = i;

            const text = document.createElement('span');
            text.className = 'slot-text';

            const pts = document.createElement('span');
            pts.className = 'slot-points';

            slot.appendChild(rank);
            slot.appendChild(text);
            slot.appendChild(pts);

            if (revealedMap[i]) {
                applyRevealedState(slot, text, pts, revealedMap[i], false);
            } else {
                text.textContent = '???';
                pts.textContent = '';
            }

            answerBoard.appendChild(slot);
        }
    }

    function revealSlotEl(el, answer) {
        const text = el.querySelector('.slot-text');
        const pts  = el.querySelector('.slot-points');
        applyRevealedState(el, text, pts, answer, true);
    }

    function applyRevealedState(slotEl, textEl, ptsEl, answer, animate) {
        slotEl.classList.add('revealed');
        textEl.textContent = answer.answer_text;
        ptsEl.textContent  = answer.points + ' Pkt.';
        if (!animate) slotEl.style.animation = 'none';
    }

    /* ----------------------------------------------------------------
       Turn UI
       ---------------------------------------------------------------- */
    function updateTurnUI(state) {
        const currentPlayerNum = state.current_player;
        const isMyTurn = (currentPlayerNum === playerNumber);

        // Turn indicators
        turnIndicators.forEach((ind, i) => {
            const active = (i + 1) === currentPlayerNum;
            ind.classList.toggle('active', active);
        });
        scoreCards.forEach((card, i) => {
            card.classList.toggle('active-turn', (i + 1) === currentPlayerNum);
        });

        // Whose turn label
        const currentP = state.players ? state.players.find(p => p.player_number === currentPlayerNum) : null;
        const name = currentP ? currentP.player_name : 'Spieler ' + currentPlayerNum;

        if (mode === 'local') {
            // Local: both players on same device — show whose turn it is
            if (whoseTurn) whoseTurn.textContent = `${name} ist dran! 🎯`;
            showInputArea(true);
        } else {
            // Online: enable input only for this player's turn
            if (isMyTurn) {
                if (whoseTurn) whoseTurn.textContent = 'Dein Zug! 🎯';
                showInputArea(true);
                stopPolling();
            } else {
                if (whoseTurn) whoseTurn.textContent = '';
                showInputArea(false);
                if (waitingText) waitingText.textContent = `${name} ist dran...`;
                startPolling();
            }
        }
    }

    function showInputArea(show) {
        if (answerInputArea) answerInputArea.classList.toggle('hidden', !show);
        if (waitingTurn) waitingTurn.classList.toggle('hidden', show);
        if (show && answerInput) {
            answerInput.value = '';
            answerInput.focus();
        }
    }

    /* ----------------------------------------------------------------
       Submit answer
       ---------------------------------------------------------------- */
    async function submitAnswer() {
        const text = answerInput ? answerInput.value.trim() : '';
        if (!text) return;

        submitAnswerBtn.disabled = true;
        passBtn.disabled = true;

        try {
            const res = await apiPost(apiUrl, {
                action: 'submit_answer',
                game_id: gameId,
                player_number: playerNumber,
                answer_text: text
            });

            if (!res.success) {
                showFeedback('Fehler: ' + (res.error || 'Unbekannt'), 'error');
                submitAnswerBtn.disabled = false;
                passBtn.disabled = false;
                return;
            }

            const d = res.data;
            if (answerInput) answerInput.value = '';

            if (d.correct) {
                showFeedback('✓ Richtig! +' + d.points + ' Punkte', 'success');
            } else {
                showFeedback('✗ Nicht gefunden', 'error');
            }

            if (d.game_state) {
                currentState = d.game_state;

                // Animate newly revealed answer
                if (d.correct && d.answer_revealed) {
                    setTimeout(() => {
                        const slot = answerBoard ? answerBoard.querySelector(`[data-order="${d.answer_revealed.display_order}"]`) : null;
                        if (slot && !slot.classList.contains('revealed')) {
                            revealSlotEl(slot, d.answer_revealed);
                        }
                    }, 150);
                }

                setTimeout(() => {
                    renderState(d.game_state);
                    submitAnswerBtn.disabled = false;
                    passBtn.disabled = false;
                }, 900);
            } else {
                submitAnswerBtn.disabled = false;
                passBtn.disabled = false;
            }
        } catch (e) {
            console.error('submitAnswer error:', e);
            showFeedback('Verbindungsfehler', 'error');
            submitAnswerBtn.disabled = false;
            passBtn.disabled = false;
        }
    }

    /* ----------------------------------------------------------------
       Pass turn
       ---------------------------------------------------------------- */
    async function passTurn() {
        passBtn.disabled = true;
        submitAnswerBtn.disabled = true;

        try {
            const res = await apiPost(apiUrl, {
                action: 'pass_turn',
                game_id: gameId,
                player_number: playerNumber
            });
            if (res.success && res.data) {
                currentState = res.data;
                renderState(res.data);
            }
        } catch (e) {
            console.error('passTurn error:', e);
        }

        passBtn.disabled = false;
        submitAnswerBtn.disabled = false;
    }

    /* ----------------------------------------------------------------
       Feedback
       ---------------------------------------------------------------- */
    function showFeedback(msg, type) {
        if (!answerFeedback) return;
        answerFeedback.textContent = msg;
        answerFeedback.className = 'answer-feedback ' + type;
        answerFeedback.classList.remove('hidden');
        clearTimeout(answerFeedback._timer);
        answerFeedback._timer = setTimeout(() => answerFeedback.classList.add('hidden'), 2500);
    }

    /* ----------------------------------------------------------------
       Round end overlay
       ---------------------------------------------------------------- */
    function showRoundEnd(state) {
        if (!roundEndOverlay) return;
        if (roundScores) {
            roundScores.innerHTML = '';
            if (state.players) {
                state.players.forEach(p => {
                    const row = document.createElement('div');
                    row.style.cssText = 'display:flex;justify-content:space-between;padding:6px 0;font-weight:700;border-bottom:1px solid var(--border)';
                    row.innerHTML = `<span>${p.player_name}</span><span style="font-family:'Fredoka One',cursive;color:var(--gold)">${p.total_score} Pkt.</span>`;
                    roundScores.appendChild(row);
                });
            }
        }
        roundEndOverlay.classList.remove('hidden');
    }

    async function startNewRound() {
        newRoundBtn.disabled = true;
        newRoundBtn.textContent = 'Lade...';
        try {
            const roundSize = currentState && currentState.game ? currentState.game.current_round_size || 5 : 5;
            await apiPost(apiUrl, { action: 'start_round', game_id: gameId, round_size: roundSize });
            roundEndOverlay.classList.add('hidden');
            await loadState();
        } catch (e) {
            console.error('startNewRound error:', e);
        }
        newRoundBtn.disabled = false;
        newRoundBtn.textContent = '🎮 Neue Runde';
    }

    /* ----------------------------------------------------------------
       Game end overlay
       ---------------------------------------------------------------- */
    function showGameEnd(state) {
        if (!gameEndOverlay) return;

        const players = state.players || [];
        let winner = players.reduce((a, b) => (b.total_score > a.total_score ? b : a), players[0] || { player_name: '?', total_score: 0 });

        if (winnerName) winnerName.textContent = winner.player_name;
        if (finalScores) {
            finalScores.innerHTML = '';
            players.forEach(p => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex;justify-content:space-between;padding:6px 0;font-weight:700;font-size:1.1rem';
                row.innerHTML = `<span>${p.player_name}</span><span style="font-family:'Fredoka One',cursive;color:var(--gold)">${p.total_score} Pkt.</span>`;
                finalScores.appendChild(row);
            });
        }

        gameEndOverlay.classList.remove('hidden');
        launchConfetti();
    }

    /* ----------------------------------------------------------------
       Confetti
       ---------------------------------------------------------------- */
    function launchConfetti() {
        if (!confettiContainer) return;
        confettiContainer.innerHTML = '';
        const colors = ['#E4A700','#072475','#13563B','#C70000','#ffffff','#ff9900','#00ccff'];
        for (let i = 0; i < 120; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left  = Math.random() * 100;
            const delay = Math.random() * 3;
            const dur   = Math.random() * 2 + 2;
            const size  = Math.random() * 8 + 6;
            const borderRadius = Math.random() > 0.5 ? '50%' : '2px';
            piece.style.cssText = [
                `left:${left}%`,
                `background:${color}`,
                `width:${size}px`,
                `height:${size}px`,
                `border-radius:${borderRadius}`,
                `animation-duration:${dur}s`,
                `animation-delay:${delay}s`
            ].join(';');
            confettiContainer.appendChild(piece);
        }
    }

    /* ----------------------------------------------------------------
       Polling (online mode)
       ---------------------------------------------------------------- */
    function startPolling() {
        if (pollInterval) return;
        pollInterval = setInterval(async () => {
            if (gameFinished) { stopPolling(); return; }
            try {
                const res = await apiPost(apiUrl, { action: 'get_state', game_id: gameId, player_number: playerNumber });
                if (res.success && res.data) {
                    const newState = res.data;
                    const newCurrentPlayer = newState.current_player;
                    const statusChanged = currentState && currentState.game && newState.game &&
                        currentState.game.status !== newState.game.status;
                    const turnChanged = currentState && currentState.current_player !== newCurrentPlayer;

                    currentState = newState;

                    if (statusChanged || turnChanged || newState.game.status !== 'active') {
                        stopPolling();
                        renderState(newState);
                    } else {
                        // Just update scores and board silently
                        buildAnswerBoard(newState);
                        if (newState.players) {
                            newState.players.forEach(p => {
                                const idx = p.player_number - 1;
                                if (playerScoreEls[idx]) playerScoreEls[idx].textContent = p.total_score;
                            });
                        }
                    }
                }
            } catch (e) { console.warn('poll error:', e); }
        }, 2000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }
}
