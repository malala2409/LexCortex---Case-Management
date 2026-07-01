<?php
// AJAX-Endpoint: Task als erledigt markieren
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
if (!$taskId) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Task-ID']);
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("UPDATE tasks SET erledigt = 1 WHERE id = ?");
    $stmt->execute([$taskId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
