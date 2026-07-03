# LexCortex – Case Management

Ein webbasiertes Fristen- und Fallmanagement-System für Anwälte und Kanzleien. Behält den Überblick über aktive Fälle, Verfahrensphasen, Deadlines und Aufgaben – mit einem integrierten Kalender.

## Features

- **Dashboard** – Metriken (aktive Fälle, Fristen, überfällige Tasks) auf einen Blick
- **Fallverwaltung** – Kläger, Beklagter, Gericht, Aktenzeichen, Streitwert
- **Phasen-Timeline** – Jeder Fall durchläuft konfigurierbare Verfahrensphasen (Mahnung → Klage → Zustellung → … → Urteil)
- **Fristen & Deadlines** – Notfristen, Gerichtsfristen, Termine mit farblicher Kennzeichnung
- **Aufgaben (Tasks)** – Aufgaben pro Phase mit Deadline, inkl. Inline-Bearbeitung
- **Kalender** – Monatskalender mit allen Deadlines und Tasks, Tagesübersicht per Klick
- **Kalender → Task-Navigation** – Aus dem Kalender direkt zum Task springen, Phase klappt automatisch auf und Task wird hervorgehoben

## Technologie-Stack

| Bereich | Technologie |
|---------|-------------|
| Backend | PHP 8 (prozedural, PDO) |
| Datenbank | MySQL / MariaDB |
| Frontend | HTML, CSS, Vanilla JS + jQuery 3.7 |
| Lokaler Server | MAMP |
| Package Manager | Keine (alles über CDN / Bordmittel) |

## Projektstruktur

```
├── index.php              # Dashboard + Kalender
├── case_detail.php        # Fall-Detailansicht mit Timeline & Tasks
├── add_case.php           # Neuen Fall anlegen
├── add_task.php           # Neue Aufgabe anlegen
├── config/
│   └── db.php             # Datenbankverbindung (PDO)
├── ajax/
│   ├── create_task.php    # Task per AJAX erstellen
│   ├── update_task.php    # Task bearbeiten
│   ├── delete_task.php    # Task löschen
│   ├── complete_task.php  # Task als erledigt markieren
│   ├── complete_phase.php # Phase abschließen
│   ├── revert_phase.php   # Phase zurücknehmen
│   ├── update_phase.php   # Phasen-Datum bearbeiten
│   └── get_phases.php     # Phasen eines Falls abrufen
├── css/
│   └── style.css          # Komplettes Stylesheet
├── js/
│   └── app.js             # Frontend-Logik (Kalender, Modals, AJAX)
├── database.sql           # Datenbankschema + Beispieldaten
└── migration.sql          # Zusätzliche DB-Migrationen
```

## Schnellstart

### Voraussetzungen

- [MAMP](https://www.mamp.info/) (oder ein anderer Apache + MySQL Stack)
- PHP 8+
- MySQL 5.7+

### Installation

1. **Projekt klonen oder in MAMP verlinken:**

```bash
git clone git@github.com:malala2409/LexCortex---Case-Management.git
ln -s /pfad/zum/projekt /Applications/MAMP/htdocs/lexcortex
```

2. **Datenbank einrichten:**

   - MAMP starten
   - [phpMyAdmin](http://localhost/phpmyadmin/) öffnen
   - Neue DB `litigationdesk` anlegen (UTF-8)
   - `database.sql` importieren (enthält Schema + 3 Beispieldaten-Fälle)

3. **Im Browser öffnen:**

```
http://localhost/lexcortex/
```

### MAMP-Konfiguration

Das Projekt ist vorkonfiguriert für MAMP mit Standard-Zugangsdaten:

- **Host:** `localhost`
- **User:** `root`
- **Passwort:** `root`
- **Socket:** `/Applications/MAMP/tmp/mysql/mysql.sock`

## Beispieldaten

Die Datenbank enthält drei Demo-Fälle:

| Fall | Parteien | Gericht | Streitwert |
|------|----------|---------|------------|
| 1 | Müller GmbH ./. Weber & Partner | LG Frankfurt | 18.500 € |
| 2 | Schmidt AG ./. Bauer KG | AG München | 4.200 € |
| 3 | Hoffmann ./. Logistics GmbH | LG Berlin | 31.000 € |

## Lizenz

Projekt im Rahmen des Moduls „Informatik 2 für Nebenfachstudierende" – Universität Erlangen-Nürnberg.
