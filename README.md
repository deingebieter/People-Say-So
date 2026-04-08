# People Say So 🎯

Das Umfrage-Rätsel-Spiel – lokal auf einem Gerät oder online gegen Freunde.

## Setup

### Voraussetzungen

- PHP 8.1+
- MySQL / MariaDB
- Webserver (Apache/Nginx/XAMPP)

### 1. Datenbank anlegen

Erstelle eine Datenbank (z. B. `people_say_so`) und importiere das Schema sowie
die Beispiel-Fragen:

```sql
CREATE DATABASE people_say_so CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Zugangsdaten eintragen

Öffne `project/db.php` und trage deine MySQL-Zugangsdaten ein:

```php
define('DB_HOST', 'localhost');      // Datenbank-Host
define('DB_NAME', 'people_say_so'); // Datenbankname
define('DB_USER', 'dein_benutzer'); // MySQL-Benutzername  ← HIER ANPASSEN
define('DB_PASS', 'dein_passwort'); // MySQL-Passwort      ← HIER ANPASSEN
```

> **Achtung:** Ohne korrekte Zugangsdaten kann kein Spiel gestartet werden.
> Die App zeigt in diesem Fall einen roten Fehlerhinweis oben auf der Startseite.

### 3. Webserver konfigurieren

Zeige den Document Root auf das Verzeichnis `project/`.

Bei XAMPP: Lege den Projektordner in `htdocs/` ab und rufe
`http://localhost/People-Say-So/project/` auf.

## Spielmodi

| Modus | Beschreibung |
|-------|-------------|
| **Lokal** | Zwei Spieler teilen sich ein Gerät – Fragen werden direkt aus der DB geladen |
| **Online** | Zwei Spieler spielen über verschiedene Geräte mit einem 6-stelligen Spielcode |
