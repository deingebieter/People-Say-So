<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People Say So - Spielen</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container game-container">
        <!-- Header -->
        <header class="header">
            <a href="index.php" class="back-btn">← Zurück</a>
            <h1 class="logo-small">People Say So</h1>
        </header>

        <!-- Game Content -->
        <main class="game-main">
            <!-- Loading State -->
            <div class="game-state" id="loadingState">
                <div class="loading-spinner"></div>
                <p>Lade Frage...</p>
            </div>

            <!-- No Questions State -->
            <div class="game-state" id="noQuestionsState" style="display: none;">
                <div class="no-questions-message">
                    <span class="big-icon">📭</span>
                    <h2>Keine Fragen verfügbar</h2>
                    <p>Es werden noch mehr Umfrageantworten benötigt, um Spielfragen zu erstellen.</p>
                    <p>Hilf mit, indem du Umfragen beantwortest!</p>
                    <a href="index.php" class="btn btn-primary">Zur Startseite</a>
                </div>
            </div>

            <!-- Game Play State -->
            <div class="game-state" id="gamePlayState" style="display: none;">
                <!-- Question Display -->
                <div class="question-section">
                    <h2 class="question-label">Die Frage lautet:</h2>
                    <p class="question-text" id="questionText"></p>
                    <p class="answer-hint">Finde die <span id="answerCount">10</span> beliebtesten Antworten!</p>
                </div>

                <!-- Score Display -->
                <div class="score-section">
                    <div class="score-display">
                        <span class="score-label">Punkte:</span>
                        <span class="score-value" id="currentScore">0</span>
                    </div>
                    <div class="strikes-display">
                        <span class="strike" id="strike1">✖</span>
                        <span class="strike" id="strike2">✖</span>
                        <span class="strike" id="strike3">✖</span>
                    </div>
                </div>

                <!-- Answer Board -->
                <div class="answer-board" id="answerBoard">
                    <!-- Answers will be populated here -->
                </div>

                <!-- Answer Input -->
                <div class="input-section">
                    <form id="answerForm" onsubmit="submitAnswer(event)">
                        <input type="text" 
                               id="answerInput" 
                               class="answer-input" 
                               placeholder="Deine Antwort eingeben..." 
                               autocomplete="off"
                               autofocus>
                        <button type="submit" class="btn btn-primary">Antworten</button>
                    </form>
                </div>

                <!-- Feedback Message -->
                <div class="feedback-message" id="feedbackMessage"></div>
            </div>

            <!-- Game Over State -->
            <div class="game-state" id="gameOverState" style="display: none;">
                <div class="game-over-content">
                    <h2>🏆 Spiel beendet!</h2>
                    <div class="final-score">
                        <span class="final-score-label">Deine Punkte:</span>
                        <span class="final-score-value" id="finalScore">0</span>
                    </div>
                    
                    <!-- Revealed Answers -->
                    <div class="revealed-answers">
                        <h3>Alle Antworten:</h3>
                        <div class="revealed-list" id="revealedList"></div>
                    </div>

                    <div class="game-over-buttons">
                        <button class="btn btn-primary" onclick="playAgain()">Nochmal spielen</button>
                        <a href="index.php" class="btn btn-secondary">Zur Startseite</a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Survey Modal (for in-game survey) -->
        <div class="modal" id="surveyModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>📝 Umfrage</h2>
                    <button class="close-btn" onclick="closeSurveyModal()">&times;</button>
                </div>
                <div class="modal-body" id="surveyContent">
                    <div class="loading">Lade Umfrage...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        // Initialize game on page load
        document.addEventListener('DOMContentLoaded', function() {
            initGame();
        });
    </script>
</body>
</html>
