<?php
// AJAX-Endpoint: Task löschen
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
    $db = getDB();

    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Task nicht gefunden']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
