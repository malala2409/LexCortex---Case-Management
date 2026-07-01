<?php
// Datenbankverbindung über PDO (MAMP: localhost, root, Passwort "root")
// Falls die Verbindung fehlschlägt, bekommst du eine klare Fehlermeldung
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=lexcortex;charset=utf8;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock',
                'root',
                'root'
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Zeige eine verständliche Fehlermeldung
            die(
                '<h2>Datenbank-Verbindungsfehler</h2>' .
                '<p><b>MySQL sagt:</b> ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '<hr>' .
                '<p><b>Häufige Ursachen:</b></p>' .
                '<ol>' .
                '<li>MySQL in MAMP läuft nicht → MAMP öffnen und <b>Start</b> klicken</li>' .
                '<li>Datenbank "lexcortex" existiert noch nicht → <a href="http://localhost:8888/phpmyadmin/">phpMyAdmin</a> öffnen und database.sql importieren</li>' .
                '<li>MAMP-Passwort anders → MAMP öffnen, auf "MySQL" Tab klicken, Passwort prüfen</li>' .
                '</ol>'
            );
        }
    }
    return $pdo;
}
