-- People Say So - Database Schema
-- MySQL 5.7+

CREATE DATABASE IF NOT EXISTS people_say_so CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE people_say_so;

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(500) NOT NULL,
    total_respondents INT NOT NULL DEFAULT 100,
    category VARCHAR(100) NOT NULL DEFAULT 'Allgemein',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answers table
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games table
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_code VARCHAR(6) NOT NULL UNIQUE,
    mode ENUM('local','online') NOT NULL DEFAULT 'online',
    status ENUM('waiting','active','round_end','finished') NOT NULL DEFAULT 'waiting',
    energy INT NOT NULL DEFAULT 100,
    current_question_index INT NOT NULL DEFAULT 0,
    current_round_size INT NOT NULL DEFAULT 5,
    questions_played_this_round INT NOT NULL DEFAULT 0,
    round_number INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game players table
CREATE TABLE IF NOT EXISTS game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_number TINYINT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    total_score INT NOT NULL DEFAULT 0,
    is_current_turn TINYINT(1) NOT NULL DEFAULT 0,
    device_token VARCHAR(64) NOT NULL DEFAULT '',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_game_player (game_id, player_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game answers table (records player answers)
CREATE TABLE IF NOT EXISTS game_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT NOT NULL,
    player_id INT NOT NULL,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (answer_id) REFERENCES answers(id),
    FOREIGN KEY (player_id) REFERENCES game_players(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game questions table (tracks which questions assigned to a game)
CREATE TABLE IF NOT EXISTS game_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    question_id INT NOT NULL,
    question_order INT NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wrong answers per question (strikes)
CREATE TABLE IF NOT EXISTS game_strikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    question_id INT NOT NULL,
    strike_count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_game_question (game_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA: 30+ German survey questions
-- ============================================================

INSERT INTO questions (question_text, total_respondents, category) VALUES
('Nenne ein Tier im Zoo', 131, 'Tiere'),
('Nenne ein Haustier', 120, 'Tiere'),
('Was essen Deutsche am liebsten zum Frühstück?', 200, 'Essen'),
('Nenne ein typisch deutsches Gericht', 180, 'Essen'),
('Nenne eine beliebte Sportart in Deutschland', 250, 'Sport'),
('Welches Fahrzeug nutzt du am häufigsten?', 160, 'Alltag'),
('Nenne einen Beruf mit hohem Ansehen', 190, 'Berufe'),
('Was machst du am liebsten am Wochenende?', 220, 'Freizeit'),
('Nenne eine beliebte deutsche Stadt', 300, 'Geografie'),
('Was trinkst du am liebsten?', 175, 'Getränke'),
('Nenne ein Gemüse', 145, 'Essen'),
('Nenne eine Obstsorte', 130, 'Essen'),
('Nenne ein Musikinstrument', 200, 'Musik'),
('Was bringst du mit in den Urlaub?', 210, 'Reisen'),
('Nenne ein Land in Europa', 280, 'Geografie'),
('Nenne einen bekannten deutschen Fußballverein', 350, 'Sport'),
('Was macht ein guter Freund aus?', 190, 'Beziehungen'),
('Nenne etwas das man in der Küche findet', 165, 'Alltag'),
('Nenne ein Tier im Bauernhof', 140, 'Tiere'),
('Nenne ein Schulfach', 230, 'Bildung'),
('Was kaufst du im Supermarkt am häufigsten?', 195, 'Alltag'),
('Nenne einen Grund warum Menschen zu spät kommen', 175, 'Alltag'),
('Nenne ein beliebtes Urlaubsland für Deutsche', 260, 'Reisen'),
('Was nervt dich am meisten im Straßenverkehr?', 210, 'Alltag'),
('Nenne ein Tier das fliegen kann', 155, 'Tiere'),
('Was ist das beliebteste Hobby in Deutschland?', 240, 'Freizeit'),
('Nenne eine Farbe', 180, 'Allgemein'),
('Was gehört zu einem schönen Abend zuhause?', 195, 'Freizeit'),
('Nenne ein Meerestier', 150, 'Tiere'),
('Nenne ein Winterkleidungsstück', 165, 'Kleidung'),
('Nenne eine beliebte Sendung im deutschen TV', 220, 'Medien'),
('Nenne einen Gegenstand den man immer dabei hat', 200, 'Alltag');

-- ============================================================
-- ANSWERS for each question
-- ============================================================

-- Q1: Tiere im Zoo
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(1, 'Löwe', 35, 1),
(1, 'Affe', 28, 2),
(1, 'Elefant', 22, 3),
(1, 'Giraffe', 15, 4),
(1, 'Tiger', 12, 5),
(1, 'Zebra', 8, 6),
(1, 'Pinguin', 6, 7),
(1, 'Bär', 5, 8);

-- Q2: Haustier
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(2, 'Hund', 45, 1),
(2, 'Katze', 38, 2),
(2, 'Goldfisch', 18, 3),
(2, 'Hamster', 10, 4),
(2, 'Kaninchen', 5, 5),
(2, 'Vogel', 4, 6);

-- Q3: Frühstück
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(3, 'Brot', 52, 1),
(3, 'Müsli', 38, 2),
(3, 'Eier', 30, 3),
(3, 'Brötchen', 28, 4),
(3, 'Joghurt', 22, 5),
(3, 'Obst', 18, 6),
(3, 'Marmelade', 12, 7);

-- Q4: Deutsches Gericht
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(4, 'Bratwurst', 48, 1),
(4, 'Schnitzel', 42, 2),
(4, 'Sauerkraut', 28, 3),
(4, 'Currywurst', 24, 4),
(4, 'Sauerbraten', 18, 5),
(4, 'Kartoffelsalat', 12, 6),
(4, 'Bretzel', 8, 7);

-- Q5: Sportart
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(5, 'Fußball', 88, 1),
(5, 'Tennis', 42, 2),
(5, 'Schwimmen', 36, 3),
(5, 'Radfahren', 30, 4),
(5, 'Turnen', 24, 5),
(5, 'Basketball', 18, 6),
(5, 'Laufen', 12, 7);

-- Q6: Fahrzeug
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(6, 'Auto', 72, 1),
(6, 'Bus', 34, 2),
(6, 'Fahrrad', 28, 3),
(6, 'Bahn', 14, 4),
(6, 'U-Bahn', 8, 5),
(6, 'Motorrad', 4, 6);

-- Q7: Beruf mit Ansehen
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(7, 'Arzt', 65, 1),
(7, 'Feuerwehrmann', 42, 2),
(7, 'Lehrer', 28, 3),
(7, 'Ingenieur', 22, 4),
(7, 'Pilot', 18, 5),
(7, 'Polizist', 15, 6);

-- Q8: Wochenende
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(8, 'Schlafen', 62, 1),
(8, 'Freunde treffen', 48, 2),
(8, 'Fernsehen', 36, 3),
(8, 'Sport', 28, 4),
(8, 'Spazieren gehen', 24, 5),
(8, 'Kochen', 14, 6),
(8, 'Reisen', 8, 7);

-- Q9: Deutsche Stadt
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(9, 'Berlin', 88, 1),
(9, 'München', 65, 2),
(9, 'Hamburg', 52, 3),
(9, 'Köln', 38, 4),
(9, 'Frankfurt', 28, 5),
(9, 'Stuttgart', 18, 6),
(9, 'Düsseldorf', 11, 7);

-- Q10: Getränke
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(10, 'Wasser', 65, 1),
(10, 'Kaffee', 48, 2),
(10, 'Bier', 28, 3),
(10, 'Saft', 18, 4),
(10, 'Tee', 10, 5),
(10, 'Cola', 6, 6);

-- Q11: Gemüse
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(11, 'Karotte', 38, 1),
(11, 'Tomate', 32, 2),
(11, 'Gurke', 28, 3),
(11, 'Paprika', 22, 4),
(11, 'Zwiebel', 15, 5),
(11, 'Brokkoli', 10, 6);

-- Q12: Obstsorte
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(12, 'Apfel', 48, 1),
(12, 'Banane', 36, 2),
(12, 'Erdbeere', 22, 3),
(12, 'Orange', 12, 4),
(12, 'Traube', 8, 5),
(12, 'Kirsche', 4, 6);

-- Q13: Musikinstrument
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(13, 'Gitarre', 62, 1),
(13, 'Klavier', 48, 2),
(13, 'Schlagzeug', 36, 3),
(13, 'Flöte', 24, 4),
(13, 'Geige', 18, 5),
(13, 'Trompete', 12, 6);

-- Q14: Urlaub mitnehmen
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(14, 'Koffer', 58, 1),
(14, 'Sonnencreme', 44, 2),
(14, 'Reisepass', 38, 3),
(14, 'Handtuch', 28, 4),
(14, 'Kamera', 22, 5),
(14, 'Medikamente', 14, 6),
(14, 'Buch', 6, 7);

-- Q15: Land in Europa
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(15, 'Frankreich', 68, 1),
(15, 'Italien', 58, 2),
(15, 'Spanien', 48, 3),
(15, 'England', 38, 4),
(15, 'Polen', 28, 5),
(15, 'Österreich', 22, 6),
(15, 'Schweiz', 18, 7);

-- Q16: Fußballverein
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(16, 'Bayern München', 110, 1),
(16, 'Borussia Dortmund', 78, 2),
(16, 'Schalke', 45, 3),
(16, 'Hamburger SV', 38, 4),
(16, 'Werder Bremen', 32, 5),
(16, 'RB Leipzig', 28, 6),
(16, 'Bayer Leverkusen', 19, 7);

-- Q17: Guter Freund
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(17, 'Ehrlichkeit', 58, 1),
(17, 'Treue', 48, 2),
(17, 'Humor', 32, 3),
(17, 'Vertrauen', 28, 4),
(17, 'Hilfsbereitschaft', 14, 5),
(17, 'Zuhören', 10, 6);

-- Q18: In der Küche
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(18, 'Messer', 52, 1),
(18, 'Herd', 38, 2),
(18, 'Topf', 30, 3),
(18, 'Kühlschrank', 22, 4),
(18, 'Pfanne', 15, 5),
(18, 'Löffel', 8, 6);

-- Q19: Bauernhoftier
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(19, 'Kuh', 48, 1),
(19, 'Schwein', 36, 2),
(19, 'Huhn', 28, 3),
(19, 'Pferd', 15, 4),
(19, 'Schaf', 8, 5),
(19, 'Ente', 5, 6);

-- Q20: Schulfach
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(20, 'Mathematik', 68, 1),
(20, 'Deutsch', 52, 2),
(20, 'Sport', 42, 3),
(20, 'Englisch', 34, 4),
(20, 'Kunst', 18, 5),
(20, 'Musik', 10, 6),
(20, 'Geschichte', 6, 7);

-- Q21: Supermarkt
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(21, 'Brot', 58, 1),
(21, 'Milch', 48, 2),
(21, 'Obst', 32, 3),
(21, 'Gemüse', 24, 4),
(21, 'Käse', 18, 5),
(21, 'Fleisch', 15, 6);

-- Q22: Zu spät kommen
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(22, 'Verschlafen', 58, 1),
(22, 'Stau', 42, 2),
(22, 'Zug verpasst', 32, 3),
(22, 'Kein Parkplatz', 22, 4),
(22, 'Vergessen', 12, 5),
(22, 'Schlechtes Wetter', 9, 6);

-- Q23: Urlaubsland
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(23, 'Spanien', 75, 1),
(23, 'Italien', 62, 2),
(23, 'Türkei', 48, 3),
(23, 'Griechenland', 36, 4),
(23, 'Kroatien', 22, 5),
(23, 'Österreich', 12, 6),
(23, 'Thailand', 5, 7);

-- Q24: Straßenverkehr
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(24, 'Stau', 68, 1),
(24, 'Raser', 48, 2),
(24, 'Rote Ampel ignorieren', 34, 3),
(24, 'Falsch parken', 28, 4),
(24, 'Handy am Steuer', 22, 5),
(24, 'Baustellen', 10, 6);

-- Q25: Tier das fliegen kann
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(25, 'Vogel', 52, 1),
(25, 'Adler', 32, 2),
(25, 'Schmetterling', 24, 3),
(25, 'Biene', 18, 4),
(25, 'Fledermaus', 14, 5),
(25, 'Möwe', 10, 6),
(25, 'Libelle', 5, 7);

-- Q26: Hobby
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(26, 'Fernsehen', 72, 1),
(26, 'Lesen', 54, 2),
(26, 'Sport', 46, 3),
(26, 'Gärtnern', 32, 4),
(26, 'Kochen', 22, 5),
(26, 'Reisen', 14, 6);

-- Q27: Farbe
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(27, 'Blau', 48, 1),
(27, 'Rot', 42, 2),
(27, 'Grün', 34, 3),
(27, 'Schwarz', 26, 4),
(27, 'Weiß', 18, 5),
(27, 'Gelb', 12, 6);

-- Q28: Schöner Abend
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(28, 'Film schauen', 62, 1),
(28, 'Essen bestellen', 48, 2),
(28, 'Mit Familie sitzen', 36, 3),
(28, 'Baden', 24, 4),
(28, 'Spieleabend', 15, 5),
(28, 'Musik hören', 10, 6);

-- Q29: Meerestier
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(29, 'Fisch', 48, 1),
(29, 'Delfin', 38, 2),
(29, 'Hai', 28, 3),
(29, 'Krake', 18, 4),
(29, 'Qualle', 10, 5),
(29, 'Seehund', 8, 6);

-- Q30: Winterkleidung
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(30, 'Jacke', 58, 1),
(30, 'Schal', 42, 2),
(30, 'Mütze', 36, 3),
(30, 'Handschuhe', 18, 4),
(30, 'Stiefel', 8, 5),
(30, 'Pullover', 3, 6);

-- Q31: TV-Sendung
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(31, 'Tatort', 72, 1),
(31, 'Tagesschau', 52, 2),
(31, 'DSDS', 36, 3),
(31, 'GZSZ', 28, 4),
(31, 'Die Höhle der Löwen', 22, 5),
(31, 'Wer wird Millionär', 10, 6);

-- Q32: Immer dabei haben
INSERT INTO answers (question_id, answer_text, points, display_order) VALUES
(32, 'Handy', 88, 1),
(32, 'Schlüssel', 58, 2),
(32, 'Geldbeutel', 32, 3),
(32, 'Kopfhörer', 12, 4),
(32, 'Ausweis', 6, 5),
(32, 'Ladekabel', 4, 6);
