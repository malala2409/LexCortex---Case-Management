<?php
// AJAX-Endpoint: Phase abschließen + automatische Fristberechnung
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$phaseId  = (int)($_POST['phase_id']  ?? 0);
$caseId   = (int)($_POST['case_id']   ?? 0);
$phaseKey = trim($_POST['phase_key']  ?? '');
$date     = trim($_POST['date']       ?? '');

if (!$phaseId || !$caseId || !$date) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    exit;
}

try {
    $db = getDB();

    // Phase als erledigt markieren
    $stmt = $db->prepare("UPDATE phases SET status = 'done', phase_date = ?, completed_at = NOW() WHERE id = ?");
    $stmt->execute([$date, $phaseId]);

    // Nächste Phase aktivieren
    $next = $db->prepare("
        SELECT id FROM phases
        WHERE case_id = ? AND status = 'pending'
        ORDER BY id ASC LIMIT 1
    ");
    $next->execute([$caseId]);
    $nextPhase = $next->fetch();
    if ($nextPhase) {
        $db->prepare("UPDATE phases SET status = 'active' WHERE id = ?")->execute([$nextPhase['id']]);
    }

    // Automatische Fristberechnung bei Schlüsseldaten
    $autoMsg = null;

    if ($phaseKey === 'zustellung') {
        // Verteidigungsanzeige: Zustellung + 14 Tage
        $frist = (new DateTime($date))->modify('+14 days')->format('Y-m-d');
        $db->prepare("
            INSERT INTO deadlines (case_id, phase_id, title, deadline_date, frist_type, auto_calculated)
            VALUES (?, ?, 'Verteidigungsanzeige', ?, 'notfrist', 1)
            ON DUPLICATE KEY UPDATE deadline_date = VALUES(deadline_date)
        ")->execute([$caseId, $phaseId, $frist]);
        // Auch Phase-Datum der Verteidigungsanzeige setzen
        $db->prepare("UPDATE phases SET phase_date = ? WHERE case_id = ? AND phase_key = 'verteidigungsanzeige'")
           ->execute([$frist, $caseId]);
        $autoMsg = 'Verteidigungsanzeige fällig am ' . (new DateTime($frist))->format('d.m.Y');
    }

    if ($phaseKey === 'urteil') {
        // Berufungsfrist: Urteil + 30 Tage
        $frist = (new DateTime($date))->modify('+30 days')->format('Y-m-d');
        $db->prepare("
            INSERT INTO deadlines (case_id, phase_id, title, deadline_date, frist_type, auto_calculated)
            VALUES (?, ?, 'Berufungsfrist', ?, 'notfrist', 1)
        ")->execute([$caseId, $phaseId, $frist]);
        $autoMsg = 'Berufungsfrist läuft ab am ' . (new DateTime($frist))->format('d.m.Y');
    }

    echo json_encode([
        'success'  => true,
        'auto_msg' => $autoMsg,
        'next_id'  => $nextPhase['id'] ?? null,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
