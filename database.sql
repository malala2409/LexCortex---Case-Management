-- ============================================================
-- LexCortex – Datenbankschema
-- Modul: Informatik 2 für Nebenfachstudierende
-- ============================================================

CREATE DATABASE IF NOT EXISTS lexcortex CHARACTER SET utf8 COLLATE utf8_general_ci;
USE lexcortex;

-- ------------------------------------------------------------
-- Tabelle: cases
-- ------------------------------------------------------------
CREATE TABLE cases (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  klaeger      VARCHAR(255) NOT NULL,
  beklagter    VARCHAR(255) NOT NULL,
  gericht      VARCHAR(255) NOT NULL,
  streitwert   DECIMAL(10,2) NOT NULL,
  aktenzeichen VARCHAR(100),
  status       VARCHAR(50) DEFAULT 'aktiv',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Tabelle: phases
-- ------------------------------------------------------------
CREATE TABLE phases (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  case_id      INT NOT NULL,
  title        VARCHAR(255) NOT NULL,
  phase_date   DATE,
  status       VARCHAR(50) DEFAULT 'pending',
  frist_type   VARCHAR(50),
  notes        TEXT,
  completed_at DATETIME,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Tabelle: deadlines
-- ------------------------------------------------------------
CREATE TABLE deadlines (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  case_id         INT NOT NULL,
  phase_id        INT,
  title           VARCHAR(255) NOT NULL,
  deadline_date   DATE NOT NULL,
  frist_type      VARCHAR(50),
  auto_calculated BOOLEAN DEFAULT FALSE,
  erledigt        BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (case_id)  REFERENCES cases(id)  ON DELETE CASCADE,
  FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Tabelle: tasks
-- ------------------------------------------------------------
CREATE TABLE tasks (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  case_id      INT NOT NULL,
  phase_id     INT,
  beschreibung VARCHAR(255) NOT NULL,
  deadline     DATE NOT NULL,
  start_date   DATE DEFAULT NULL,
  erledigt     BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (case_id)  REFERENCES cases(id)  ON DELETE CASCADE,
  FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL
);

-- ============================================================
-- Beispieldaten
-- ============================================================

-- Fall 1: Müller GmbH ./. Weber & Partner
INSERT INTO cases (klaeger, beklagter, gericht, streitwert, aktenzeichen, status)
VALUES ('Müller GmbH', 'Weber & Partner', 'LG Frankfurt', 18500.00, '2 O 441/24', 'aktiv');

INSERT INTO phases (case_id, title, phase_date, status, frist_type, completed_at) VALUES
(1, 'Vorgerichtliche Mahnung', '2026-03-05', 'done',    NULL,           '2026-03-05 10:00:00'),
(1, 'Klageerhebung',           '2026-04-28', 'done',    NULL,           '2026-04-28 10:00:00'),
(1, 'Zustellung',              '2026-06-07', 'done',    'schluessel',   '2026-06-07 10:00:00'),
(1, 'Verteidigungsanzeige',    '2026-06-21', 'active',  'notfrist',     NULL),
(1, 'Klageerwiderung',         NULL,         'pending', 'gerichtsfrist',NULL),
(1, 'Replik',                  NULL,         'pending', 'gerichtsfrist',NULL),
(1, 'Güteverhandlung',         NULL,         'pending', 'termin',       NULL),
(1, 'Mündliche Verhandlung',   NULL,         'pending', 'termin',       NULL),
(1, 'Urteil',                  NULL,         'pending', 'urteil',       NULL),
(1, 'Rechtskraft / Vollstreckung', NULL,     'pending', NULL,           NULL);

INSERT INTO deadlines (case_id, phase_id, title, deadline_date, frist_type, auto_calculated, erledigt) VALUES
(1, 4, 'Verteidigungsanzeige', '2026-06-21', 'notfrist', TRUE, FALSE);

INSERT INTO tasks (case_id, phase_id, beschreibung, deadline, start_date, erledigt) VALUES
(1, 4, 'Mandant über Fristversäumnis informieren', CURDATE(), CURDATE(), FALSE),
(1, 4, 'Versäumnisurteil prüfen', CURDATE(), CURDATE(), FALSE),
(1, 5, 'Klageerwiderung vorbereiten', '2026-07-10', '2026-07-01', FALSE);

-- Fall 2: Schmidt AG ./. Bauer KG
INSERT INTO cases (klaeger, beklagter, gericht, streitwert, aktenzeichen, status)
VALUES ('Schmidt AG', 'Bauer KG', 'AG München', 4200.00, '114 C 8892/24', 'aktiv');

INSERT INTO phases (case_id, title, phase_date, status, frist_type, completed_at) VALUES
(2, 'Vorgerichtliche Mahnung', '2026-01-10', 'done',    NULL,           '2026-01-10 10:00:00'),
(2, 'Klageerhebung',           '2026-02-14', 'done',    NULL,           '2026-02-14 10:00:00'),
(2, 'Zustellung',              '2026-03-03', 'done',    'schluessel',   '2026-03-03 10:00:00'),
(2, 'Verteidigungsanzeige',    '2026-03-15', 'done',    'notfrist',     '2026-03-15 10:00:00'),
(2, 'Klageerwiderung',         '2026-04-14', 'done',    'gerichtsfrist','2026-04-14 10:00:00'),
(2, 'Replik',                  '2026-07-08', 'active',  'gerichtsfrist',NULL),
(2, 'Güteverhandlung',         NULL,         'pending', 'termin',       NULL),
(2, 'Mündliche Verhandlung',   NULL,         'pending', 'termin',       NULL),
(2, 'Urteil',                  NULL,         'pending', 'urteil',       NULL),
(2, 'Rechtskraft / Vollstreckung', NULL,     'pending', NULL,           NULL);

INSERT INTO deadlines (case_id, phase_id, title, deadline_date, frist_type, auto_calculated, erledigt) VALUES
(2, 16, 'Replik einreichen', '2026-07-08', 'gerichtsfrist', FALSE, FALSE);

INSERT INTO tasks (case_id, phase_id, beschreibung, deadline, start_date, erledigt) VALUES
(2, 16, 'Replik ausarbeiten', CURDATE(), CURDATE(), FALSE),
(2, 16, 'Beweise sichten', '2026-07-01', '2026-06-25', TRUE);

-- Fall 3: Hoffmann ./. Logistics GmbH
INSERT INTO cases (klaeger, beklagter, gericht, streitwert, aktenzeichen, status)
VALUES ('Hoffmann', 'Logistics GmbH', 'LG Berlin', 31000.00, '7 O 118/24', 'aktiv');

INSERT INTO phases (case_id, title, phase_date, status, frist_type, completed_at) VALUES
(3, 'Vorgerichtliche Mahnung', '2025-10-15', 'done',    NULL,           '2025-10-15 10:00:00'),
(3, 'Klageerhebung',           '2025-11-20', 'done',    NULL,           '2025-11-20 10:00:00'),
(3, 'Zustellung',              '2025-12-10', 'done',    'schluessel',   '2025-12-10 10:00:00'),
(3, 'Verteidigungsanzeige',    '2025-12-22', 'done',    'notfrist',     '2025-12-22 10:00:00'),
(3, 'Klageerwiderung',         '2026-02-28', 'done',    'gerichtsfrist','2026-02-28 10:00:00'),
(3, 'Replik',                  '2026-04-04', 'done',    'gerichtsfrist','2026-04-04 10:00:00'),
(3, 'Güteverhandlung',         '2026-06-29', 'active',  'termin',       NULL),
(3, 'Mündliche Verhandlung',   '2026-07-16', 'pending', 'termin',       NULL),
(3, 'Urteil',                  NULL,         'pending', 'urteil',       NULL),
(3, 'Rechtskraft / Vollstreckung', NULL,     'pending', NULL,           NULL);

INSERT INTO deadlines (case_id, phase_id, title, deadline_date, frist_type, auto_calculated, erledigt) VALUES
(3, 27, 'Güteverhandlung', '2026-06-29', 'termin', FALSE, FALSE),
(3, 28, 'Mündliche Verhandlung', '2026-07-16', 'termin', FALSE, FALSE);

INSERT INTO tasks (case_id, phase_id, beschreibung, deadline, start_date, erledigt) VALUES
(3, 27, 'Verhandlungsunterlagen vorbereiten', CURDATE(), CURDATE(), FALSE),
(3, 27, 'Mandant auf Gütetermin vorbereiten', CURDATE(), CURDATE(), FALSE),
(3, 28, 'Plädoyer ausarbeiten', '2026-07-10', '2026-07-01', FALSE);
