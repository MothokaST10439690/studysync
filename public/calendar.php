<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$month  = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year   = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

$year = max(2020, min(2099, $year));
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$firstDay    = mktime(0,0,0,$month,1,$year);
$daysInMonth = (int)date('t', $firstDay);
$startOffset = (int)date('w', $firstDay);

$calendar = array_fill(0, $startOffset, null);
for ($d = 1; $d <= $daysInMonth; $d++) $calendar[] = $d;

$myGroups   = getUserGroups($userId);
$groupIds   = array_column($myGroups, 'group_id');
$tasksByDay = [];

if (!empty($groupIds)) {
    $tasks = getCalendarTasks($groupIds, $month, $year);
    foreach ($tasks as $t) {
        $day = (int)date('j', strtotime($t['due_date']));
        $tasksByDay[$day][] = $t;
    }
}

$today        = (int)date('j');
$isTodayMonth = ($month == (int)date('n')) && ($year == (int)date('Y'));

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1)  { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1;  $nextYear++; }
?>

<!-- ── HERO ───────────────────────────────────────────────── -->
<div class="page-hero" style="margin-bottom:24px;">
    <div class="page-hero-eyebrow">Planning & Deadlines</div>
    <div class="page-hero-title">Calendar</div>
    <div class="page-hero-sub">Keep track of assignments, due dates and group activities.</div>
    <div style="position:absolute;right:30px;top:50%;transform:translateY(-50%);z-index:1;
                background:var(--copper-dim);border:1px solid var(--copper-border);
                border-radius:10px;padding:10px 18px;text-align:center;">
        <div style="font-size:11px;color:rgba(255,255,255,.45);margin-bottom:3px;">Current Month</div>
        <div style="font-family:'Playfair Display',serif;font-size:18px;font-weight:800;color:#fff;">
            <?= date('F Y', $firstDay) ?>
        </div>
    </div>
</div>

<!-- ── NAV ───────────────────────────────────────────────── -->
<div class="card" style="padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;
                          justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="font-size:14px;font-weight:600;">Monthly Schedule</div>
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
           style="width:34px;height:34px;border-radius:6px;border:1px solid var(--border);
                  background:var(--card);display:flex;align-items:center;justify-content:center;
                  color:var(--text-secondary);text-decoration:none;transition:background .15s;"
           onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='var(--card)'">
            <i class="bi bi-chevron-left" style="font-size:13px;"></i>
        </a>
        <div style="padding:7px 16px;background:var(--cream);border-radius:6px;font-size:13.5px;
                    font-weight:600;min-width:160px;text-align:center;">
            <?= date('F Y', $firstDay) ?>
        </div>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>"
           style="width:34px;height:34px;border-radius:6px;border:1px solid var(--border);
                  background:var(--card);display:flex;align-items:center;justify-content:center;
                  color:var(--text-secondary);text-decoration:none;transition:background .15s;"
           onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='var(--card)'">
            <i class="bi bi-chevron-right" style="font-size:13px;"></i>
        </a>
        <a href="calendar.php" class="btn-primary" style="font-size:12px;padding:7px 14px;">Today</a>
    </div>
</div>

<!-- ── CALENDAR GRID ──────────────────────────────────────── -->
<div class="card" style="margin-bottom:18px;">

    <!-- Weekday headers -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);background:var(--cream);border-bottom:1px solid var(--border);">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd): ?>
        <div style="padding:12px 0;text-align:center;font-size:10.5px;font-weight:600;
                    color:var(--text-muted);letter-spacing:.5px;"><?= $wd ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Day cells -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:var(--border-soft);">
        <?php foreach ($calendar as $day):
            $currentDay = $isTodayMonth && $day === $today;
            $hasTasks   = $day && isset($tasksByDay[$day]);
        ?>
        <div style="background:var(--card);min-height:120px;padding:10px;
                    transition:background .1s;<?= $day ? 'cursor:pointer;' : '' ?>"
             onmouseover="<?= $day ? "this.style.background='var(--cream)'" : '' ?>"
             onmouseout="<?= $day ? "this.style.background='var(--card)'" : '' ?>">

            <?php if ($day): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <span style="
                        <?= $currentDay
                            ? 'width:28px;height:28px;border-radius:50%;background:var(--text-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;'
                            : 'font-size:13px;font-weight:600;color:var(--text-primary);' ?>
                    "><?= $day ?></span>
                    <?php if ($hasTasks): ?>
                        <span style="background:var(--copper-dim);color:var(--copper-dark);border:1px solid var(--copper-border);
                                     font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;">
                            <?= count($tasksByDay[$day]) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($hasTasks): ?>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        <?php foreach ($tasksByDay[$day] as $task):
                            $isDone    = $task['status'] === 'done';
                            $isOverdue = !$isDone && strtotime($task['due_date']) < strtotime('today');
                            $dotColor  = $isDone ? 'var(--green)' : ($isOverdue ? 'var(--red)' : 'var(--amber)');
                        ?>
                        <div title="<?= htmlspecialchars($task['title']) ?>"
                             style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:var(--text-secondary);">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?= $dotColor ?>;flex-shrink:0;"></span>
                            <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                <?= htmlspecialchars(mb_substr($task['title'], 0, 18)) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── LEGEND ─────────────────────────────────────────────── -->
<div class="card" style="padding:14px 20px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:10px;">Status Legend</div>
    <div style="display:flex;flex-wrap:wrap;gap:18px;font-size:13px;color:var(--text-secondary);">
        <div style="display:flex;align-items:center;gap:7px;">
            <span style="width:10px;height:10px;border-radius:50%;background:var(--amber);"></span> Pending
        </div>
        <div style="display:flex;align-items:center;gap:7px;">
            <span style="width:10px;height:10px;border-radius:50%;background:var(--red);"></span> Overdue
        </div>
        <div style="display:flex;align-items:center;gap:7px;">
            <span style="width:10px;height:10px;border-radius:50%;background:var(--green);"></span> Completed
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require 'layout.php';
?>