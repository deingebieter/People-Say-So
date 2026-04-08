# People Say So

Ein interaktives Ratespiel, bei dem Spieler die beliebtesten Antworten auf Umfragen erraten müssen.

## 🎮 Konzept

Das Spiel basiert auf zwei miteinander verbundenen Systemen:

### Spielmodus (Content nutzen)
- Spieler spielen Runden mit bereits vorhandenen Fragen
- Diese Fragen stammen aus abgeschlossenen Umfragen
- Ziel: Punkte sammeln und gegen andere gewinnen
- Jede Runde verbraucht **10% Energie**

### Umfragemodus (Content geben)
- Spieler beantworten Umfragen aktiv
- Dabei liefern sie die Antworten, die später im Spiel gesucht werden
- Jede beantwortete Umfrage gibt **+10% Energie**
- Nach **100 Antworten** wird eine Umfrage zur spielbaren Frage

## 🔋 Energie-System

- **Startenergie:** 50%
- **Spielen:** -10% pro Runde
- **Umfrage:** +10% pro Frage
- **Maximum:** 100%

Die Energie verbindet beide Systeme und zwingt Spieler, regelmäßig zwischen Spielen und Umfragen zu wechseln.

## 📁 Projektstruktur

```
project/
├── index.php          # Startseite mit Spielen/Umfrage Buttons
├── game.php           # Spielseite
├── api.php            # AJAX API Endpoints
├── db.php             # Datenbankverbindung
├── game_logic.php     # Spiellogik und Funktionen
├── database.sql       # Datenbank-Schema
└── assets/
    ├── style.css      # Stylesheet
    └── app.js         # Frontend JavaScript
```

## 🗄️ Datenbank

MySQL-Verbindung:
- **Host:** sql103.hstn.me
- **Port:** 3306
- **Database:** mseet_41580932_p
- **User:** mseet_41580932

### Tabellen

- `users` - Spielerdaten und Energie
- `surveys` - Aktive Umfragen
- `survey_responses` - Umfrageantworten der Spieler
- `game_questions` - Spielbare Fragen (aus konvertierten Umfragen)
- `game_answers` - Antworten zu Spielfragen mit Punkten
- `game_sessions` - Spielsitzungen
- `energy_log` - Protokoll der Energieänderungen

## 🚀 Installation

1. Lade die `database.sql` Datei in phpMyAdmin hoch
2. Lade alle PHP-Dateien auf den Webserver
3. Stelle sicher, dass die Datenbankverbindung in `db.php` korrekt ist
4. Öffne `index.php` im Browser

## 🎨 Farbschema

| Farbe | HEX-Code |
|-------|----------|
| Schwarz | #000000 |
| Dunkelblau | #072475 |
| Dunkelgrün | #13563B |
| Braun | #A46928 |
| Gold/Gelb | #E4A700 |
| Rot | #C70000 |
| Dunkelrot | #7B1414 |
| Weiß | #FFFFFF |

## 🔄 Spielfluss

1. Spieler startet mit 50% Energie
2. Spieler spielt Runden → Energie sinkt
3. Energie wird knapp
4. Spieler beantwortet Umfragen
5. Dafür erhält er Energie zurück
6. Gleichzeitig entstehen neue Spielfragen (nach 100 Antworten)
7. Spieler nutzt diese Fragen wieder im Spiel

## API Endpoints

### User
- `GET api.php?action=get_user` - Benutzerdaten abrufen
- `GET api.php?action=get_stats` - Statistiken abrufen

### Umfragen
- `GET api.php?action=get_survey` - Nächste Umfrage abrufen
- `POST api.php?action=submit_survey` - Umfrage beantworten

### Spiel
- `GET api.php?action=can_play` - Kann spielen (genug Energie)?
- `GET api.php?action=get_question` - Spielfrage abrufen
- `POST api.php?action=start_game` - Spiel starten
- `POST api.php?action=check_answer` - Antwort prüfen
- `POST api.php?action=complete_game` - Spiel abschließen
