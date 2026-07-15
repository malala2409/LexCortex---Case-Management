# LexCortex - Fristen- und Fallmanagement

LexCortex ist ein webbasiertes Tool zur Verwaltung von Gerichtsverfahren, Fristen und Aufgaben. Es wurde im Rahmen des Moduls "Informatik 2 fur Nebenfachstudierende" an der Universitat Erlangen-Nurnberg entwickelt.

## Was das Tool kann

Auf dem Dashboard bekommt man einen schnellen Uberblick: wie viele Falle gerade aktiv sind, welche Fristen diese Woche anstehen, was uberfallig ist. Jeder Fall durchlauft konfigurierbare Verfahrensphasen - von der vorgerichtlichen Mahnung uber Klageerhebung, Zustellung, Verhandlung bis hin zum Urteil. Die Timeline in der Falldetailansicht zeigt immer, wo man gerade steht.

Deadlines werden farblich markiert (Notfristen rot, Gerichtsfristen orange, Termine blau, Urteile lila). Tasks konnen direkt in der Timeline erledigt, bearbeitet oder neu angelegt werden. Der integrierte Monatskalender zeigt alle Deadlines und Aufgaben auf einen Blick; ein Klick auf einen Tag offnet die Tagesubersicht, und von dort springt man direkt zum entsprechenden Task im Fall.

## Technik

Hinten lauft PHP 8 mit PDO fur die Datenbankanbindung, vorne HTML, CSS und ein bisschen Vanilla JS mit jQuery 3.7. Als Datenbank reicht MySQL oder MariaDB. Das Ganze ist bewusst schlank gehalten - keine Frameworks, keine Build-Tools, kein Package Manager. jQuery und alles andere kommt per CDN.

## Projektstruktur

```
index.php              Dashboard + Kalender
case_detail.php        Fall-Detailansicht mit Timeline und Tasks
add_case.php           Neuen Fall anlegen
add_task.php           Neue Aufgabe anlegen
config/db.php          Datenbankverbindung (PDO)
ajax/                  AJAX-Endpunkte (create, update, delete, complete)
css/style.css          Stylesheet
js/app.js              Frontend-Logik (Kalender, Modals, AJAX)
database.sql           Datenbankschema + drei Beispielfalle
```

## Installation

Voraussetzung: MAMP (oder XAMPP) mit PHP 8+ und MySQL 5.7+.

1. Projekt in das htdocs-Verzeichnis von MAMP legen (oder verlinken):
   ```
   ln -s /pfad/zum/projekt /Applications/MAMP/htdocs/lexcortex
   ```

2. MAMP starten, dann in phpMyAdmin eine neue Datenbank namens "lexcortex" anlegen (UTF-8).

3. Die Datei database.sql in phpMyAdmin importieren. Sie legt alle Tabellen an und enthalt drei Demo-Falle zum Ausprobieren.

4. Im Browser offnen:
   ```
   http://localhost/lexcortex/
   ```

Die Datenbankverbindung in config/db.php erkennt automatisch, ob MAMP oder XAMPP lauft, und verwendet die passenden Zugangsdaten. Fur MAMP ist das root/root, fur XAMPP root mit leerem Passwort.

## Beispieldaten

Die drei enthaltenen Demo-Falle zeigen typische Konstellationen:

- Muller GmbH gegen Weber & Partner am LG Frankfurt (Streitwert 18.500 EUR) - das Verfahren ist mitten in der Verteidigungsanzeige
- Schmidt AG gegen Bauer KG am AG Munchen (Streitwert 4.200 EUR) - hier geht es gerade um die Replik
- Hoffmann gegen Logistics GmbH am LG Berlin (Streitwert 31.000 EUR) - der Gutetermin steht kurz bevor, die mundliche Verhandlung ist fur den 16.07.2026 angesetzt

## Lizenz

Dieses Projekt ist Teil des Bonusprojekts fur das Modul "Informatik 2 fur Nebenfachstudierende" an der FAU Erlangen-Nurnberg, Sommersemester 2026.
