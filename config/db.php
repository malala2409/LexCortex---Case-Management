<?php
// Datenbankverbindung über PDO
// Unterstützt MAMP (Mac) und XAMPP (Mac/Windows) automatisch
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Reihenfolge: erst MAMP, dann XAMPP probieren
        $dsnList = [
            // MAMP (Mac): Unix-Socket + Passwort "root"
            'MAMP' => [
                'mysql:host=localhost;dbname=lexcortex;charset=utf8;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock',
                'root', 'root'
            ],
            // XAMPP (Mac/Windows): TCP-Verbindung, kein Passwort
            'XAMPP' => [
                'mysql:host=localhost;port=3306;dbname=lexcortex;charset=utf8',
                'root', ''
            ],
        ];

        $lastError = '';
        foreach ($dsnList as $name => [$dsn, $user, $pass]) {
            try {
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return $pdo;  // Verbindung erfolgreich → sofort zurück
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
            }
        }

        // Keine Verbindung möglich
        die(
            '<h2>Datenbank-Verbindungsfehler</h2>' .
            '<p><b>MySQL sagt:</b> ' . htmlspecialchars($lastError) . '</p>' .
            '<hr>' .
            '<p><b>Häufige Ursachen:</b></p>' .
            '<ol>' .
            '<li>MySQL läuft nicht → MAMP/XAMPP öffnen und <b>Start</b> klicken</li>' .
            '<li>Datenbank "lexcortex" existiert noch nicht → phpMyAdmin öffnen und database.sql importieren</li>' .
            '<li>Falls XAMPP: MySQL-Passwort prüfen (Standard: leer, also nichts eintragen)</li>' .
            '</ol>'
        );
    }
    return $pdo;
}
