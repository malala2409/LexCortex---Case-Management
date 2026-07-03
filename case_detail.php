<?php
require_once 'config/db.php';
$db = getDB();

// Case-ID aus URL lesen und validieren
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

// Fall laden
$stmt = $db->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$id]);
$case = $stmt->fetch();
if (!$case) { header('Location: index.php'); exit; }

// Alle Phasen des Falls
$phases = $db->prepare("SELECT * FROM phases WHERE case_id = ? ORDER BY id ASC");
$phases->execute([$id]);
$phases = $phases->fetchAll();

// Tasks des Falls (nach Phase gruppiert)
$tasks = $db->prepare("SELECT t.*, p.title AS phase_title FROM tasks t LEFT JOIN phases p ON p.id = t.phase_id WHERE t.case_id = ? ORDER BY t.deadline ASC");
$tasks->execute([$id]);
$tasks = $tasks->fetchAll();

$tasks_by_phase = [];
foreach ($tasks as $t) {
    $pid = $t['phase_id'] ?? 0;
    $tasks_by_phase[$pid][] = $t;
}

// Fortschritt berechnen
$total = count($phases);
$done  = count(array_filter($phases, fn($p) => $p['status'] === 'done'));
$pct   = $total > 0 ? round(($done / $total) * 100) : 0;

// Beschreibungen pro Phase
$phase_descriptions = [
    'Vorgerichtliche Mahnung' => 'Schriftliche Mahnung an den Schuldner wurde versandt. Die Zahlung blieb aus.',
    'Klageerhebung'           => 'Klageschrift wurde beim zuständigen Gericht eingereicht.',
    'Zustellung'              => 'Zustellung der Klageschrift an den Beklagten. Dieses Datum ist das Schlüsseldatum und löst automatisch die Notfrist für die Verteidigungsanzeige aus (+14 Tage).',
    'Verteidigungsanzeige'    => 'Der Beklagte muss innerhalb von 14 Tagen nach Zustellung seine Verteidigungsbereitschaft anzeigen (§ 276 ZPO). Bei Versäumnis kann Versäumnisurteil beantragt werden.',
    'Klageerwiderung'         => 'Das Gericht setzt dem Beklagten eine Frist zur Einreichung der Klageerwiderung.',
    'Replik'                  => 'Erwiderung des Klägers auf die Klageerwiderung des Beklagten (Replik). Gerichtliche Frist.',
    'Güteverhandlung'         => 'Gerichtlicher Güteversuch (§ 278 ZPO). Das Gericht lädt zu einem Gütetermin.',
    'Mündliche Verhandlung'   => 'Hauptverhandlungstermin vor Gericht. Beide Parteien tragen ihre Positionen mündlich vor.',
    'Urteil'                  => 'Verkündung des Urteils. Bei Zustellung des Urteils läuft automatisch die Berufungsfrist von 30 Tagen ab.',
    'Rechtskraft / Vollstreckung' => 'Das Urteil ist rechtskräftig. Vollstreckungsmaßnahmen können eingeleitet werden.',
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>LexCortex – <?= htmlspecialchars($case['klaeger']) ?> ./. <?= htmlspecialchars($case['beklagter']) ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ══ NAV ══════════════════════════════════════════════════ -->
<nav>
  <a class="nav-logo" href="index.php">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l3.5 6.5H18l-6 9-6-9h2.5L12 3z"/>
      <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    LexCortex
  </a>
  <div class="nav-tabs">
    <a class="nav-tab" href="index.php">Dashboard</a>
    <a class="nav-tab" href="index.php?tab=calendar">Kalender</a>
  </div>
  <div class="nav-avatar">LD</div>
</nav>

<!-- ══ CONTENT ═══════════════════════════════════════════════ -->
<div class="page active" id="page-detail">
  <div style="max-width:860px;margin:0 auto;">

    <!-- Header / Breadcrumb -->
    <div class="detail-header">
      <a href="index.php" class="btn btn-ghost btn-sm">← Zurück</a>
      <div class="breadcrumb">
        <a href="index.php">Dashboard</a>
        <span>/</span>
        <span><?= htmlspecialchars($case['klaeger']) ?> ./. <?= htmlspecialchars($case['beklagter']) ?></span>
      </div>
    </div>

    <!-- Fall-Metadaten -->
    <div class="meta-card">
      <div class="meta-row">
        <div class="meta-item">
          <label>Kläger</label>
          <value><?= htmlspecialchars($case['klaeger']) ?></value>
        </div>
        <div class="meta-item">
          <label>Beklagter</label>
          <value><?= htmlspecialchars($case['beklagter']) ?></value>
        </div>
        <div class="meta-item">
          <label>Streitwert</label>
          <value><?= number_format($case['streitwert'], 2, ',', '.') ?> €</value>
        </div>
        <div class="meta-item">
          <label>Gericht</label>
          <value><?= htmlspecialchars($case['gericht']) ?></value>
        </div>
      </div>
      <div class="meta-az">Aktenzeichen: <?= htmlspecialchars($case['aktenzeichen'] ?? '—') ?></div>
    </div>

    <!-- Fortschrittsbalken -->
    <div class="progress-wrap">
      <div class="progress-header">
        <span style="font-weight:600;">Fortschritt</span>
        <span style="color:var(--muted);"><?= $done ?> / <?= $total ?> Phasen abgeschlossen</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="timeline" style="margin-bottom:20px;">
      <div class="timeline-header">Verfahrens-Timeline</div>

      <?php foreach ($phases as $i => $ph): ?>
      <?php
        $desc = $phase_descriptions[$ph['title']] ?? '';
        $dotClass = match($ph['status']) {
            'done'   => 'done',
            'active' => 'active',
            default  => 'pending'
        };
        $titleClass = match($ph['status']) {
            'done'   => 'done',
            'active' => 'active',
            default  => ''
        };
        $badge = '';
        if ($ph['frist_type']) {
            $badgeMap = [
                'notfrist'      => ['badge-notfrist',      'Notfrist'],
                'gerichtsfrist' => ['badge-gerichtsfrist', 'Gerichtsfrist'],
                'termin'        => ['badge-termin',        'Termin'],
                'schluessel'    => ['badge-schluessel',    'Schlüsseldatum'],
                'urteil'        => ['badge-urteil',        'Urteil'],
            ];
            if (isset($badgeMap[$ph['frist_type']])) {
                [$bc, $bl] = $badgeMap[$ph['frist_type']];
                $badge = "<span class='badge {$bc}'>{$bl}</span>";
            }
        }
        if ($ph['status'] === 'done')   $badge .= "<span class='badge badge-done'>Erledigt</span>";
        if ($ph['status'] === 'active') $badge .= "<span class='badge badge-active'>Aktiv</span>";
      ?>
      <div class="phase-item" id="phase-<?= $ph['id'] ?>">
        <div class="phase-trigger" onclick="togglePhase('phase-<?= $ph['id'] ?>')">
          <div class="phase-dot <?= $dotClass ?>">
            <?= $ph['status'] === 'done' ? '✓' : ($i + 1) ?>
          </div>
          <div class="phase-date" id="phase-date-display-<?= $ph['id'] ?>"
               onclick="event.stopPropagation(); editPhaseDate(<?= $ph['id'] ?>)"
               title="Klicken zum Bearbeiten"
               style="cursor:pointer;text-decoration:underline dotted">
            <?= $ph['phase_date'] ? (new DateTime($ph['phase_date']))->format('d.m.Y') : '—' ?>
          </div>
          <input type="date" class="phase-date-edit" id="phase-date-edit-<?= $ph['id'] ?>"
                 value="<?= $ph['phase_date'] ?? '' ?>"
                 onchange="savePhaseDate(<?= $ph['id'] ?>)"
                 onclick="event.stopPropagation()"
                 style="display:none">
          <div class="phase-title <?= $titleClass ?>"><?= htmlspecialchars($ph['title']) ?></div>
          <div class="phase-badges"><?= $badge ?></div>
          <?php if ($ph['status'] === 'done'): ?>
          <button class="btn-icon-revert"
                  onclick="event.stopPropagation(); revertPhase(<?= $id ?>, <?= $ph['id'] ?>)"
                  title="Diese Phase zurücknehmen">↩</button>
          <?php endif; ?>
          <div class="phase-chevron">▾</div>
        </div>
        <div class="phase-panel">
          <?php if ($desc): ?>
          <div class="phase-panel-desc"><?= htmlspecialchars($desc) ?></div>
          <?php endif; ?>

          <?php if ($ph['status'] === 'active'): ?>
          <!-- Phase abschließen -->
          <div class="phase-complete-form">
            <label>Datum:</label>
            <input type="date" id="date-<?= $ph['id'] ?>"
                   value="<?= $ph['phase_date'] ?? date('Y-m-d') ?>">
            <button class="btn btn-green btn-sm"
                    onclick="completePhase(<?= $ph['id'] ?>, <?= $id ?>, '<?= htmlspecialchars($ph['title']) ?>')">
              ✓ Phase abschließen
            </button>
            <button class="btn btn-ghost btn-sm"
                    onclick="revertPhase(<?= $id ?>)"
                    title="Letzte abgeschlossene Phase zurücknehmen">
              ↩ Zurück
            </button>
          </div>
          <?php endif; ?>
          <div class="phase-complete-feedback" id="feedback-<?= $ph['id'] ?>">Phase abgeschlossen!</div>

          <?php if ($ph['notes']): ?>
          <div style="margin-top:10px;font-size:12.5px;color:var(--muted);"><?= htmlspecialchars($ph['notes']) ?></div>
          <?php endif; ?>

          <!-- Tasks dieser Phase -->
          <?php $phaseTasks = $tasks_by_phase[$ph['id']] ?? []; ?>
          <?php if (!empty($phaseTasks)): ?>
          <div class="phase-tasks">
            <?php foreach ($phaseTasks as $t): ?>
            <div class="phase-task-item <?= $t['erledigt'] ? 'task-done' : '' ?>" id="task-<?= $t['id'] ?>">
              <div class="task-checkbox <?= $t['erledigt'] ? 'done' : '' ?>"
                   onclick="completeTask(<?= $t['id'] ?>, this)"
                   title="Als erledigt markieren"><?= $t['erledigt'] ? '✓' : '' ?></div>
              <div class="phase-task-info">
                <span class="phase-task-desc"><?= htmlspecialchars($t['beschreibung']) ?></span>
                <span class="phase-task-deadline">📅 <?= (new DateTime($t['deadline']))->format('d.m.Y') ?></span>
              </div>
              <div class="phase-task-actions">
                <button class="btn-icon" onclick="editTask(<?= $t['id'] ?>)" title="Bearbeiten">✎</button>
                <button class="btn-icon btn-icon-save" style="display:none" onclick="saveTask(<?= $t['id'] ?>)" title="Speichern">✓</button>
                <button class="btn-icon btn-icon-cancel" style="display:none" onclick="cancelEditTask(<?= $t['id'] ?>)" title="Abbrechen">✕</button>
                <button class="btn-icon btn-icon-delete" onclick="deleteTask(<?= $t['id'] ?>)" title="Löschen">🗑</button>
              </div>
              <div class="phase-task-edit" style="display:none">
                <input type="text" class="task-edit-desc" value="<?= htmlspecialchars($t['beschreibung']) ?>">
                <input type="date" class="task-edit-deadline" value="<?= $t['deadline'] ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Inline Task-Form für diese Phase -->
          <div class="phase-add-task">
            <button class="btn-add-task-inline"
                    onclick="toggleInlineTaskForm(<?= $ph['id'] ?>)">+ Aufgabe</button>
            <div class="phase-add-task-form" id="inline-form-<?= $ph['id'] ?>" style="display:none">
              <input type="text" id="inline-desc-<?= $ph['id'] ?>" placeholder="Beschreibung (optional)">
              <input type="date" id="inline-start-<?= $ph['id'] ?>" value="<?= date('Y-m-d') ?>">
              <input type="date" id="inline-deadline-<?= $ph['id'] ?>">
              <button class="btn btn-primary btn-sm"
                      onclick="addInlineTask(<?= $ph['id'] ?>, <?= $id ?>)">Speichern</button>
              <button class="btn btn-ghost btn-sm"
                      onclick="toggleInlineTaskForm(<?= $ph['id'] ?>)">✕</button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div><!-- /timeline -->

    <!-- Task hinzufügen -->
    <div class="tasks-section" style="margin-top:24px;">
      <div class="card-header">
        <span class="card-title">Neue Aufgabe</span>
        <span style="font-size:11.5px;color:var(--muted);">
          <?= count(array_filter($tasks, fn($t) => !$t['erledigt'])) ?> offen
        </span>
      </div>
      <form class="add-task-form" id="add-task-form" method="POST" action="add_task.php"
            onsubmit="return validateTask()">
        <input type="hidden" name="case_id" value="<?= $id ?>">
        <div class="form-row" style="align-items:end;">
          <div class="form-group">
            <label>Beschreibung</label>
            <input type="text" name="beschreibung" id="task-desc" placeholder="z.B. Akte prüfen" style="width:220px;">
            <div class="form-err" id="err-task-desc"></div>
          </div>
          <div class="form-group">
            <label>Phase</label>
            <select name="phase_id" style="width:180px;">
              <option value="">— Ohne Phase —</option>
              <?php foreach ($phases as $ph): ?>
              <option value="<?= $ph['id'] ?>" <?= $ph['status'] === 'active' ? 'selected' : '' ?>>
                <?= htmlspecialchars($ph['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Deadline *</label>
            <input type="date" name="deadline" id="task-deadline">
            <div class="form-err" id="err-task-deadline"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-bottom:4px;">+ Hinzufügen</button>
        </div>
      </form>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
