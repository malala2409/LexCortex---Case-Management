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
    ['mahnung',              'Vorgerichtliche Mahnung',    null,            null],
    ['klageerhebung',        'Klageerhebung',              null,            null],
    ['zustellung',           'Zustellung',                 $zustellungDatum,'schluessel'],
    ['verteidigungsanzeige', 'Verteidigungsanzeige',       null,            'notfrist'],
    ['klageerwiderung',      'Klageerwiderung',            null,            'gerichtsfrist'],
    ['replik',               'Replik',                     null,            'gerichtsfrist'],
    ['gueteverhandlung',     'Güteverhandlung',            null,            'termin'],
    ['muendliche_verh',      'Mündliche Verhandlung',      null,            'termin'],
    ['urteil',               'Urteil',                     null,            'urteil'],
    ['rechtskraft',          'Rechtskraft / Vollstreckung',null,            null],
];

$phaseStmt = $db->prepare("INSERT INTO phases (case_id, phase_key, title, phase_date, frist_type) VALUES (?, ?, ?, ?, ?)");
foreach ($phasen as [$key, $title, $date, $fristType]) {
    $phaseStmt->execute([$caseId, $key, $title, $date, $fristType]);
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
