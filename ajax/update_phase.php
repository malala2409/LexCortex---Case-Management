<?php
// AJAX-Endpoint: Phase-Datum (und optional Status) aktualisieren
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$phaseId = (int)($_POST['phase_id'] ?? 0);
$date    = trim($_POST['date']    ?? '');

if (!$phaseId || !$date) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    exit;
}

try {
    $db = getDB();
    $db->prepare("UPDATE phases SET phase_date = ? WHERE id = ?")
       ->execute([$date, $phaseId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
