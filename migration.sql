-- Migration: start_date für Tasks hinzufügen
-- In phpMyAdmin auf die Datenbank "lexcortex" → SQL → folgenden Code ausführen:

ALTER TABLE tasks ADD COLUMN start_date DATE DEFAULT NULL AFTER deadline;

-- Bestehende Tasks: start_date = deadline (als Fallback)
UPDATE tasks SET start_date = deadline WHERE start_date IS NULL;
