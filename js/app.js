// ============================================================
// LexCortex – app.js
// jQuery + Vanilla JS
// ============================================================

// ── Tab-Wechsel: Dashboard / Kalender ────────────────────────
function showTab(name) {
    $('.page').removeClass('active');
    $('.nav-tab').removeClass('active');
    $('#page-' + name).addClass('active');
    $('#tab-' + name).addClass('active');
    if (name === 'calendar') renderCalendar();
}

// Beim Laden prüfen ob Kalender direkt geöffnet werden soll
$(document).ready(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'calendar') {
        showTab('calendar');
    }

    // Phasen-Filter: Tabelle nach Phase filtern (jQuery)
    $('#phase-filter').on('change', function () {
        var val = $(this).val().toLowerCase();
        $('#cases-tbody tr').each(function () {
            var phase = $(this).data('phase') || '';
            if (!val || phase.toLowerCase().indexOf(val) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Aus Kalender kommend: Task highlighten, Phase aufklappen, blinken
    highlightTaskFromCalendar();
});

// ── Phase accordion (jQuery slideToggle) ─────────────────────
function togglePhase(id) {
    var item  = $('#' + id);
    var panel = item.find('.phase-panel');
    panel.slideToggle(200);
    item.toggleClass('open');
}

// ── Aus Kalender kommend: Task finden, Phase aufklappen, blinken ──
function highlightTaskFromCalendar() {
    var params = new URLSearchParams(window.location.search);
    var taskId = params.get('task_id');
    var from   = params.get('from');

    if (!taskId || from !== 'calendar') return;

    var taskEl = document.getElementById('task-' + taskId);
    if (!taskEl) return;

    // Übergeordnete Phase finden und ggf. aufklappen
    var phaseItem = taskEl.closest('.phase-item');
    var doHighlight = function() {
        // Zum Task scrollen
        taskEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Zweimal blinken lassen
        taskEl.classList.add('flash-highlight');
        setTimeout(function() {
            taskEl.classList.remove('flash-highlight');
            setTimeout(function() {
                taskEl.classList.add('flash-highlight');
                setTimeout(function() {
                    taskEl.classList.remove('flash-highlight');
                }, 600);
            }, 150);
        }, 600);
    };

    if (phaseItem && !phaseItem.classList.contains('open')) {
        togglePhase(phaseItem.id);
        // Warten bis Slide-Animation (200ms) fertig ist, dann highlighten
        setTimeout(doHighlight, 350);
    } else {
        doHighlight();
    }
}

// ── Formularvalidierung: Neuer Fall (Vanilla JS) ─────────────
function validateForm() {
    var valid = true;

    // Fehlermeldungen zurücksetzen
    ['klaeger', 'beklagter', 'gericht', 'streitwert'].forEach(function (f) {
        document.getElementById('err-' + f).textContent = '';
        var el = document.getElementById('f-' + f);
        if (el) el.classList.remove('error');
    });

    // Pflichtfelder prüfen
    var fields = [
        { id: 'f-klaeger',    err: 'err-klaeger',    msg: 'Kläger ist Pflichtfeld' },
        { id: 'f-beklagter',  err: 'err-beklagter',  msg: 'Beklagter ist Pflichtfeld' },
        { id: 'f-gericht',    err: 'err-gericht',    msg: 'Bitte Gericht wählen' },
    ];
    fields.forEach(function (f) {
        var el = document.getElementById(f.id);
        if (!el.value.trim()) {
            document.getElementById(f.err).textContent = f.msg;
            el.classList.add('error');
            valid = false;
        }
    });

    // Streitwert > 0 prüfen
    var sw = document.getElementById('f-streitwert');
    if (!sw.value || parseFloat(sw.value) <= 0) {
        document.getElementById('err-streitwert').textContent = 'Streitwert muss größer als 0 sein';
        sw.classList.add('error');
        valid = false;
    }

    return valid;
}

// Automatische Fristvorschau bei Zustellungsdatum-Eingabe
function calcDeadlinePreview() {
    var val  = document.getElementById('f-zustellung').value;
    var hint = document.getElementById('hint-zustellung');
    if (!val) { hint.textContent = ''; return; }
    var d = new Date(val);
    d.setDate(d.getDate() + 14);
    var fmt = d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    hint.textContent = '⚡ Verteidigungsanzeige fällig am: ' + fmt + ' (automatisch +14 Tage)';
}

// ── Formularvalidierung: Task hinzufügen ─────────────────────
function validateTask() {
    var valid = true;
    document.getElementById('err-task-desc').textContent     = '';
    document.getElementById('err-task-deadline').textContent = '';
    document.getElementById('task-desc').classList.remove('error');
    document.getElementById('task-deadline').classList.remove('error');

    var phaseEl = document.getElementById('task-phase-select');
    if (phaseEl) {
        document.getElementById('err-task-phase').textContent = '';
        phaseEl.classList.remove('error');
        if (!phaseEl.value) {
            document.getElementById('err-task-phase').textContent = 'Phase ist Pflichtfeld';
            phaseEl.classList.add('error');
            valid = false;
        }
    }

    if (!document.getElementById('task-desc').value.trim()) {
        document.getElementById('err-task-desc').textContent = 'Beschreibung ist Pflichtfeld';
        document.getElementById('task-desc').classList.add('error');
        valid = false;
    }

    var dl = document.getElementById('task-deadline').value;
    if (!dl) {
        document.getElementById('err-task-deadline').textContent = 'Deadline ist Pflichtfeld';
        document.getElementById('task-deadline').classList.add('error');
        valid = false;
    }

    return valid;
}

// ── AJAX: Task als erledigt markieren ────────────────────────
function completeTask(taskId, checkbox) {
    $.ajax({
        url:  'ajax/complete_task.php',
        type: 'POST',
        data: { task_id: taskId },
        success: function (res) {
            if (res.success) {
                // Checkbox visuell abhaken
                $(checkbox).addClass('done').text('✓');
                // Dashboard: alte Struktur (.task-item)
                $(checkbox).closest('.task-item').find('.task-desc').addClass('done');
                // Case-Detail: neue Struktur (.phase-task-item)
                $(checkbox).closest('.phase-task-item').addClass('task-done');
            }
        },
        error: function () {
            alert('Fehler beim Speichern.');
        }
    });
}

// ── AJAX: Phase abschließen ──────────────────────────────────
function completePhase(phaseId, caseId, phaseTitle) {
    var dateVal = document.getElementById('date-' + phaseId).value;
    if (!dateVal) {
        alert('Bitte zuerst ein Datum eingeben.');
        return;
    }

    $.ajax({
        url:  'ajax/complete_phase.php',
        type: 'POST',
        data: { phase_id: phaseId, case_id: caseId, phase_title: phaseTitle, date: dateVal },
        success: function (res) {
            if (res.success) {
                // Dot auf grün setzen
                var item = $('#phase-' + phaseId);
                item.find('.phase-dot').removeClass('active pending').addClass('done').text('✓');
                item.find('.phase-title').removeClass('active').addClass('done');
                item.find('.phase-badges').html("<span class='badge badge-done'>Erledigt</span>");

                // Feedback anzeigen
                var feedback = $('#feedback-' + phaseId);
                if (res.auto_msg) feedback.text('✓ ' + res.auto_msg);
                else              feedback.text('✓ Phase abgeschlossen!');
                feedback.show();

                // Button entfernen
                item.find('.phase-complete-form').hide();

                // Nach kurzer Pause Seite neu laden (damit nächste Phase aktiv wird)
                setTimeout(function () { location.reload(); }, 1500);
            }
        },
        error: function () {
            alert('Fehler beim Speichern.');
        }
    });
}

// ── Phase zurücknehmen ──────────────────────────────────────
function revertPhase(caseId, phaseId) {
    var msg = phaseId
        ? 'Diese Phase und alle nachfolgenden zurücknehmen?'
        : 'Letzte abgeschlossene Phase zurücknehmen?';
    if (!confirm(msg)) return;

    $.ajax({
        url:  'ajax/revert_phase.php',
        type: 'POST',
        data: { case_id: caseId, phase_id: phaseId || 0 },
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Fehler: ' + (res.error || 'Unbekannter Fehler'));
            }
        },
        error: function() {
            alert('Fehler beim Zurücksetzen.');
        }
    });
}

// ── Phase-Datum inline bearbeiten ───────────────────────────
function editPhaseDate(phaseId) {
    var display = document.getElementById('phase-date-display-' + phaseId);
    var input   = document.getElementById('phase-date-edit-' + phaseId);
    if (!display || !input) return;
    display.style.display = 'none';
    input.style.display   = 'inline';
    input.focus();
}

function savePhaseDate(phaseId) {
    var display = document.getElementById('phase-date-display-' + phaseId);
    var input   = document.getElementById('phase-date-edit-' + phaseId);
    if (!display || !input) return;
    var newDate = input.value;
    if (!newDate) { cancelPhaseDate(phaseId); return; }

    $.ajax({
        url:  'ajax/update_phase.php',
        type: 'POST',
        data: { phase_id: phaseId, date: newDate },
        success: function(res) {
            if (res.success) {
                var parts = newDate.split('-');
                display.textContent = parts[2] + '.' + parts[1] + '.' + parts[0];
            }
            display.style.display = '';
            input.style.display   = 'none';
        },
        error: function() {
            display.style.display = '';
            input.style.display   = 'none';
        }
    });
}

// ── Inline Task-Form (Case-Detail, unter jeder Phase) ──────
function toggleInlineTaskForm(phaseId) {
    var form = document.getElementById('inline-form-' + phaseId);
    if (!form) return;
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
}

function addInlineTask(phaseId, caseId) {
    var desc    = document.getElementById('inline-desc-'    + phaseId).value.trim();
    var start   = document.getElementById('inline-start-'   + phaseId).value;
    var deadline = document.getElementById('inline-deadline-' + phaseId).value;

    if (!deadline) { alert('Bitte Deadline eingeben.'); return; }
    if (!desc) desc = 'Neue Aufgabe';

    $.ajax({
        url:  'ajax/create_task.php',
        type: 'POST',
        data: {
            case_id: caseId, phase_id: phaseId,
            beschreibung: desc, start_date: start, deadline: deadline
        },
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Fehler: ' + (res.error || 'Unbekannter Fehler'));
            }
        },
        error: function() { alert('Fehler beim Speichern.'); }
    });
}

// ── Kalender-Task-Modal ─────────────────────────────────────
function populateCaseDropdown() {
    var sel = document.getElementById('task-case');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Fall wählen —</option>';
    if (typeof CASES !== 'undefined' && CASES.length) {
        CASES.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.klaeger + ' ./. ' + c.beklagter;
            sel.appendChild(opt);
        });
    }
}

function openTaskModal(dateStr, formattedDate) {
    document.getElementById('task-date-label').textContent = formattedDate;
    document.getElementById('task-deadline-hidden').value = dateStr;
    document.getElementById('task-deadline-input').value = dateStr;
    document.getElementById('task-case').value = '';
    document.getElementById('task-phase').innerHTML = '<option value="">— Erst Fall wählen —</option>';
    document.getElementById('task-beschreibung').value = '';
    ['err-task-case', 'err-task-phase', 'err-task-deadline'].forEach(function(id) {
        document.getElementById(id).textContent = '';
    });
    populateCaseDropdown();
    document.getElementById('task-modal-overlay').classList.add('open');
}

function closeTaskModal() {
    document.getElementById('task-modal-overlay').classList.remove('open');
}

function loadPhasesForCase() {
    var caseId   = document.getElementById('task-case').value;
    var phaseSel = document.getElementById('task-phase');
    document.getElementById('err-task-case').textContent = '';

    if (!caseId) {
        phaseSel.innerHTML = '<option value="">— Erst Fall wählen —</option>';
        return;
    }

    phaseSel.innerHTML = '<option value="">Lade Phasen…</option>';

    $.ajax({
        url:  'ajax/get_phases.php',
        type: 'GET',
        data: { case_id: caseId },
        success: function(res) {
            if (res.success) {
                if (res.phases.length === 0) {
                    phaseSel.innerHTML = '<option value="">— Keine offenen Phasen —</option>';
                } else {
                    var html = '<option value="">— Phase wählen —</option>';
                    res.phases.forEach(function(p) {
                        var label = p.status === 'active' ? ' [aktiv]' : '';
                        html += '<option value="' + p.id + '">' + p.title + label + '</option>';
                    });
                    phaseSel.innerHTML = html;
                }
            }
        },
        error: function() {
            phaseSel.innerHTML = '<option value="">— Fehler beim Laden —</option>';
        }
    });
}

function submitCalendarTask() {
    var caseId   = document.getElementById('task-case').value;
    var phaseId  = document.getElementById('task-phase').value;
    var beschr   = document.getElementById('task-beschreibung').value.trim();
    var deadline = document.getElementById('task-deadline-input').value;
    var valid    = true;

    ['task-case', 'task-phase', 'task-deadline'].forEach(function(f) {
        document.getElementById('err-' + f).textContent = '';
    });

    if (!caseId) {
        document.getElementById('err-task-case').textContent = 'Bitte Fall wählen';
        valid = false;
    }
    if (!phaseId) {
        document.getElementById('err-task-phase').textContent = 'Bitte Phase wählen';
        valid = false;
    }
    if (!deadline) {
        document.getElementById('err-task-deadline').textContent = 'Bitte Deadline wählen';
        valid = false;
    }
    if (!valid) return;

    // Beschreibung optional: Wenn leer, Fallnamen als Titel verwenden
    if (!beschr) {
        var caseSel = document.getElementById('task-case');
        beschr = caseSel.options[caseSel.selectedIndex].textContent;
    }

    $.ajax({
        url:  'ajax/create_task.php',
        type: 'POST',
        data: {
            case_id:      caseId,
            phase_id:     phaseId,
            beschreibung: beschr,
            start_date:   deadline,  // Kalender-Task: Start = Deadline (nur an diesem Tag sichtbar)
            deadline:     deadline
        },
        success: function(res) {
            if (res.success) {
                closeTaskModal();
                // Dashboard Task-Liste synchron aktualisieren
                addTaskToDashboard(res.task);
                // Task sofort im Kalender anzeigen (ohne Neuladen)
                addTaskToCalendar(res.task);
                // Kalender neu rendern
                renderCalendar();
            } else {
                alert('Fehler: ' + (res.error || 'Unbekannter Fehler'));
            }
        },
        error: function() {
            alert('Fehler beim Speichern.');
        }
    });
}

// Fügt den neuen Task dynamisch in die Dashboard-Task-Liste ein
function addTaskToDashboard(task) {
    var list = document.querySelector('.task-list');
    if (!list) return;

    var empty = list.querySelector('.empty');
    if (empty) empty.remove();

    var today     = new Date();
    today.setHours(0, 0, 0, 0);
    var startDate = new Date(task.start_date || task.deadline);
    startDate.setHours(0, 0, 0, 0);

    // Nur einfügen wenn Start-Datum erreicht
    if (startDate <= today) {
        var deadlineDate = new Date(task.deadline);
        var daysLeft = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
        var warnClass = '';
        var badgeHtml = '';
        if (daysLeft < 0)      { warnClass = 'task-overdue'; badgeHtml = '<span class="task-deadline-badge overdue">⚠ überfällig</span>'; }
        else if (daysLeft <= 3) { warnClass = 'task-warn';    badgeHtml = '<span class="task-deadline-badge">⚡ ' + daysLeft + ' Tage</span>'; }

        var item = document.createElement('div');
        item.className = 'task-item ' + warnClass;
        item.id = 'task-' + task.id;
        item.innerHTML =
            '<div class="task-checkbox" ' +
            'onclick="completeTask(' + task.id + ', this)" ' +
            'title="Als erledigt markieren"></div>' +
            '<div class="task-info">' +
            '<div class="task-desc">' + escapeHtml(task.beschreibung) + ' ' + badgeHtml + '</div>' +
            '<div class="task-case">' + escapeHtml(task.klaeger) + ' ./. ' + escapeHtml(task.beklagter) + '</div>' +
            '</div>';
        list.insertBefore(item, list.firstChild);
    }
}

// Einfaches HTML-Escaping für dynamisch eingefügte Inhalte
function escapeHtml(text) {
    var d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

// Task sofort im Kalender anzeigen (ohne Seiten-Reload)
function addTaskToCalendar(task) {
    if (typeof calEvents === 'undefined') return;
    if (!calEvents[task.deadline]) calEvents[task.deadline] = [];
    calEvents[task.deadline].push({
        type:    'task',
        label:   task.beschreibung,
        color:   'task',
        case:    task.klaeger + ' ./. ' + task.beklagter,
        case_id: task.case_id,
        task_id: task.id
    });
}

// ── Tagesübersicht (Kalender-Tag klicken) ────────────────────
var selectedDayDate  = '';  // YYYY-MM-DD
var selectedDayLabel = '';  // dd.mm.yyyy

function openDayModal(dateStr, formattedDate) {
    // Nur öffnen wenn NICHT auf + Button oder Event-Pill geklickt wurde
    selectedDayDate  = dateStr;
    selectedDayLabel = formattedDate;
    document.getElementById('day-modal-date').textContent = formattedDate;

    var events  = calEvents[dateStr] || [];
    var body    = document.getElementById('day-modal-body');
    var html    = '';

    if (events.length === 0) {
        html = '<div class="day-modal-empty">Keine Termine oder Aufgaben an diesem Tag.</div>';
    } else {
        html = '<ul class="day-modal-list">';
        events.forEach(function(ev) {
            var icon = ev.type === 'task' ? '📋' : '🔴';
            html += '<li class="day-modal-item">';
            html += '<span class="day-modal-icon">' + icon + '</span>';
            html += '<div class="day-modal-info">';
            html += '<div class="day-modal-label">' + escapeHtml(ev.label) + '</div>';
            html += '<div class="day-modal-case">' + escapeHtml(ev.case) + '</div>';
            html += '</div>';
            if (ev.case_id) {
                var href = 'case_detail.php?id=' + ev.case_id;
                if (ev.type === 'task' && ev.task_id) {
                    href += '&task_id=' + ev.task_id + '&from=calendar';
                }
                html += '<a class="day-modal-link" href="' + href + '">→</a>';
            }
            html += '</li>';
        });
        html += '</ul>';
    }
    body.innerHTML = html;
    document.getElementById('day-modal-overlay').classList.add('open');
}

function closeDayModal() {
    document.getElementById('day-modal-overlay').classList.remove('open');
}

function openTaskModalFromDay() {
    closeDayModal();
    openTaskModal(selectedDayDate, selectedDayLabel);
}

// ── Task bearbeiten / löschen ────────────────────────────────
function editTask(taskId) {
    var item = document.getElementById('task-' + taskId);
    if (!item) return;
    // Zeige Edit-Felder, verstecke Anzeige
    item.classList.add('editing');
    item.querySelector('.phase-task-desc').style.display = 'none';
    item.querySelector('.phase-task-deadline').style.display = 'none';
    item.querySelector('.phase-task-edit').style.display = 'flex';
    // Buttons tauschen
    item.querySelector('.btn-icon').style.display = 'none';
    item.querySelector('.btn-icon-delete').style.display = 'none';
    item.querySelector('.btn-icon-save').style.display = 'inline';
    item.querySelector('.btn-icon-cancel').style.display = 'inline';
}

function cancelEditTask(taskId) {
    var item = document.getElementById('task-' + taskId);
    if (!item) return;
    item.classList.remove('editing');
    item.querySelector('.phase-task-desc').style.display = '';
    item.querySelector('.phase-task-deadline').style.display = '';
    item.querySelector('.phase-task-edit').style.display = 'none';
    item.querySelector('.btn-icon').style.display = '';
    item.querySelector('.btn-icon-delete').style.display = '';
    item.querySelector('.btn-icon-save').style.display = 'none';
    item.querySelector('.btn-icon-cancel').style.display = 'none';
}

function saveTask(taskId) {
    var item   = document.getElementById('task-' + taskId);
    if (!item) return;
    var desc   = item.querySelector('.task-edit-desc').value.trim();
    var deadline = item.querySelector('.task-edit-deadline').value;

    if (!desc || !deadline) { alert('Beschreibung und Deadline dürfen nicht leer sein.'); return; }

    $.ajax({
        url:  'ajax/update_task.php',
        type: 'POST',
        data: { task_id: taskId, beschreibung: desc, deadline: deadline },
        success: function(res) {
            if (res.success) {
                item.querySelector('.phase-task-desc').textContent = desc;
                item.querySelector('.phase-task-deadline').textContent =
                    '📅 ' + new Date(deadline).toLocaleDateString('de-DE',
                        { day: '2-digit', month: '2-digit', year: 'numeric' });
                cancelEditTask(taskId);
            } else {
                alert('Fehler: ' + (res.error || 'Unbekannter Fehler'));
            }
        },
        error: function() { alert('Fehler beim Speichern.'); }
    });
}

function deleteTask(taskId) {
    if (!confirm('Task wirklich löschen?')) return;
    $.ajax({
        url:  'ajax/delete_task.php',
        type: 'POST',
        data: { task_id: taskId },
        success: function(res) {
            if (res.success) {
                var item = document.getElementById('task-' + taskId);
                if (item) item.remove();
            } else {
                alert('Fehler: ' + (res.error || 'Unbekannter Fehler'));
            }
        },
        error: function() { alert('Fehler beim Löschen.'); }
    });
}

// ── Kalender ─────────────────────────────────────────────────
var calYear  = new Date().getFullYear();
var calMonth = new Date().getMonth();

// Events aus PHP (werden in index.php als CAL_EVENTS übergeben)
// Kalender-Events haben auch case_id für die Verlinkung
var calEvents = (typeof CAL_EVENTS !== 'undefined') ? CAL_EVENTS : {};

function changeMonth(dir) {
    calMonth += dir;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    renderCalendar();
}

function renderCalendar() {
    var monthNames = ['Januar','Februar','März','April','Mai','Juni',
                      'Juli','August','September','Oktober','November','Dezember'];
    document.getElementById('cal-title').textContent = monthNames[calMonth] + ' ' + calYear;

    var container = document.getElementById('cal-days');
    container.innerHTML = '';

    var today     = new Date();
    var firstDay  = new Date(calYear, calMonth, 1);
    var startDow  = firstDay.getDay();
    if (startDow === 0) startDow = 7;
    startDow--;

    var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    var daysInPrev  = new Date(calYear, calMonth, 0).getDate();
    var totalCells  = Math.ceil((startDow + daysInMonth) / 7) * 7;

    for (var i = 0; i < totalCells; i++) {
        var day, m = calMonth, y = calYear, isOther = false;

        if (i < startDow) {
            day = daysInPrev - startDow + i + 1;
            m   = calMonth - 1;
            if (m < 0) { m = 11; y--; }
            isOther = true;
        } else if (i >= startDow + daysInMonth) {
            day = i - startDow - daysInMonth + 1;
            m   = calMonth + 1;
            if (m > 11) { m = 0; y++; }
            isOther = true;
        } else {
            day = i - startDow + 1;
        }

        var cell = document.createElement('div');
        cell.className = 'cal-day' + (isOther ? ' other-month' : '');

        var isToday = day === today.getDate() && m === today.getMonth() && y === today.getFullYear();
        if (isToday) cell.classList.add('today');

        // Tageszahl
        var numDiv = document.createElement('div');
        numDiv.className = 'cal-day-num';
        numDiv.textContent = day;
        cell.appendChild(numDiv);

        // Events aus calEvents (Deadlines + Tasks)
        var dateStr = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        var dayEvents = calEvents[dateStr] || [];
        dayEvents.forEach(function (ev) {
            var pill = document.createElement('a');
            if (ev.type === 'task') {
                // Task-Pill: grau / warn / rot je nach Deadline-Nähe
                var taskCls = 'cal-event ' + (ev.color || 'task');
                pill.className = taskCls;
                var prefix = '📋 ';
                if (ev.color === 'task-warn')    prefix = '⚡ ';
                if (ev.color === 'task-overdue') prefix = '⚠ ';
                pill.textContent = prefix + ev.label;
            } else {
                // Deadline-Pill: farbig wie bisher
                pill.className = 'cal-event ' + ev.color;
                pill.textContent = ev.label;
            }
            pill.title = ev.case;
            if (ev.case_id) {
                var href = 'case_detail.php?id=' + ev.case_id;
                if (ev.type === 'task' && ev.task_id) {
                    href += '&task_id=' + ev.task_id + '&from=calendar';
                }
                pill.href = href;
            } else {
                pill.href = 'index.php';
            }
            cell.appendChild(pill);
        });

        container.appendChild(cell);

        // Tag-Klick → Tagesübersicht (nur aktueller Monat)
        if (!isOther) {
            var fmtDate = day + '.' + String(m + 1).padStart(2, '0') + '.' + y;
            (function(ds, fd) {
                cell.addEventListener('click', function(e) {
                    // Nicht feuern wenn + oder Event-Pill geklickt wurde
                    if (e.target.classList.contains('cal-add-task') ||
                        e.target.classList.contains('cal-event')) return;
                    e.stopPropagation();
                    openDayModal(ds, fd);
                });
            })(dateStr, fmtDate);
            cell.style.cursor = 'pointer';
        }

        // "+" Button: Task aus Kalender erstellen
        if (!isOther) {
            var addBtn = document.createElement('span');
            addBtn.className = 'cal-add-task';
            addBtn.textContent = '+';
            addBtn.title = 'Neue Aufgabe';

            var fmtDate = day + '.' + String(m + 1).padStart(2, '0') + '.' + y;

            (function(ds, fd) {
                addBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    openTaskModal(ds, fd);
                });
            })(dateStr, fmtDate);

            cell.appendChild(addBtn);
        }
    }
}
