<?php
// AJAX-Endpoint: Task als erledigt markieren / wieder öffnen (Toggle)
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

    // Aktuellen Status abfragen
    $check = $db->prepare("SELECT erledigt FROM tasks WHERE id = ?");
    $check->execute([$taskId]);
    $row = $check->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Task nicht gefunden']);
        exit;
    }

    // Toggle: 1→0, 0→1
    $newStatus = $row['erledigt'] ? 0 : 1;
    $stmt = $db->prepare("UPDATE tasks SET erledigt = ? WHERE id = ?");
    $stmt->execute([$newStatus, $taskId]);

    echo json_encode(['success' => true, 'erledigt' => (bool)$newStatus]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
