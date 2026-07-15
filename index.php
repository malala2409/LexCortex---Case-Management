<?php
require_once 'config/db.php';
$db = getDB();

// ── Metriken ──────────────────────────────────────────────
$aktive      = $db->query("SELECT COUNT(*) FROM cases WHERE status = 'aktiv'")->fetchColumn();
$abgeschl    = $db->query("SELECT COUNT(*) FROM cases WHERE status = 'abgeschlossen'")->fetchColumn();
$ueberfaellig = $db->query("SELECT COUNT(*) FROM deadlines WHERE deadline_date < CURDATE() AND erledigt = 0")->fetchColumn();
$diese_woche  = $db->query("SELECT COUNT(*) FROM deadlines WHERE deadline_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND erledigt = 0")->fetchColumn();

// ── Alle aktiven Cases mit aktiver Phase ──────────────────
$cases = $db->query("
    SELECT c.*,
           p.title      AS phase_title,
           p.frist_type AS phase_frist,
           p.status     AS phase_status,
           p.phase_date AS phase_date
    FROM cases c
    LEFT JOIN phases p ON p.case_id = c.id AND p.status = 'active'
    WHERE c.status = 'aktiv'
    ORDER BY c.created_at DESC
")->fetchAll();

// ── Heutige Tasks (ab start_date bis Erledigung) ──────────
$today_tasks = $db->query("
    SELECT t.*, c.klaeger, c.beklagter,
           DATEDIFF(t.deadline, CURDATE()) AS days_until_deadline
    FROM tasks t
    JOIN cases c ON c.id = t.case_id
    WHERE t.deadline <= CURDATE() AND t.erledigt = 0
    ORDER BY t.deadline ASC
    LIMIT 6
")->fetchAll();

// ── Deadlines + Tasks für Kalender (als JSON für JS) ──────────
$deadlines_raw = $db->query("
    SELECT d.deadline_date, d.title, d.frist_type, d.case_id, c.klaeger, c.beklagter
    FROM deadlines d
    JOIN cases c ON c.id = d.case_id
    WHERE d.erledigt = 0
")->fetchAll();

$tasks_raw = $db->query("
    SELECT t.deadline as deadline_date, t.beschreibung as title, t.case_id,
           c.klaeger, c.beklagter, t.erledigt, t.id as task_id,
           t.start_date,
           DATEDIFF(t.deadline, CURDATE()) AS days_left
    FROM tasks t
    JOIN cases c ON c.id = t.case_id
    WHERE t.erledigt = 0
")->fetchAll();

$cal_events = [];
foreach ($deadlines_raw as $dl) {
    $color = match($dl['frist_type']) {
        'notfrist'      => 'red',
        'gerichtsfrist' => 'orange',
        'termin'        => 'blue',
        'urteil'        => 'purple',
        default         => 'blue'
    };
    $cal_events[$dl['deadline_date']][] = [
        'type'    => 'deadline',
        'label'   => $dl['title'],
        'color'   => $color,
        'case'    => $dl['klaeger'] . ' ./. ' . $dl['beklagter'],
        'case_id' => (int)$dl['case_id']
    ];
}
foreach ($tasks_raw as $t) {
    $daysLeft = (int)$t['days_left'];
    if ($daysLeft <= 3 && $daysLeft >= 0) {
        $taskColor = 'task-warn';        // Deadline in 0-3 Tagen → Warnfarbe
    } elseif ($daysLeft < 0) {
        $taskColor = 'task-overdue';     // überfällig → rot
    } else {
        $taskColor = 'task';
    }
    $cal_events[$t['deadline_date']][] = [
        'type'       => 'task',
        'label'      => $t['title'],
        'color'      => $taskColor,
        'case'       => $t['klaeger'] . ' ./. ' . $t['beklagter'],
        'case_id'    => (int)$t['case_id'],
        'task_id'    => (int)$t['task_id'],
        'days_left'  => $daysLeft,
    ];
}

// ── Cases für JS (Kalender-Task-Modal) ─────────────────────
$cases_for_js = array_map(function($c) {
    return [
        'id'        => (int)$c['id'],
        'klaeger'   => $c['klaeger'],
        'beklagter' => $c['beklagter'],
    ];
}, $cases);

// ── Hilfsfunktion: Urgency-Dot ────────────────────────────
function urgencyDot(string $phaseDate = null, string $phaseStatus = null): string {
    if ($phaseStatus === 'active' && $phaseDate) {
        $diff = (new DateTime($phaseDate))->diff(new DateTime())->days;
        if ((new DateTime($phaseDate)) < new DateTime()) return 'red';
        if ($diff <= 7) return 'orange';
    }
    return 'green';
}

// ── Hilfsfunktion: Frist-Badge ────────────────────────────
function fristBadge(?string $fristType, string $phaseStatus): string {
    if ($phaseStatus === 'active' && $fristType) {
        $map = [
            'notfrist'      => ['badge-notfrist',      'Notfrist'],
            'gerichtsfrist' => ['badge-gerichtsfrist',  'Gerichtsfrist'],
            'termin'        => ['badge-termin',         'Termin'],
            'schluessel'    => ['badge-schluessel',     'Schlüsseldatum'],
            'urteil'        => ['badge-urteil',         'Urteil'],
        ];
        [$cls, $label] = $map[$fristType] ?? ['badge-active', $fristType];
        return "<span class='badge {$cls}'>{$label}</span>";
    }
    return "<span class='badge badge-pending'>—</span>";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>LexCortex</title>
  <link rel="stylesheet" href="css/style.css?v=2">
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
    <button class="nav-tab active" id="tab-dashboard" onclick="showTab('dashboard')">Dashboard</button>
    <button class="nav-tab"        id="tab-calendar"  onclick="showTab('calendar')">Kalender</button>
  </div>
  <div class="nav-avatar">LD</div>
</nav>

<!-- ══ DASHBOARD ═════════════════════════════════════════════ -->
<div class="page active" id="page-dashboard">

  <!-- Metriken -->
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-label">Aktive Fälle</div>
      <div class="metric-value"><?= $aktive ?></div>
      <div class="metric-sub">laufende Verfahren</div>
    </div>
    <div class="metric-card c-orange">
      <div class="metric-label">Fristen diese Woche</div>
      <div class="metric-value"><?= $diese_woche ?></div>
      <div class="metric-sub">nächste 7 Tage</div>
    </div>
    <div class="metric-card c-red">
      <div class="metric-label">Überfällig</div>
      <div class="metric-value"><?= $ueberfaellig ?></div>
      <div class="metric-sub">offene Fristen</div>
    </div>
    <div class="metric-card c-green">
      <div class="metric-label">Abgeschlossen</div>
      <div class="metric-value"><?= $abgeschl ?></div>
      <div class="metric-sub">in diesem Quartal</div>
    </div>
  </div>

  <!-- 2-Spalten Grid -->
  <div class="main-grid">

    <!-- Cases Tabelle -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Aktive Fälle</span>
        <div style="display:flex;gap:8px;align-items:center;">
          <select class="filter" id="phase-filter">
            <option value="">Alle Phasen</option>
            <option value="Vorgerichtliche Mahnung">Mahnung</option>
            <option value="Klageerhebung">Klageerhebung</option>
            <option value="Zustellung">Zustellung</option>
            <option value="Verteidigungsanzeige">Verteidigungsanzeige</option>
            <option value="Klageerwiderung">Klageerwiderung</option>
            <option value="Replik">Replik</option>
            <option value="Güteverhandlung">Güteverhandlung</option>
            <option value="Mündliche Verhandlung">Mündl. Verhandlung</option>
          </select>
          <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-overlay').classList.add('open')">+ Neuer Fall</button>
        </div>
      </div>
      <table class="cases-table">
        <thead>
          <tr>
            <th style="width:20px;"></th>
            <th>Fall</th>
            <th>Gericht / AZ</th>
            <th>Aktive Phase</th>
            <th>Streitwert</th>
          </tr>
        </thead>
        <tbody id="cases-tbody">
          <?php foreach ($cases as $c): ?>
          <?php
            $dot   = urgencyDot($c['phase_date'], $c['phase_status']);
            $badge = fristBadge($c['phase_frist'], $c['phase_status'] ?? '');
          ?>
          <tr data-phase="<?= htmlspecialchars($c['phase_title'] ?? '') ?>"
              onclick="window.location='case_detail.php?id=<?= $c['id'] ?>'">
            <td><span class="dot <?= $dot ?>"></span></td>
            <td>
              <div class="case-name"><?= htmlspecialchars($c['klaeger']) ?> ./. <?= htmlspecialchars($c['beklagter']) ?></div>
              <div class="case-meta"><?= htmlspecialchars($c['gericht']) ?></div>
            </td>
            <td>
              <div style="font-size:13px;"><?= htmlspecialchars($c['gericht']) ?></div>
              <div class="case-meta"><?= htmlspecialchars($c['aktenzeichen'] ?? '—') ?></div>
            </td>
            <td>
              <?= $badge ?>
              <?php if ($c['phase_title']): ?>
              <span style="font-size:12px;color:var(--muted);margin-left:4px;"><?= htmlspecialchars($c['phase_title']) ?></span>
              <?php endif; ?>
            </td>
            <td><span class="amount"><?= number_format($c['streitwert'], 2, ',', '.') ?> €</span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($cases)): ?>
          <tr><td colspan="5" class="empty">Keine aktiven Fälle</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Heutige Tasks -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Heutige Tasks</span>
        <span style="font-size:11.5px;color:var(--muted);">heute + überfällig</span>
      </div>
      <div class="task-list">
        <?php foreach ($today_tasks as $t):
          $daysLeft = (int)$t['days_until_deadline'];
          if ($daysLeft < 0)      $warnClass = 'task-overdue';
          elseif ($daysLeft <= 3) $warnClass = 'task-warn';
          else                    $warnClass = '';
        ?>
        <div class="task-item <?= $warnClass ?>" id="task-<?= $t['id'] ?>">
          <div class="task-checkbox <?= $t['erledigt'] ? 'done' : '' ?>"
               onclick="completeTask(<?= $t['id'] ?>, this)"
               title="Als erledigt markieren">
            <?= $t['erledigt'] ? '✓' : '' ?>
          </div>
          <div class="task-info">
            <div class="task-desc <?= $t['erledigt'] ? 'done' : '' ?>">
              <?= htmlspecialchars($t['beschreibung']) ?>
              <?php if ($daysLeft <= 3 && $daysLeft >= 0): ?>
              <span class="task-deadline-badge">⚡ <?= $daysLeft ?> Tage</span>
              <?php elseif ($daysLeft < 0): ?>
              <span class="task-deadline-badge overdue">⚠ überfällig</span>
              <?php endif; ?>
            </div>
            <div class="task-case"><?= htmlspecialchars($t['klaeger']) ?> ./. <?= htmlspecialchars($t['beklagter']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($today_tasks)): ?>
        <div class="empty">Keine offenen Tasks für heute</div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /main-grid -->
</div><!-- /dashboard -->


<!-- ══ KALENDER ══════════════════════════════════════════════ -->
<div class="page" id="page-calendar">
  <div class="card" style="max-width:1100px;margin:0 auto;">
    <div class="cal-header">
      <div style="display:flex;align-items:center;gap:16px;">
        <span class="cal-month-title" id="cal-title"></span>
        <div class="cal-nav">
          <button class="cal-nav-btn" onclick="changeMonth(-1)">‹</button>
          <button class="cal-nav-btn" onclick="changeMonth(1)">›</button>
        </div>
      </div>
      <div class="cal-legend">
        <div class="cal-legend-item"><span class="legend-dot" style="background:var(--red)"></span>Notfrist</div>
        <div class="cal-legend-item"><span class="legend-dot" style="background:var(--orange)"></span>Gerichtsfrist</div>
        <div class="cal-legend-item"><span class="legend-dot" style="background:var(--blue)"></span>Termin</div>
        <div class="cal-legend-item"><span class="legend-dot" style="background:#7c3aed"></span>Urteil</div>
      </div>
    </div>
    <div class="cal-grid-wrapper">
      <div class="cal-weekdays">
        <div class="cal-weekday">Mo</div><div class="cal-weekday">Di</div>
        <div class="cal-weekday">Mi</div><div class="cal-weekday">Do</div>
        <div class="cal-weekday">Fr</div><div class="cal-weekday">Sa</div>
        <div class="cal-weekday">So</div>
      </div>
      <div class="cal-days" id="cal-days"></div>
    </div>
  </div>
</div>


<!-- ══ MODAL: NEUER FALL ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Neuen Fall anlegen</span>
      <button class="modal-close" onclick="document.getElementById('modal-overlay').classList.remove('open')">✕</button>
    </div>
    <form method="POST" action="add_case.php" id="new-case-form">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Kläger *</label>
            <input type="text" name="klaeger" id="f-klaeger" placeholder="z.B. Müller GmbH">
            <div class="form-error" id="err-klaeger"></div>
          </div>
          <div class="form-group">
            <label>Beklagter *</label>
            <input type="text" name="beklagter" id="f-beklagter" placeholder="z.B. Weber & Partner">
            <div class="form-error" id="err-beklagter"></div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Gericht *</label>
            <select name="gericht" id="f-gericht">
              <option value="">— Gericht wählen —</option>
              <option>LG Frankfurt</option>
              <option>LG München</option>
              <option>AG München</option>
              <option>LG Berlin</option>
              <option>AG Hamburg</option>
              <option>LG Stuttgart</option>
            </select>
            <div class="form-error" id="err-gericht"></div>
          </div>
          <div class="form-group">
            <label>Streitwert (€) *</label>
            <input type="number" name="streitwert" id="f-streitwert" placeholder="0.00" min="0.01" step="0.01">
            <div class="form-error" id="err-streitwert"></div>
          </div>
        </div>
        <div class="form-group">
          <label>Aktenzeichen (optional)</label>
          <input type="text" name="aktenzeichen" placeholder="z.B. 2 O 441/26">
        </div>
        <div class="form-group">
          <label>Zustellungsdatum (optional)</label>
          <input type="date" name="zustellung_datum" id="f-zustellung" oninput="calcDeadlinePreview()">
          <div class="form-hint" id="hint-zustellung"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-overlay').classList.remove('open')">Abbrechen</button>
        <button type="submit" class="btn btn-primary" onclick="return validateForm()">Fall anlegen</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: TAGESÜBERSICHT ══════════════════════════════════ -->
<div class="modal-overlay" id="day-modal-overlay">
  <div class="modal" style="max-width:450px;">
    <div class="modal-header">
      <span class="modal-title">📅 <span id="day-modal-date"></span></span>
      <button class="modal-close" onclick="closeDayModal()">✕</button>
    </div>
    <div class="modal-body" id="day-modal-body">
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary btn-sm" onclick="openTaskModalFromDay()">+ Neue Aufgabe</button>
      <button class="btn btn-ghost btn-sm" onclick="closeDayModal()">Schließen</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: TASK AUS KALENDER ERSTELLEN ═══════════════════════ -->
<div class="modal-overlay" id="task-modal-overlay">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Neue Aufgabe – <span id="task-date-label"></span></span>
      <button class="modal-close" onclick="closeTaskModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Fall *</label>
        <select id="task-case" onchange="loadPhasesForCase()">
          <option value="">— Fall wählen —</option>
        </select>
        <div class="form-error" id="err-task-case"></div>
      </div>
      <div class="form-group">
        <label>Phase *</label>
        <select id="task-phase">
          <option value="">— Erst Fall wählen —</option>
        </select>
        <div class="form-error" id="err-task-phase"></div>
      </div>
      <div class="form-group">
        <label>Deadline *</label>
        <input type="date" id="task-deadline-input">
        <div class="form-error" id="err-task-deadline"></div>
      </div>
      <div class="form-group">
        <label>Beschreibung</label>
        <input type="text" id="task-beschreibung" placeholder="Optional, z.B. Akte prüfen">
      </div>
      <input type="hidden" id="task-deadline-hidden">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeTaskModal()">Abbrechen</button>
      <button type="button" class="btn btn-primary" onclick="submitCalendarTask()">Aufgabe erstellen</button>
    </div>
  </div>
</div>

<!-- Kalender-Daten aus PHP als JS-Variable -->
<script>
  const CAL_EVENTS = <?= json_encode($cal_events) ?>;
  const CASES      = <?= json_encode($cases_for_js) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/app.js?v=2"></script>
</body>
</html>
