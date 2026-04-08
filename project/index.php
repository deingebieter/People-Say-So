<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People Say So - Start</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1 class="logo">People Say So</h1>
        </header>

        <!-- Main Menu -->
        <main class="main-menu">
            <div class="welcome-section">
                <h2>Willkommen!</h2>
                <p class="intro-text">
                    Sage was die Leute sagen! Spiele gegen andere und zeige, 
                    dass du weißt, wie Menschen denken.
                </p>
            </div>

            <!-- Stats Display -->
            <div class="stats-container" id="statsContainer">
                <div class="stat-item">
                    <span class="stat-value" id="totalPoints">0</span>
                    <span class="stat-label">Punkte</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="gamesPlayed">0</span>
                    <span class="stat-label">Spiele</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="surveysCompleted">0</span>
                    <span class="stat-label">Umfragen</span>
                </div>
            </div>

            <!-- Main Action Buttons -->
            <div class="action-buttons">
                <!-- Play Game Button -->
                <button class="btn btn-primary btn-large" id="playBtn" onclick="startGame()">
                    <span class="btn-icon">🎮</span>
                    <span class="btn-text">
                        <strong>Spielen</strong>
                    </span>
                </button>

                <!-- Survey Button -->
                <button class="btn btn-secondary btn-large" id="surveyBtn" onclick="openSurveyModal()">
                    <span class="btn-icon">📝</span>
                    <span class="btn-text">
                        <strong>Umfrage beantworten</strong>
                    </span>
                </button>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <h3>Wie funktioniert es?</h3>
                <div class="info-cards">
                    <div class="info-card">
                        <span class="info-icon">📊</span>
                        <h4>Umfragen</h4>
                        <p>Deine Antworten werden zu neuen Spielfragen. Du gestaltest das Spiel mit!</p>
                    </div>
                    <div class="info-card">
                        <span class="info-icon">🏆</span>
                        <h4>Spielen</h4>
                        <p>Errate die beliebtesten Antworten und sammle Punkte!</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Survey Modal -->
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

        <!-- Footer -->
        <footer class="footer">
            <p>People Say So &copy; 2024</p>
        </footer>
    </div>

    <script src="assets/app.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUserData();
        });
    </script>
</body>
</html>
