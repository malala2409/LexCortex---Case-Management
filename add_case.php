<?php
require_once 'config/db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Eingaben lesen
$klaeger        = trim($_POST['klaeger']        ?? '');
$beklagter      = trim($_POST['beklagter']      ?? '');
$gericht        = trim($_POST['gericht']        ?? '');
$streitwert     = (float)($_POST['streitwert']  ?? 0);
$aktenzeichen   = trim($_POST['aktenzeichen']   ?? '') ?: null;
$zustellungDatum = trim($_POST['zustellung_datum'] ?? '') ?: null;

// Pflichtfelder prüfen (serverseitige Absicherung)
if (!$klaeger || !$beklagter || !$gericht || $streitwert <= 0) {
    header('Location: index.php');
    exit;
}

// Fall anlegen
$stmt = $db->prepare("INSERT INTO cases (klaeger, beklagter, gericht, streitwert, aktenzeichen) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$klaeger, $beklagter, $gericht, $streitwert, $aktenzeichen]);
$caseId = (int)$db->lastInsertId();

// Alle 10 Phasen der Zahlungsklage anlegen
$phasen = [
    ['Vorgerichtliche Mahnung',    null,            null],
    ['Klageerhebung',              null,            null],
    ['Zustellung',                 $zustellungDatum,'schluessel'],
    ['Verteidigungsanzeige',       null,            'notfrist'],
    ['Klageerwiderung',            null,            'gerichtsfrist'],
    ['Replik',                     null,            'gerichtsfrist'],
    ['Güteverhandlung',            null,            'termin'],
    ['Mündliche Verhandlung',      null,            'termin'],
    ['Urteil',                     null,            'urteil'],
    ['Rechtskraft / Vollstreckung',null,            null],
];

$phaseStmt = $db->prepare("INSERT INTO phases (case_id, title, phase_date, frist_type, status) VALUES (?, ?, ?, ?, ?)");
$isFirst = true;
foreach ($phasen as [$title, $date, $fristType]) {
    $status = $isFirst ? 'active' : 'pending';
    $phaseStmt->execute([$caseId, $title, $date, $fristType, $status]);
    $isFirst = false;
}

// Automatische Fristberechnung: Zustellungsdatum → Verteidigungsanzeige +14 Tage
if ($zustellungDatum) {
    $fristDatum = (new DateTime($zustellungDatum))->modify('+14 days')->format('Y-m-d');
    $dlStmt = $db->prepare("INSERT INTO deadlines (case_id, title, deadline_date, frist_type, auto_calculated) VALUES (?, ?, ?, ?, ?)");
    $dlStmt->execute([$caseId, 'Verteidigungsanzeige', $fristDatum, 'notfrist', true]);
}

// Weiterleitung zum neuen Fall
header('Location: case_detail.php?id=' . $caseId);
exit;
