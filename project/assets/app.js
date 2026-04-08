/* ================================================================
   People Say So – app.js
   Client-side game state management, audio, animations
   ================================================================ */

'use strict';

const MAX_POLL_FAILURES = 10;

// ================================================================
// Theme & Music (run on every page immediately)
// ================================================================
(function initThemeAndMusic() {
    const saved = localStorage.getItem('pss_theme') || 'light';
    document.documentElement.dataset.theme = saved;
    document.body.dataset.theme = saved;

    function updateThemeBtn() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;
        btn.textContent = document.body.dataset.theme === 'dark' ? '☀️' : '🌙';
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateThemeBtn();
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', () => {
                const next = document.body.dataset.theme === 'dark' ? 'light' : 'dark';
                document.body.dataset.theme = next;
                document.documentElement.dataset.theme = next;
                localStorage.setItem('pss_theme', next);
                updateThemeBtn();
            });
        }
    });
})();

// ================================================================
// Audio Engine (Web Audio API – no external files)
// ================================================================
const Audio8Bit = (function() {
    let ctx = null;
    let musicNodes = [];
    let musicPlaying = false;
    let musicEnabled = localStorage.getItem('pss_music') !== 'off';

    function getCtx() {
        if (!ctx) {
            try {
                ctx = new (window.AudioContext || window.webkitAudioContext)();
            } catch(e) { return null; }
        }
        return ctx;
    }

    function resumeCtx() {
        const c = getCtx();
        if (c && c.state === 'suspended') c.resume();
        return c;
    }

    // Simple beep
    function beep(freq, duration, type = 'square', gain = 0.3, delay = 0) {
        const c = resumeCtx();
        if (!c) return;
        const o = c.createOscillator();
        const g = c.createGain();
        o.connect(g);
        g.connect(c.destination);
        o.type = type;
        o.frequency.setValueAtTime(freq, c.currentTime + delay);
        g.gain.setValueAtTime(0, c.currentTime + delay);
        g.gain.linearRampToValueAtTime(gain, c.currentTime + delay + 0.01);
        g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + delay + duration);
        o.start(c.currentTime + delay);
        o.stop(c.currentTime + delay + duration + 0.05);
    }

    function playCorrect() {
        beep(523, 0.12, 'square', 0.25);
        beep(659, 0.12, 'square', 0.25, 0.13);
        beep(784, 0.2,  'square', 0.25, 0.26);
    }

    function playWrong() {
        beep(220, 0.08, 'sawtooth', 0.3);
        beep(180, 0.15, 'sawtooth', 0.3, 0.09);
    }

    function playReveal() {
        beep(880, 0.1, 'sine', 0.2);
        beep(1100, 0.1, 'sine', 0.2, 0.11);
    }

    function playWin() {
        const melody = [523,659,784,1047,784,1047,1319];
        melody.forEach((f, i) => beep(f, 0.15, 'square', 0.25, i * 0.12));
    }

    // Background music – simple looping 8-bit melody (Mario-ish feel)
    const MELODY = [
        // [freq, duration_beats]
        [659,0.5],[659,0.5],[0,0.5],[659,0.5],[0,0.5],[523,0.5],[659,0.5],
        [784,1],[0,1],
        [392,1],[0,1],
        [523,1],[0,0.5],[392,0.5],[0,0.5],[330,0.5],
        [440,1],[494,1],[466,0.5],[440,1],
        [392,0.67],[659,0.67],[784,0.67],[880,1],
        [698,0.5],[784,0.5],[0,0.5],[659,1],
        [523,0.5],[587,0.5],[494,1],[0,1],
        [523,1],[0,0.5],[392,0.5],[0,0.5],[330,0.5],
        [440,1],[494,1],[466,0.5],[440,1],
        [392,0.67],[659,0.67],[784,0.67],[880,1],
        [698,0.5],[784,0.5],[0,0.5],[659,1],
        [523,0.5],[587,0.5],[494,1],
    ];
    const BEAT = 0.22; // seconds per beat

    let musicScheduled = false;
    let musicTimeout = null;

    function scheduleMelody(startTime) {
        const c = resumeCtx();
        if (!c || !musicEnabled) return;

        let t = startTime;
        const nodes = [];
        MELODY.forEach(([freq, beats]) => {
            if (freq > 0) {
                const o = c.createOscillator();
                const g = c.createGain();
                o.connect(g);
                g.connect(c.destination);
                o.type = 'square';
                o.frequency.setValueAtTime(freq, t);
                g.gain.setValueAtTime(0.05, t);
                g.gain.linearRampToValueAtTime(0.05, t + beats * BEAT - 0.02);
                g.gain.linearRampToValueAtTime(0, t + beats * BEAT);
                o.start(t);
                o.stop(t + beats * BEAT + 0.05);
                nodes.push(o);
            }
            t += beats * BEAT;
        });
        musicNodes = nodes;

        // Schedule next loop
        const duration = t - startTime;
        musicTimeout = setTimeout(() => {
            if (musicEnabled && musicPlaying) {
                const newCtx = resumeCtx();
                if (newCtx) scheduleMelody(newCtx.currentTime + 0.05);
            }
        }, (duration - 0.3) * 1000);
    }

    function startMusic() {
        if (musicPlaying || !musicEnabled) return;
        musicPlaying = true;
        const c = resumeCtx();
        if (!c) return;
        scheduleMelody(c.currentTime + 0.1);
    }

    function stopMusic() {
        musicPlaying = false;
        if (musicTimeout) clearTimeout(musicTimeout);
        musicNodes.forEach(n => { try { n.stop(); } catch(e) {} });
        musicNodes = [];
    }

    function toggleMusic() {
        musicEnabled = !musicEnabled;
        localStorage.setItem('pss_music', musicEnabled ? 'on' : 'off');
        if (musicEnabled) {
            startMusic();
        } else {
            stopMusic();
        }
        return musicEnabled;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('musicToggle');
        function updateBtn() {
            if (btn) btn.textContent = musicEnabled ? '🎵' : '🔇';
        }
        updateBtn();
        if (btn) {
            btn.addEventListener('click', () => {
                toggleMusic();
                updateBtn();
            });
        }
        // Start music on first user interaction
        const startOnce = () => {
            if (musicEnabled) startMusic();
            document.removeEventListener('click', startOnce);
            document.removeEventListener('keydown', startOnce);
        };
        document.addEventListener('click', startOnce);
        document.addEventListener('keydown', startOnce);
    });

    return { playCorrect, playWrong, playReveal, playWin, startMusic, stopMusic };
})();

// ================================================================
// Local Game Engine – all logic runs in-browser, no server calls
// ================================================================

function levenshteinDistance(a, b) {
    // O(n) space – only two rows needed
    const m = a.length, n = b.length;
    let prev = Array.from({length: n + 1}, (_, j) => j);
    let curr = new Array(n + 1);
    for (let i = 1; i <= m; i++) {
        curr[0] = i;
        for (let j = 1; j <= n; j++) {
            curr[j] = a[i-1] === b[j-1]
                ? prev[j-1]
                : 1 + Math.min(prev[j], curr[j-1], prev[j-1]);
        }
        [prev, curr] = [curr, prev];
    }
    return prev[n];
}

function fuzzyMatchLocal(input, stored) {
    const umlauts = {'ä':'ae','ö':'oe','ü':'ue','ß':'ss','à':'a','á':'a','â':'a',
                     'è':'e','é':'e','ê':'e','ì':'i','í':'i','î':'i',
                     'ò':'o','ó':'o','ô':'o','ù':'u','ú':'u','û':'u'};
    const normalize = s => s.toLowerCase().trim().replace(/\s+/g, ' ')
                            .replace(/[äöüßàáâèéêìíîòóôùúû]/g, c => umlauts[c] || c);
    const normInput  = normalize(input);
    const normStored = normalize(stored);

    if (normInput === normStored) return true;
    if (input.toLowerCase().trim() === stored.toLowerCase().trim()) return true;

    if (normInput.length >= 3 && normStored.length >= 3) {
        if (normStored.includes(normInput) || normInput.includes(normStored)) return true;
    }
    if (normInput.length > 4 && normStored.length > 4) {
        if (levenshteinDistance(normInput, normStored) <= 2) return true;
    }
    return false;
}

class LocalEngine {
    constructor(data) {
        // data = { questions, roundSize, players:[{player_number, player_name}] }
        this.questions        = data.questions.slice();
        this.roundSize        = data.roundSize;
        this.currentIndex     = 0;
        this.questionsPlayed  = 0;
        this.roundNumber      = 1;
        this.energy           = 100;
        this.status           = 'active';
        this.players          = data.players.map(p => ({
            player_number:  p.player_number,
            player_name:    p.player_name,
            total_score:    0,
            is_current_turn: p.player_number === 1 ? 1 : 0,
        }));
        this.revealedAnswers  = {};  // questionId → [answerId, …]
        this.strikes          = {};  // questionId → count
    }

    getState() {
        const q       = this.questions[this.currentIndex] || null;
        const revIds  = q ? (this.revealedAnswers[q.id] || []) : [];
        const revObjs = q
            ? (q.answers || []).filter(a => revIds.includes(a.id))
                               .sort((a, b) => (a.display_order || 0) - (b.display_order || 0))
            : [];
        return {
            game: {
                id:                          0,
                game_code:                   '',
                mode:                        'local',
                status:                      this.status,
                energy:                      this.energy,
                current_question_index:      this.currentIndex,
                current_round_size:          this.roundSize,
                questions_played_this_round: this.questionsPlayed,
                round_number:                this.roundNumber,
            },
            players:          this.players,
            current_question: q,
            revealed_answers: revObjs,
            strikes:          q ? (this.strikes[q.id] || 0) : 0,
            total_questions:  this.roundSize,
            current_player:   null,
        };
    }

    submitAnswer(playerNumber, answerText) {
        const q = this.questions[this.currentIndex];
        if (!q) return { success: false, error: 'Keine aktuelle Frage.' };

        const revIds  = this.revealedAnswers[q.id] || [];
        let   matched = null;
        for (const answer of (q.answers || [])) {
            if (revIds.includes(answer.id)) continue;
            if (fuzzyMatchLocal(answerText, answer.answer_text)) { matched = answer; break; }
        }

        const result = {
            correct: false, points: 0, answer_revealed: null,
            all_revealed: false, round_ended: false, strike_count: 0,
        };

        if (matched) {
            const player = this.players.find(p => p.player_number === playerNumber);
            if (player) player.total_score += matched.points;

            if (!this.revealedAnswers[q.id]) this.revealedAnswers[q.id] = [];
            this.revealedAnswers[q.id].push(matched.id);

            result.correct         = true;
            result.points          = matched.points;
            result.answer_revealed = matched;

            if (this.revealedAnswers[q.id].length >= (q.answers || []).length) {
                result.all_revealed = true;
            }
            this._switchTurn(playerNumber);
        } else {
            if (!this.strikes[q.id]) this.strikes[q.id] = 0;
            this.strikes[q.id]++;
            result.strike_count = this.strikes[q.id];
            if (this.strikes[q.id] >= 3) result.all_revealed = true;
            this._switchTurn(playerNumber);
        }

        if (result.all_revealed) result.round_ended = this._advanceQuestion();

        result.game_state = this.getState();
        return { success: true, data: result };
    }

    passTurn(playerNumber) {
        this._switchTurn(playerNumber);
        return { success: true, data: this.getState() };
    }

    startNewRound() {
        this._shuffle();
        this.currentIndex    = 0;
        this.questionsPlayed = 0;
        this.roundNumber++;
        this.revealedAnswers = {};
        this.strikes         = {};
        this.status          = 'active';
        this.players.forEach(p => { p.is_current_turn = p.player_number === 1 ? 1 : 0; });
        return { success: true, data: this.getState() };
    }

    _switchTurn(current) {
        const next = current === 1 ? 2 : 1;
        this.players.forEach(p => { p.is_current_turn = p.player_number === next ? 1 : 0; });
    }

    _advanceQuestion() {
        this.currentIndex++;
        this.questionsPlayed++;
        const cost = Math.round(100 / this.roundSize);
        this.energy = Math.max(0, this.energy - cost);
        if (this.questionsPlayed >= this.roundSize || this.energy <= 0) {
            this.status = this.energy <= 0 ? 'finished' : 'round_end';
            return true;
        }
        this.players.forEach(p => { p.is_current_turn = p.player_number === 1 ? 1 : 0; });
        return false;
    }

    _shuffle() {
        for (let i = this.questions.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [this.questions[i], this.questions[j]] = [this.questions[j], this.questions[i]];
        }
    }
}

// ================================================================
// GameState class
// ================================================================
class GameState {
    constructor(config) {
        this.gameId       = config.gameId;
        this.playerNumber = config.playerNumber;
        this.gameCode     = config.gameCode;
        this.mode         = config.mode;
        this.apiUrl       = config.apiUrl || 'api.php';

        this.state        = null;
        this.pollInterval = null;
        this.submitting   = false;
        this.lastQuestionId = null;

        // Local mode: run entirely in-browser
        this.localEngine = (config.mode === 'local' && config.localData)
            ? new LocalEngine(config.localData)
            : null;
    }

    async api(payload) {
        const res = await fetch(this.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return res.json();
    }

    async loadState() {
        if (this.localEngine) {
            this.state = this.localEngine.getState();
            this.render();
            return { success: true };
        }
        try {
            const res = await this.api({
                action: 'get_state',
                game_id: this.gameId,
                player_number: this.playerNumber
            });
            if (res.success) {
                this._pollFailures = 0;
                this.state = res.data;
                this.render();
            }
            return res;
        } catch(e) {
            this._pollFailures = (this._pollFailures || 0) + 1;
            // Stop polling after 10 consecutive failures (server unavailable)
            if (this._pollFailures >= MAX_POLL_FAILURES) {
                this.stopPolling();
                showFeedback('wrong', 'Verbindung unterbrochen. Bitte Seite neu laden.');
            }
            return null;
        }
    }

    startPolling() {
        if (this.mode === 'online') {
            this._pollFailures = 0;
            this.pollInterval = setInterval(() => this.loadState(), 2000);
        }
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    isMyTurn() {
        if (!this.state || !this.state.players) return false;
        const me = this.state.players.find(p => parseInt(p.player_number) === this.playerNumber);
        return me && parseInt(me.is_current_turn) === 1;
    }

    getPlayer(num) {
        if (!this.state || !this.state.players) return null;
        return this.state.players.find(p => parseInt(p.player_number) === num);
    }

    getCurrentPlayer() {
        if (!this.state || !this.state.players) return null;
        return this.state.players.find(p => parseInt(p.is_current_turn) === 1);
    }

    // ---- Render ----
    render() {
        if (!this.state) return;
        const { game, players, current_question, revealed_answers, strikes, total_questions } = this.state;

        // Energy bar
        updateEnergyBar(parseInt(game.energy) || 0);

        // Round info
        const roundInfo = document.getElementById('roundInfo');
        if (roundInfo) roundInfo.textContent = `Runde ${game.round_number} · ${game.questions_played_this_round + 1}/${game.current_round_size}`;

        // Scores
        const p1 = players.find(p => parseInt(p.player_number) === 1);
        const p2 = players.find(p => parseInt(p.player_number) === 2);
        if (p1) {
            setText('player1Name', p1.player_name);
            setText('player1Score', p1.total_score);
            document.getElementById('scoreCard1')?.classList.toggle('is-active', parseInt(p1.is_current_turn) === 1);
        }
        if (p2) {
            setText('player2Name', p2.player_name);
            setText('player2Score', p2.total_score);
            document.getElementById('scoreCard2')?.classList.toggle('is-active', parseInt(p2.is_current_turn) === 1);
        }

        // Question
        if (current_question) {
            const qNum = parseInt(game.questions_played_this_round) + 1;
            setText('questionNumber', `Frage ${qNum} / ${game.current_round_size}`);
            setText('questionText', current_question.question_text);
            this.renderAnswerBoard(current_question, revealed_answers || []);

            // Detect question change
            if (this.lastQuestionId !== current_question.id) {
                this.lastQuestionId = current_question.id;
                this.resetStrikes();
            }
        }

        // Strikes
        this.renderStrikes(strikes || 0);

        // Turn display
        this.renderTurnPanel();

        // Status
        if (game.status === 'round_end' || game.status === 'finished') {
            this.stopPolling();
            if (game.status === 'finished') {
                setTimeout(() => this.showGameEnd(), 800);
            } else {
                setTimeout(() => this.showRoundEnd(), 500);
            }
        }
    }

    renderAnswerBoard(question, revealedAnswers) {
        const board = document.getElementById('answerBoard');
        if (!board) return;

        const answers = question.answers || [];
        const revealedIds = revealedAnswers.map(a => parseInt(a.id));

        // Only rebuild if needed
        const existing = board.querySelectorAll('.answer-slot');
        if (existing.length !== answers.length) {
            board.innerHTML = '';
            answers.forEach((ans, idx) => {
                const slot = document.createElement('div');
                slot.className = 'answer-slot';
                slot.dataset.answerId = ans.id;
                slot.innerHTML = `
                    <span class="answer-slot-number">${idx + 1}</span>
                    <span class="answer-slot-text"></span>
                    <span class="answer-slot-points"></span>
                `;
                board.appendChild(slot);
            });
        }

        answers.forEach((ans, idx) => {
            const slot = board.querySelector(`[data-answer-id="${ans.id}"]`);
            if (!slot) return;
            const isRevealed = revealedIds.includes(parseInt(ans.id));
            const wasRevealed = slot.classList.contains('revealed');

            if (isRevealed && !wasRevealed) {
                slot.classList.add('revealed');
                slot.querySelector('.answer-slot-text').textContent = ans.answer_text;
                slot.querySelector('.answer-slot-points').textContent = ans.points;
                Audio8Bit.playReveal();
            } else if (isRevealed) {
                slot.querySelector('.answer-slot-text').textContent = ans.answer_text;
                slot.querySelector('.answer-slot-points').textContent = ans.points;
            } else {
                slot.querySelector('.answer-slot-text').textContent = '???';
                slot.querySelector('.answer-slot-points').textContent = '';
            }
        });
    }

    renderStrikes(count) {
        for (let i = 1; i <= 3; i++) {
            const el = document.getElementById(`strike${i}`);
            if (el) el.classList.toggle('active', i <= count);
        }
    }

    resetStrikes() {
        for (let i = 1; i <= 3; i++) {
            const el = document.getElementById(`strike${i}`);
            if (el) el.classList.remove('active');
        }
    }

    renderTurnPanel() {
        const myTurn = this.mode === 'local' ? true : this.isMyTurn();
        const currentPlayer = this.getCurrentPlayer();
        const inputArea = document.getElementById('answerInputArea');
        const waitingDiv = document.getElementById('waitingTurn');
        const whoseTurn = document.getElementById('whoseTurn');

        if (!currentPlayer) return;

        const name = currentPlayer.player_name;

        if (this.mode === 'local') {
            // Local: always show input, indicate whose turn
            inputArea?.classList.remove('hidden');
            waitingDiv?.classList.add('hidden');
            if (whoseTurn) {
                const isMe = parseInt(currentPlayer.player_number) === this.playerNumber;
                whoseTurn.textContent = `${name} ist dran! 🎯`;
            }
        } else {
            if (this.isMyTurn()) {
                inputArea?.classList.remove('hidden');
                waitingDiv?.classList.add('hidden');
                if (whoseTurn) whoseTurn.textContent = `Dein Zug! 🎯`;
            } else {
                inputArea?.classList.add('hidden');
                waitingDiv?.classList.remove('hidden');
                const waitTxt = document.getElementById('waitingText');
                if (waitTxt) waitTxt.textContent = `${name} ist dran...`;
            }
        }
    }

    async submitAnswer() {
        if (this.submitting) return;
        const input = document.getElementById('answerInput');
        if (!input) return;
        const text = input.value.trim();
        if (!text) { input.focus(); return; }

        // For local mode, determine current player number from state
        let playerNum = this.playerNumber;
        if (this.mode === 'local') {
            const current = this.getCurrentPlayer();
            if (current) playerNum = parseInt(current.player_number);
        }

        this.submitting = true;
        const btn = document.getElementById('submitAnswer');
        if (btn) { btn.disabled = true; btn.textContent = '...'; }

        try {
            let res;
            if (this.localEngine) {
                res = this.localEngine.submitAnswer(playerNum, text);
            } else {
                res = await this.api({
                    action: 'submit_answer',
                    game_id: this.gameId,
                    player_number: playerNum,
                    answer_text: text
                });
            }

            if (res.success) {
                const data = res.data;
                input.value = '';

                if (data.correct) {
                    handleCorrectAnswer(data.answer_revealed, data.points);
                    this.showScorePop(data.points);
                } else {
                    handleWrongAnswer(data.strike_count || 0);
                }

                if (data.game_state) {
                    this.state = data.game_state;
                    this.render();
                }

                if (data.round_ended) {
                    this.stopPolling();
                    setTimeout(() => {
                        if (this.state?.game?.status === 'finished') {
                            this.showGameEnd();
                        } else {
                            this.showRoundEnd();
                        }
                    }, 1200);
                }
            } else {
                showFeedback('wrong', res.error || 'Fehler');
            }
        } catch(e) {
            showFeedback('wrong', 'Verbindungsfehler');
        } finally {
            this.submitting = false;
            if (btn) { btn.disabled = false; btn.textContent = '✔ Senden'; }
            input.focus();
        }
    }

    async passTurn() {
        let playerNum = this.playerNumber;
        if (this.mode === 'local') {
            const current = this.getCurrentPlayer();
            if (current) playerNum = parseInt(current.player_number);
        }

        try {
            let res;
            if (this.localEngine) {
                res = this.localEngine.passTurn(playerNum);
            } else {
                res = await this.api({
                    action: 'pass_turn',
                    game_id: this.gameId,
                    player_number: playerNum
                });
            }
            if (res.success) {
                this.state = res.data;
                this.render();
                showFeedback('wrong', 'Ausgesetzt!');
            }
        } catch(e) {
            console.error('Aussetzen fehlgeschlagen:', e);
            showFeedback('wrong', 'Fehler beim Aussetzen. Bitte erneut versuchen.');
        }
    }

    showScorePop(points) {
        const scoreboard = document.getElementById('scoreboard');
        if (!scoreboard) return;
        const pop = document.createElement('div');
        pop.className = 'score-pop';
        pop.textContent = `+${points}`;
        pop.style.top = '50%';
        pop.style.left = '50%';
        pop.style.transform = 'translate(-50%,-50%)';
        scoreboard.style.position = 'relative';
        scoreboard.appendChild(pop);
        setTimeout(() => pop.remove(), 1300);
    }

    showRoundEnd() {
        const overlay = document.getElementById('roundEndOverlay');
        if (!overlay) return;

        const scoresDiv = document.getElementById('roundScores');
        if (scoresDiv && this.state?.players) {
            scoresDiv.innerHTML = '';
            const sorted = [...this.state.players].sort((a, b) => b.total_score - a.total_score);
            sorted.forEach((p, idx) => {
                const div = document.createElement('div');
                div.className = 'score-row' + (idx === 0 ? ' winner-row' : '');
                div.innerHTML = `
                    <span class="score-row-name">${idx === 0 ? '🥇' : '🥈'} ${escapeHtml(p.player_name)}</span>
                    <span class="score-row-pts">${p.total_score} Punkte</span>
                `;
                scoresDiv.appendChild(div);
            });
        }

        overlay.classList.remove('hidden');

        // New round button
        const newRoundBtn = document.getElementById('newRoundBtn');
        if (newRoundBtn) {
            newRoundBtn.onclick = async () => {
                overlay.classList.add('hidden');
                const roundSize = this.state?.game?.current_round_size || 5;
                try {
                    let res;
                    if (this.localEngine) {
                        res = this.localEngine.startNewRound();
                    } else {
                        res = await this.api({
                            action: 'start_round',
                            game_id: this.gameId,
                            round_size: roundSize
                        });
                    }
                    if (res.success) {
                        this.state = res.data;
                        this.lastQuestionId = null;
                        this.render();
                        if (this.mode === 'online') this.startPolling();
                    }
                } catch(e) {
                    console.error('Neue Runde starten fehlgeschlagen:', e);
                    showFeedback('wrong', 'Fehler beim Starten der neuen Runde.');
                }
            };
        }
    }

    showGameEnd() {
        const overlay = document.getElementById('gameEndOverlay');
        if (!overlay) return;

        Audio8Bit.playWin();
        confettiExplosion();

        if (this.state?.players) {
            const sorted = [...this.state.players].sort((a, b) => b.total_score - a.total_score);
            const winner = sorted[0];
            const isTie  = sorted.length > 1 && sorted[0].total_score === sorted[1].total_score;

            const winnerTitle = document.getElementById('winnerTitle');
            const winnerName  = document.getElementById('winnerName');
            const finalScores = document.getElementById('finalScores');
            const winnerIcon  = document.getElementById('winnerIcon');

            if (isTie) {
                if (winnerTitle) winnerTitle.textContent = 'Unentschieden!';
                if (winnerIcon)  winnerIcon.textContent = '🤝';
                if (winnerName)  winnerName.textContent = 'Gleiche Punkte!';
            } else {
                if (winnerTitle) winnerTitle.textContent = 'Gewinner!';
                if (winnerIcon)  winnerIcon.textContent = '🏆';
                if (winnerName)  winnerName.textContent = winner.player_name;
            }

            if (finalScores) {
                finalScores.innerHTML = '';
                sorted.forEach((p, idx) => {
                    const div = document.createElement('div');
                    div.className = 'score-row' + (idx === 0 && !isTie ? ' winner-row' : '');
                    div.innerHTML = `
                        <span class="score-row-name">${['🥇','🥈','🥉'][idx] || '•'} ${escapeHtml(p.player_name)}</span>
                        <span class="score-row-pts">${p.total_score} Punkte</span>
                    `;
                    finalScores.appendChild(div);
                });
            }
        }

        overlay.classList.remove('hidden');
    }
}

// ================================================================
// Helper functions
// ================================================================

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function updateEnergyBar(energy) {
    const bar = document.getElementById('energyBar');
    const val = document.getElementById('energyValue');
    if (bar) bar.style.width = Math.max(0, energy) + '%';
    if (val) val.textContent = Math.max(0, energy) + '%';

    // Color shift
    if (bar) {
        if (energy > 60) {
            bar.style.background = 'linear-gradient(90deg, #1db954 0%, #E4A700 70%)';
        } else if (energy > 30) {
            bar.style.background = 'linear-gradient(90deg, #E4A700 0%, #ff8c00 100%)';
        } else {
            bar.style.background = 'linear-gradient(90deg, #C70000 0%, #ff4444 100%)';
        }
    }
}

function handleCorrectAnswer(answer, points) {
    Audio8Bit.playCorrect();
    const input = document.getElementById('answerInput');
    if (input) input.classList.add('correct');
    setTimeout(() => input?.classList.remove('correct'), 600);

    const msg = answer ? `✅ ${answer.answer_text} — ${points} Punkte!` : `✅ Richtig! +${points}`;
    showFeedback('correct', msg);
}

function handleWrongAnswer(strikeCount) {
    Audio8Bit.playWrong();
    const input = document.getElementById('answerInput');
    if (input) {
        input.classList.add('shake');
        setTimeout(() => input.classList.remove('shake'), 500);
    }
    const msgs = ['❌ Falsch!', '❌ Nicht dabei!', '❌ Leider nein!'];
    showFeedback('wrong', msgs[Math.floor(Math.random() * msgs.length)]);
}

function showFeedback(type, msg) {
    const el = document.getElementById('answerFeedback');
    if (!el) return;
    el.className = 'answer-feedback ' + type;
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._hideTimeout);
    el._hideTimeout = setTimeout(() => el.classList.add('hidden'), 2500);
}

// ================================================================
// Confetti
// ================================================================
function confettiExplosion() {
    const container = document.getElementById('confettiContainer');
    if (!container) return;
    container.innerHTML = '';

    const colors = ['#E4A700','#FFD84D','#1db954','#3a7bd5','#C70000','#ffffff','#ff69b4','#00cfff'];
    const count = 80;

    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100;
        const delay = Math.random() * 2;
        const duration = 2.5 + Math.random() * 2;
        const size = 6 + Math.random() * 10;
        const rotate = Math.random() * 360;
        const shapes = ['circle', 'rect', 'rect'];
        const shape = shapes[Math.floor(Math.random() * shapes.length)];

        piece.style.cssText = `
            left: ${left}%;
            width: ${size}px;
            height: ${size * (shape === 'circle' ? 1 : 1.5)}px;
            background: ${color};
            border-radius: ${shape === 'circle' ? '50%' : '2px'};
            animation-duration: ${duration}s;
            animation-delay: ${delay}s;
            transform: rotate(${rotate}deg);
        `;
        container.appendChild(piece);
    }

    // Cleanup after animation
    setTimeout(() => { container.innerHTML = ''; }, 6000);
}

// ================================================================
// Main game initialisation
// ================================================================
function initGame(config) {
    if (!config || !config.gameId) return;

    const game = new GameState(config);

    // Load initial state
    game.loadState().then(() => {
        if (config.mode === 'online') {
            game.startPolling();
        }
    });

    // Submit answer
    const submitBtn = document.getElementById('submitAnswer');
    const answerInput = document.getElementById('answerInput');

    if (submitBtn) {
        submitBtn.addEventListener('click', () => game.submitAnswer());
    }

    if (answerInput) {
        answerInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') game.submitAnswer();
        });
        // Auto-focus
        setTimeout(() => answerInput.focus(), 300);
    }

    // Pass turn
    const passBtn = document.getElementById('passBtn');
    if (passBtn) {
        passBtn.addEventListener('click', () => game.passTurn());
    }

    // Expose for debugging
    window._gameState = game;
}
