<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$userId       = $_SESSION['user_id'];
$myTasks      = getTasksByUser($userId);
$myGroups     = getUserGroups($userId);
$pendingTasks = array_filter($myTasks, fn($t) => $t['status'] === 'pending');
$overdueTasks = array_filter($myTasks, fn($t) =>
    $t['status'] === 'pending' && strtotime($t['due_date']) < strtotime('today')
);
$stats = ($_SESSION['user_role'] === 'admin') ? getAdminStats() : null;
?>

<!-- ── HERO ───────────────────────────────────────────────── -->
<div class="page-hero" style="margin-bottom:24px;">
    <div class="page-hero-eyebrow">Welcome Back</div>
    <div class="page-hero-title"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
    <div class="page-hero-sub"><?= date('l, d F Y') ?></div>
    <a href="groups.php" style="
        position:absolute; right:30px; top:50%; transform:translateY(-50%);
        padding:9px 18px; border-radius:10px; background:transparent;
        border:1px solid rgba(205,133,63,.28); color:#cd853f;
        font-size:13px; font-weight:600; text-decoration:none;
        display:flex; align-items:center; gap:6px; z-index:1;
        transition:background .15s;
    " onmouseover="this.style.background='rgba(205,133,63,.12)'"
       onmouseout="this.style.background='transparent'">
        Browse Groups <i class="bi bi-arrow-right"></i>
    </a>
</div>

<!-- ── STAT CARDS ────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px;">

    <div class="stat-card">
        <div class="stat-card-label"><i class="bi bi-people-fill" style="margin-right:5px;"></i>My Groups</div>
        <div class="stat-card-value"><?= count($myGroups) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-label"><i class="bi bi-list-task" style="margin-right:5px;"></i>My Tasks</div>
        <div class="stat-card-value"><?= count($myTasks) ?></div>
    </div>

    <div class="stat-card copper-tint">
        <div class="stat-card-label"><i class="bi bi-clock-fill" style="margin-right:5px;"></i>Pending</div>
        <div class="stat-card-value"><?= count($pendingTasks) ?></div>
    </div>

    <div class="stat-card red-tint">
        <div class="stat-card-label"><i class="bi bi-exclamation-triangle-fill" style="margin-right:5px;"></i>Overdue</div>
        <div class="stat-card-value"><?= count($overdueTasks) ?></div>
    </div>

</div>

<!-- ── QUICK ACTIONS ─────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:22px;">

    <?php
    $quickActions = [
        ['href'=>'groups.php',   'icon'=>'bi-people-fill',          'title'=>'Browse Groups', 'desc'=>'Discover and join study communities'],
        ['href'=>'tasks.php',    'icon'=>'bi-check2-square',         'title'=>'Manage Tasks',  'desc'=>'Track assignments and deadlines'],
        ['href'=>'calendar.php', 'icon'=>'bi-calendar-event-fill',   'title'=>'Calendar',      'desc'=>'View upcoming deadlines at a glance'],
    ];
    foreach ($quickActions as $qa):
    ?>
    <a href="<?= $qa['href'] ?>" style="
        background:var(--card); border:1px solid var(--border); border-radius:14px;
        padding:16px 18px; display:flex; align-items:center; gap:12px;
        text-decoration:none; color:inherit; transition:border-color .15s, background .15s;
    " onmouseover="this.style.borderColor='rgba(205,133,63,.28)';this.style.background='#fdf9f4'"
       onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--card)'">
        <div style="width:38px;height:38px;border-radius:6px;background:#f2ece5;color:#a8642a;
                    display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;">
            <i class="bi <?= $qa['icon'] ?>"></i>
        </div>
        <div style="min-width:0;">
            <div style="font-size:13.5px;font-weight:600;"><?= $qa['title'] ?></div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;"><?= $qa['desc'] ?></div>
        </div>
        <i class="bi bi-arrow-right" style="margin-left:auto;color:var(--border);font-size:15px;flex-shrink:0;"></i>
    </a>
    <?php endforeach; ?>

</div>

<!-- ── ADMIN STRIP ────────────────────────────────────────── -->
<?php if ($stats): ?>
<div style="background:var(--sidebar);border:1px solid var(--sidebar-border);border-radius:14px;
            padding:20px 24px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;">
    <div>
        <div style="font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#fff;
                    display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            System Overview
            <span style="font-family:'DM Sans',sans-serif;font-size:10px;padding:2px 8px;border-radius:20px;
                         background:rgba(205,133,63,.12);color:#cd853f;border:1px solid rgba(205,133,63,.28);
                         font-weight:500;letter-spacing:.3px;">Administrator</span>
        </div>
        <div style="display:flex;gap:32px;">
            <?php foreach([
                ['Users',  $stats['total_users']],
                ['Groups', $stats['total_groups']],
                ['Tasks',  $stats['total_tasks']],
                ['Files',  $stats['total_files']],
            ] as [$lbl,$val]): ?>
            <div>
                <div style="font-size:11px;color:rgba(255,255,255,.35);margin-bottom:4px;"><?= $lbl ?></div>
                <div style="font-family:'Playfair Display',serif;font-size:24px;font-weight:800;color:#fff;letter-spacing:-0.5px;"><?= $val ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <a href="admin.php" class="btn-primary btn-copper">
        <i class="bi bi-shield-lock-fill"></i> Admin Panel
    </a>
</div>
<?php endif; ?>

<!-- ── TASKS + GROUPS ─────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;">

    <!-- Recent Tasks -->
    <div class="card">
        <div class="panel-header">
            <div>
                <div class="panel-title">Recent Tasks</div>
                <div class="panel-sub">Your upcoming assignments and deadlines</div>
            </div>
            <a href="tasks.php" class="panel-link">View All →</a>
        </div>

        <?php if (empty($myTasks)): ?>
            <div class="empty-state">
                <i class="bi bi-check2-square" style="font-size:28px;display:block;margin-bottom:8px;color:var(--text-muted);"></i>
                <p>No tasks yet. Tasks assigned to you will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach (array_slice($myTasks, 0, 5) as $task):
                $isOverdue = $task['status'] === 'pending' && strtotime($task['due_date']) < strtotime('today');
                $pipColor  = $task['status'] === 'done' ? '#1a7a40' : ($isOverdue ? '#c0392b' : '#b7610a');
                $pillClass = $task['status'] === 'done' ? 'pill-done' : ($isOverdue ? 'pill-overdue' : 'pill-pending');
                $label     = $task['status'] === 'done' ? 'Completed' : ($isOverdue ? 'Overdue' : 'Pending');
            ?>
            <div style="display:flex;align-items:center;gap:13px;padding:12px 20px;
                        border-bottom:1px solid var(--border-soft);transition:background .1s;cursor:pointer;"
                 onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background=''">
                <div style="width:9px;height:9px;border-radius:50%;background:<?= $pipColor ?>;flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13.5px;font-weight:500;<?= $task['status']==='done' ? 'text-decoration:line-through;color:var(--text-muted);' : '' ?>">
                        <?= htmlspecialchars($task['title']) ?>
                    </div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;">
                        Due <?= date('d M Y', strtotime($task['due_date'])) ?>
                    </div>
                </div>
                <span class="pill <?= $pillClass ?>"><?= $label ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- My Groups -->
    <div class="card">
        <div class="panel-header">
            <div>
                <div class="panel-title">My Groups</div>
                <div class="panel-sub">Active study communities</div>
            </div>
            <a href="groups.php" class="panel-link">View All →</a>
        </div>

        <?php if (empty($myGroups)): ?>
            <div class="empty-state">
                <i class="bi bi-people" style="font-size:28px;display:block;margin-bottom:8px;color:var(--text-muted);"></i>
                <p>No groups joined yet.</p>
                <a href="groups.php" style="display:inline-block;margin-top:12px;" class="btn-primary">Explore Groups</a>
            </div>
        <?php else: ?>
            <div style="padding:10px 0 6px;">
                <?php foreach ($myGroups as $g): ?>
                <a href="group.php?id=<?= $g['group_id'] ?>" style="
                    display:flex;align-items:center;gap:11px;padding:9px 14px;
                    margin:0 10px 7px;border-radius:10px;border:1px solid var(--border);
                    text-decoration:none;color:inherit;transition:border-color .15s,background .1s;
                " onmouseover="this.style.borderColor='rgba(205,133,63,.28)';this.style.background='#fdf9f4'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.background=''">
                    <div style="width:36px;height:36px;border-radius:6px;background:var(--sidebar);
                                color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;
                                font-weight:700;font-size:13px;flex-shrink:0;">
                        <?= strtoupper(substr($g['group_name'], 0, 2)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($g['group_name']) ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">
                            <?= $g['member_count'] ?> members
                        </div>
                    </div>
                    <i class="bi bi-chevron-right" style="color:var(--border);font-size:12px;flex-shrink:0;"></i>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php
$page_content = ob_get_clean();
require 'layout.php';
?>