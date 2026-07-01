<?php
// AJAX-Endpoint: Task bearbeiten (Beschreibung, Deadline, erledigt)
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$taskId       = (int)($_POST['task_id']       ?? 0);
$beschreibung = trim($_POST['beschreibung']   ?? '');
$deadline     = trim($_POST['deadline']       ?? '');
$erledigt     = isset($_POST['erledigt']) ? (int)$_POST['erledigt'] : null;

if (!$taskId) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Task-ID']);
    exit;
}

try {
    $db = getDB();

    // Task existiert?
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) {
        echo json_encode(['success' => false, 'error' => 'Task nicht gefunden']);
        exit;
    }

    // Nur übergebene Felder updaten
    $fields = [];
    $params = [];

    if ($beschreibung !== '') {
        $fields[] = 'beschreibung = ?';
        $params[] = $beschreibung;
    }
    if ($deadline !== '') {
        $fields[] = 'deadline = ?';
        $params[] = $deadline;
    }
    if ($erledigt !== null) {
        $fields[] = 'erledigt = ?';
        $params[] = $erledigt;
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'error' => 'Keine Felder zum Aktualisieren']);
        exit;
    }

    $params[] = $taskId;
    $db->prepare("UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

    // Aktualisierten Task zurückgeben
    $stmt = $db->prepare("
        SELECT t.*, c.klaeger, c.beklagter
        FROM tasks t
        JOIN cases c ON c.id = t.case_id
        WHERE t.id = ?
    ");
    $stmt->execute([$taskId]);
    $updated = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'task'    => $updated,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
