/**
 * People Say So - Frontend JavaScript
 * Handles all client-side interactions
 */

// API base URL
const API_URL = 'api.php';

// Global state
let currentUser = null;
let currentSurvey = null;
let currentQuestion = null;
let gameSession = null;
let currentScore = 0;
let strikes = 0;
let revealedAnswers = [];
let allAnswers = [];

// =====================
// API HELPER FUNCTIONS
// =====================

async function apiGet(action, params = {}) {
    const queryParams = new URLSearchParams({ action, ...params });
    const response = await fetch(`${API_URL}?${queryParams}`);
    return response.json();
}

async function apiPost(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    const response = await fetch(API_URL, {
        method: 'POST',
        body: formData
    });
    return response.json();
}

// =====================
// USER & ENERGY FUNCTIONS
// =====================

async function loadUserData() {
    try {
        const result = await apiGet('get_user');
        if (result.success) {
            currentUser = result.user;
            updateEnergyDisplay(currentUser.energy);
            updateStatsDisplay(currentUser);
            updatePlayButton(currentUser.energy);
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

function updateEnergyDisplay(energy) {
    const energyFill = document.getElementById('energyFill');
    const energyText = document.getElementById('energyText');
    
    if (energyFill) {
        energyFill.style.width = `${energy}%`;
        
        // Change color based on energy level
        if (energy <= 20) {
            energyFill.className = 'energy-fill energy-low';
        } else if (energy <= 50) {
            energyFill.className = 'energy-fill energy-medium';
        } else {
            energyFill.className = 'energy-fill energy-high';
        }
    }
    
    if (energyText) {
        energyText.textContent = `${energy}%`;
    }
}

function updateStatsDisplay(user) {
    const totalPoints = document.getElementById('totalPoints');
    const gamesPlayed = document.getElementById('gamesPlayed');
    const surveysCompleted = document.getElementById('surveysCompleted');
    
    if (totalPoints) totalPoints.textContent = user.total_points || 0;
    if (gamesPlayed) gamesPlayed.textContent = user.games_played || 0;
    if (surveysCompleted) surveysCompleted.textContent = user.surveys_completed || 0;
}

function updatePlayButton(energy) {
    const playBtn = document.getElementById('playBtn');
    const energyWarning = document.getElementById('energyWarning');
    
    if (energy < 10) {
        if (playBtn) {
            playBtn.disabled = true;
            playBtn.classList.add('btn-disabled');
        }
        if (energyWarning) {
            energyWarning.style.display = 'block';
        }
    } else {
        if (playBtn) {
            playBtn.disabled = false;
            playBtn.classList.remove('btn-disabled');
        }
        if (energyWarning) {
            energyWarning.style.display = 'none';
        }
    }
}

// =====================
// SURVEY FUNCTIONS
// =====================

function openSurveyModal() {
    const modal = document.getElementById('surveyModal');
    modal.style.display = 'flex';
    loadSurvey();
}

function closeSurveyModal() {
    const modal = document.getElementById('surveyModal');
    modal.style.display = 'none';
    // Refresh user data after closing
    loadUserData();
}

async function loadSurvey() {
    const surveyContent = document.getElementById('surveyContent');
    surveyContent.innerHTML = '<div class="loading">Lade Umfrage...</div>';
    
    try {
        const result = await apiGet('get_survey');
        
        if (result.success && result.survey) {
            currentSurvey = result.survey;
            renderSurvey(result.survey);
        } else {
            surveyContent.innerHTML = `
                <div class="no-survey">
                    <span class="big-icon">✅</span>
                    <h3>Alle Umfragen beantwortet!</h3>
                    <p>Du hast alle verfügbaren Umfragen beantwortet. Komm später wieder!</p>
                    <button class="btn btn-secondary" onclick="closeSurveyModal()">Schließen</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading survey:', error);
        surveyContent.innerHTML = `
            <div class="error-message">
                <p>Fehler beim Laden der Umfrage.</p>
                <button class="btn btn-secondary" onclick="loadSurvey()">Erneut versuchen</button>
            </div>
        `;
    }
}

function renderSurvey(survey) {
    const surveyContent = document.getElementById('surveyContent');
    surveyContent.innerHTML = `
        <div class="survey-question">
            <p class="survey-question-text">${escapeHtml(survey.question)}</p>
            <p class="survey-responses">${survey.total_responses} Antworten bisher</p>
        </div>
        <form class="survey-form" onsubmit="submitSurveyAnswer(event)">
            <input type="text" 
                   id="surveyAnswer" 
                   class="survey-input" 
                   placeholder="Deine Antwort..." 
                   required
                   autocomplete="off"
                   autofocus>
            <button type="submit" class="btn btn-primary btn-block">
                Absenden (+10% Energie)
            </button>
        </form>
        <div class="survey-info">
            <p>💡 Deine Antwort hilft, neue Spielfragen zu erstellen!</p>
        </div>
    `;
}

async function submitSurveyAnswer(event) {
    event.preventDefault();
    
    const answerInput = document.getElementById('surveyAnswer');
    const answer = answerInput.value.trim();
    
    if (!answer || !currentSurvey) return;
    
    const surveyContent = document.getElementById('surveyContent');
    
    try {
        const result = await apiPost('submit_survey', {
            survey_id: currentSurvey.id,
            answer: answer
        });
        
        if (result.success) {
            // Update energy display
            if (result.new_energy !== undefined) {
                updateEnergyDisplay(result.new_energy);
                if (currentUser) {
                    currentUser.energy = result.new_energy;
                    updatePlayButton(result.new_energy);
                }
            }
            
            // Show success and load next survey
            surveyContent.innerHTML = `
                <div class="survey-success">
                    <span class="big-icon">✅</span>
                    <h3>Danke für deine Antwort!</h3>
                    <p class="energy-bonus">+10% Energie erhalten!</p>
                    <button class="btn btn-primary" onclick="loadSurvey()">Nächste Umfrage</button>
                    <button class="btn btn-secondary" onclick="closeSurveyModal()">Schließen</button>
                </div>
            `;
        } else {
            showSurveyError(result.message || 'Ein Fehler ist aufgetreten');
        }
    } catch (error) {
        console.error('Error submitting survey:', error);
        showSurveyError('Verbindungsfehler. Bitte erneut versuchen.');
    }
}

function showSurveyError(message) {
    const surveyContent = document.getElementById('surveyContent');
    const currentContent = surveyContent.innerHTML;
    
    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'survey-error';
    errorDiv.textContent = message;
    surveyContent.insertBefore(errorDiv, surveyContent.firstChild);
    
    // Remove after 3 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 3000);
}

// =====================
// GAME FUNCTIONS
// =====================

function startGame() {
    window.location.href = 'game.php';
}

async function initGame() {
    showGameState('loadingState');
    
    try {
        // First check energy and load user data
        const userResult = await apiGet('get_user');
        if (userResult.success) {
            currentUser = userResult.user;
            updateEnergyDisplay(currentUser.energy);
        }
        
        // Check if can play
        const canPlayResult = await apiGet('can_play');
        
        if (!canPlayResult.can_play) {
            const energyValue = canPlayResult.energy !== undefined ? canPlayResult.energy : (currentUser ? currentUser.energy : 0);
            document.getElementById('currentEnergyDisplay').textContent = `${energyValue}%`;
            showGameState('noEnergyState');
            return;
        }
        
        // Get a question
        const questionResult = await apiGet('get_question');
        
        if (!questionResult.success || !questionResult.question) {
            showGameState('noQuestionsState');
            return;
        }
        
        currentQuestion = questionResult.question;
        
        // Start game session
        const startResult = await apiPost('start_game', {
            question_id: currentQuestion.id
        });
        
        if (!startResult.success) {
            if (startResult.message.includes('Energie')) {
                const energyValue = startResult.energy !== undefined ? startResult.energy : (currentUser ? currentUser.energy : 0);
                document.getElementById('currentEnergyDisplay').textContent = `${energyValue}%`;
                showGameState('noEnergyState');
            } else {
                showGameError(startResult.message);
            }
            return;
        }
        
        gameSession = startResult.session_id;
        updateEnergyDisplay(startResult.energy);
        
        // Get all answers (hidden)
        const answersResult = await apiGet('get_answers', {
            question_id: currentQuestion.id
        });
        
        if (answersResult.success) {
            allAnswers = answersResult.answers;
        }
        
        // Initialize game state
        currentScore = 0;
        strikes = 0;
        revealedAnswers = [];
        
        // Display question
        document.getElementById('questionText').textContent = currentQuestion.question;
        document.getElementById('answerCount').textContent = allAnswers.length;
        
        // Create answer board
        createAnswerBoard();
        
        // Reset strikes display
        updateStrikesDisplay();
        
        // Show game
        showGameState('gamePlayState');
        document.getElementById('answerInput').focus();
        
    } catch (error) {
        console.error('Error initializing game:', error);
        showGameError('Fehler beim Laden des Spiels');
    }
}

/**
 * Show error message in game UI instead of alert
 */
function showGameError(message) {
    const loadingState = document.getElementById('loadingState');
    if (loadingState) {
        loadingState.innerHTML = `
            <div class="no-energy-message">
                <span class="big-icon">⚠️</span>
                <h2>Fehler</h2>
                <p>${escapeHtml(message)}</p>
                <a href="index.php" class="btn btn-primary">Zur Startseite</a>
            </div>
        `;
    }
}

function showGameState(stateId) {
    const states = ['loadingState', 'noEnergyState', 'noQuestionsState', 'gamePlayState', 'gameOverState'];
    states.forEach(state => {
        const element = document.getElementById(state);
        if (element) {
            element.style.display = state === stateId ? 'block' : 'none';
        }
    });
}

function createAnswerBoard() {
    const board = document.getElementById('answerBoard');
    board.innerHTML = '';
    
    allAnswers.forEach((answer, index) => {
        const slot = document.createElement('div');
        slot.className = 'answer-slot';
        slot.id = `answer-${answer.id}`;
        slot.innerHTML = `
            <span class="answer-rank">${index + 1}</span>
            <span class="answer-text">???</span>
            <span class="answer-points">${answer.points}</span>
        `;
        board.appendChild(slot);
    });
}

async function submitAnswer(event) {
    event.preventDefault();
    
    const input = document.getElementById('answerInput');
    const answer = input.value.trim();
    
    if (!answer || !currentQuestion) return;
    
    input.value = '';
    
    try {
        const result = await apiPost('check_answer', {
            question_id: currentQuestion.id,
            answer: answer
        });
        
        if (result.success && result.result) {
            if (result.result.correct) {
                // Correct answer!
                handleCorrectAnswer(result.result);
            } else {
                // Wrong answer
                handleWrongAnswer();
            }
        }
    } catch (error) {
        console.error('Error checking answer:', error);
    }
    
    input.focus();
}

function handleCorrectAnswer(result) {
    // Check if already revealed
    const matchedAnswer = allAnswers.find(a => 
        a.answer.toLowerCase() === result.answer.toLowerCase()
    );
    
    if (!matchedAnswer || revealedAnswers.includes(matchedAnswer.id)) {
        showFeedback('Diese Antwort wurde bereits genannt!', 'warning');
        return;
    }
    
    // Mark as revealed
    revealedAnswers.push(matchedAnswer.id);
    
    // Update score
    currentScore += result.points;
    document.getElementById('currentScore').textContent = currentScore;
    
    // Reveal answer on board
    const slot = document.getElementById(`answer-${matchedAnswer.id}`);
    if (slot) {
        slot.classList.add('revealed');
        slot.querySelector('.answer-text').textContent = result.answer;
    }
    
    // Show feedback
    showFeedback(`✅ ${result.answer} - ${result.points} Punkte!`, 'success');
    
    // Check if all answers found
    if (revealedAnswers.length === allAnswers.length) {
        endGame(true);
    }
}

function handleWrongAnswer() {
    strikes++;
    updateStrikesDisplay();
    showFeedback('❌ Leider falsch!', 'error');
    
    if (strikes >= 3) {
        endGame(false);
    }
}

function updateStrikesDisplay() {
    for (let i = 1; i <= 3; i++) {
        const strike = document.getElementById(`strike${i}`);
        if (strike) {
            if (i <= strikes) {
                strike.classList.add('active');
            } else {
                strike.classList.remove('active');
            }
        }
    }
}

function showFeedback(message, type) {
    const feedback = document.getElementById('feedbackMessage');
    feedback.textContent = message;
    feedback.className = `feedback-message ${type}`;
    feedback.style.display = 'block';
    
    setTimeout(() => {
        feedback.style.display = 'none';
    }, 2000);
}

async function endGame(allFound) {
    // Complete game session
    if (gameSession) {
        await apiPost('complete_game', {
            session_id: gameSession,
            score: currentScore
        });
    }
    
    // Update final score
    document.getElementById('finalScore').textContent = currentScore;
    
    // Show all answers
    const revealedList = document.getElementById('revealedList');
    revealedList.innerHTML = '';
    
    allAnswers.forEach((answer, index) => {
        const wasRevealed = revealedAnswers.includes(answer.id);
        const item = document.createElement('div');
        item.className = `revealed-item ${wasRevealed ? 'found' : 'missed'}`;
        item.innerHTML = `
            <span class="revealed-rank">${index + 1}</span>
            <span class="revealed-text">${escapeHtml(answer.answer)}</span>
            <span class="revealed-points">${answer.points} Pkt</span>
        `;
        revealedList.appendChild(item);
    });
    
    // Refresh user data
    await loadUserData();
    
    // Show game over state
    showGameState('gameOverState');
}

async function playAgain() {
    // Reload user data first
    await loadUserData();
    
    // Check energy
    if (currentUser && currentUser.energy < 10) {
        const energyValue = currentUser.energy !== undefined ? currentUser.energy : 0;
        document.getElementById('currentEnergyDisplay').textContent = `${energyValue}%`;
        showGameState('noEnergyState');
    } else {
        // Reset and start new game
        initGame();
    }
}

function openSurveyFromGame() {
    openSurveyModal();
}

// =====================
// UTILITY FUNCTIONS
// =====================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle modal close on outside click
document.addEventListener('click', function(event) {
    const modal = document.getElementById('surveyModal');
    if (event.target === modal) {
        closeSurveyModal();
    }
});

// Handle escape key to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeSurveyModal();
    }
});
