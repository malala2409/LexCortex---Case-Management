<?php
// AJAX-Endpoint: Phase zurücknehmen
// - Mit phase_id: Diese bestimmte Phase (und alle danach) zurücksetzen
// - Ohne phase_id: Nur die letzte erledigte Phase zurücksetzen
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$caseId  = (int)($_POST['case_id']  ?? 0);
$phaseId = (int)($_POST['phase_id'] ?? 0);

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Case-ID']);
    exit;
}

try {
    $db = getDB();

    if ($phaseId) {
        // Bestimmte Phase zurücksetzen
        $stmt = $db->prepare("SELECT id, status FROM phases WHERE id = ? AND case_id = ?");
        $stmt->execute([$phaseId, $caseId]);
        $target = $stmt->fetch();

        if (!$target || $target['status'] !== 'done') {
            echo json_encode(['success' => false, 'error' => 'Phase ist nicht erledigt']);
            exit;
        }
    } else {
        // Letzte erledigte Phase finden (für "↩ Zurück" auf active-Phase)
        $stmt = $db->prepare("
            SELECT id FROM phases
            WHERE case_id = ? AND status = 'done'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$caseId]);
        $target = $stmt->fetch();

        if (!$target) {
            echo json_encode(['success' => false, 'error' => 'Keine erledigte Phase zum Zurücknehmen']);
            exit;
        }
        $phaseId = $target['id'];
    }

    // Alle Phasen NACH der Ziel-Phase auf pending setzen
    $db->prepare("
        UPDATE phases SET status = 'pending', completed_at = NULL
        WHERE case_id = ? AND id > ?
    ")->execute([$caseId, $phaseId]);

    // Ziel-Phase wieder auf active setzen
    $db->prepare("UPDATE phases SET status = 'active', completed_at = NULL WHERE id = ?")
       ->execute([$phaseId]);

    echo json_encode(['success' => true, 'phase_id' => $phaseId]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
