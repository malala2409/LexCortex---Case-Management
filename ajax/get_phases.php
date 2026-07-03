<?php
// AJAX-Endpoint: Phasen eines Falls als JSON zurückgeben
// Wird vom Kalender-Task-Modal verwendet (cascading dropdown)
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$caseId = (int)($_GET['case_id'] ?? 0);
if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Case-ID']);
    exit;
}

try {
    $db = getDB();

    // Nur Phasen laden, die noch nicht abgeschlossen sind (pending + active)
    $stmt = $db->prepare("
        SELECT id, title, status
        FROM phases
        WHERE case_id = ? AND status IN ('pending', 'active')
        ORDER BY id ASC
    ");
    $stmt->execute([$caseId]);
    $phases = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'phases'  => $phases,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
