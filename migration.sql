-- Migration: start_date für Tasks hinzufügen
-- In phpMyAdmin auf die Datenbank "lexcortex" → SQL → folgenden Code ausführen:

ALTER TABLE tasks ADD COLUMN start_date DATE DEFAULT NULL AFTER deadline;

-- Bestehende Tasks: start_date = deadline (als Fallback)
UPDATE tasks SET start_date = deadline WHERE start_date IS NULL;

-- Migration: phase_key-Spalte entfernen (title reicht als Identifikation)
ALTER TABLE phases DROP COLUMN phase_key;

-- Migration: phase_id in tasks und deadlines auf NOT NULL setzen
-- Achtung: erst alle NULL-Einträge bereinigen, dann Foreign Keys neu anlegen
-- Schritt 1: Tasks/Deadlines ohne Phase löschen (oder manuell Phase zuweisen)
DELETE FROM tasks WHERE phase_id IS NULL;
DELETE FROM deadlines WHERE phase_id IS NULL;

-- Schritt 2: Alte Foreign Keys droppen
ALTER TABLE tasks DROP FOREIGN KEY tasks_ibfk_2;
ALTER TABLE deadlines DROP FOREIGN KEY deadlines_ibfk_2;

-- Schritt 3: Spalten auf NOT NULL ändern
ALTER TABLE tasks MODIFY phase_id INT NOT NULL;
ALTER TABLE deadlines MODIFY phase_id INT NOT NULL;

-- Schritt 4: Neue Foreign Keys mit ON DELETE CASCADE
ALTER TABLE tasks ADD FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE;
ALTER TABLE deadlines ADD FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE;
