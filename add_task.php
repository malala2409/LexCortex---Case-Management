<?php
require_once 'config/db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$caseId       = (int)($_POST['case_id']      ?? 0);
$phaseId      = (int)($_POST['phase_id']     ?? 0);
$beschreibung = trim($_POST['beschreibung']  ?? '');
$startDate    = trim($_POST['start_date']    ?? date('Y-m-d'));
$deadline     = trim($_POST['deadline']       ?? '');

if (!$caseId || !$deadline) {
    header('Location: case_detail.php?id=' . $caseId);
    exit;
}

if ($beschreibung === '') {
    $beschreibung = 'Neue Aufgabe';
}

$stmt = $db->prepare("
    INSERT INTO tasks (case_id, phase_id, beschreibung, start_date, deadline)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$caseId, $phaseId ?: null, $beschreibung, $startDate, $deadline]);

header('Location: case_detail.php?id=' . $caseId);
exit;
