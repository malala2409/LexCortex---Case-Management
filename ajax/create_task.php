<?php
// AJAX-Endpoint: Task aus dem Kalender heraus erstellen
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$caseId       = (int)($_POST['case_id']       ?? 0);
$phaseId      = (int)($_POST['phase_id']      ?? 0);
$beschreibung = trim($_POST['beschreibung']   ?? '');
$startDate    = trim($_POST['start_date']     ?? date('Y-m-d'));
$deadline     = trim($_POST['deadline']       ?? '');

// ── Validierung ──────────────────────────────────────────────
$errors = [];
if (!$caseId)                            $errors[] = 'Kein Fall ausgewählt';
if (!$phaseId)                           $errors[] = 'Keine Phase ausgewählt';
if ($deadline === '')                   $errors[] = 'Deadline fehlt';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode('; ', $errors)]);
    exit;
}

// ── Existenz von case + phase prüfen ─────────────────────────
try {
    $db = getDB();

    $caseExists = $db->prepare("SELECT 1 FROM cases WHERE id = ?");
    $caseExists->execute([$caseId]);
    if (!$caseExists->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Fall existiert nicht']);
        exit;
    }

    $phaseExists = $db->prepare("SELECT 1 FROM phases WHERE id = ? AND case_id = ?");
    $phaseExists->execute([$phaseId, $caseId]);
    if (!$phaseExists->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Phase gehört nicht zu diesem Fall']);
        exit;
    }

    // Beschreibung ist optional → Fallback auf Case-Namen
    if ($beschreibung === '') {
        $caseInfo = $db->prepare("SELECT CONCAT(klaeger, ' ./. ', beklagter) AS name FROM cases WHERE id = ?");
        $caseInfo->execute([$caseId]);
        $row = $caseInfo->fetch();
        $beschreibung = $row ? $row['name'] : 'Neue Aufgabe';
    }

    // ── Task einfügen ─────────────────────────────────────────
    $stmt = $db->prepare("
        INSERT INTO tasks (case_id, phase_id, beschreibung, start_date, deadline)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$caseId, $phaseId, $beschreibung, $startDate, $deadline]);
    $newTaskId = (int)$db->lastInsertId();

    // ── Fall-Infos für die Dashboard-Aktualisierung zurückgeben ──
    $caseInfo = $db->prepare("SELECT klaeger, beklagter FROM cases WHERE id = ?");
    $caseInfo->execute([$caseId]);
    $case = $caseInfo->fetch();

    echo json_encode([
        'success'    => true,
        'task_id'    => $newTaskId,
        'task'       => [
            'id'           => $newTaskId,
            'case_id'      => $caseId,
            'phase_id'     => $phaseId,
            'beschreibung' => $beschreibung,
            'start_date'   => $startDate,
            'deadline'     => $deadline,
            'erledigt'     => false,
            'klaeger'      => $case['klaeger'],
            'beklagter'    => $case['beklagter'],
        ],
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
