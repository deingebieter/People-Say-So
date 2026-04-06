-- People Say So - Database Schema
-- MySQL Database: mseet_41580932_p
-- Server: sql103.hstn.me:3306

-- Users table to track player data and energy
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) DEFAULT NULL,
    energy INT DEFAULT 50,  -- Start with 50% energy
    total_points INT DEFAULT 0,
    games_played INT DEFAULT 0,
    surveys_completed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Surveys table for active survey questions
CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    total_responses INT DEFAULT 0,
    is_converted_to_game TINYINT(1) DEFAULT 0,  -- True when 100 responses reached
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted_at TIMESTAMP NULL DEFAULT NULL
);

-- Survey responses from players
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    user_id INT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_survey (user_id, survey_id)  -- One response per user per survey
);

-- Game questions (converted from surveys with 100+ responses)
CREATE TABLE IF NOT EXISTS game_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    times_played INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Game answers (aggregated from survey responses)
CREATE TABLE IF NOT EXISTS game_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer TEXT NOT NULL,
    response_count INT DEFAULT 1,  -- How many people gave this answer
    points INT DEFAULT 0,  -- Points awarded for this answer (based on popularity)
    rank_position INT DEFAULT 0,  -- 1 = most popular, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES game_questions(id) ON DELETE CASCADE
);

-- Game sessions for tracking matches
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    score INT DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES game_questions(id) ON DELETE CASCADE
);

-- Energy log to track energy changes
CREATE TABLE IF NOT EXISTS energy_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    change_amount INT NOT NULL,  -- Positive for gain, negative for loss
    reason ENUM('game_played', 'survey_completed', 'initial', 'bonus') NOT NULL,
    new_total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample surveys for testing
INSERT INTO surveys (question, is_active) VALUES
('Nenne eine Sache, die Menschen im Supermarkt vergessen zu kaufen', 1),
('Nenne etwas, das man auf einer Party braucht', 1),
('Nenne einen Grund, warum Menschen zu spät zur Arbeit kommen', 1),
('Nenne etwas, das man im Urlaub macht', 1),
('Nenne einen beliebten Geburtstagswunsch', 1);
