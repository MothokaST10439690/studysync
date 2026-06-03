<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$groupId) { ob_end_clean(); header('Location: groups.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM study_groups WHERE group_id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) { ob_end_clean(); header('Location: groups.php'); exit; }

$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $_SESSION['user_id']]);
if (!$stmt->fetch()) { ob_end_clean(); header('Location: groups.php'); exit; }

$tab      = $_GET['tab'] ?? 'tasks';
$members  = getGroupMembers($groupId);
$tasks    = getGroupTasks($groupId);
$files    = getGroupFiles($groupId);
$messages = getMessages($groupId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) sendMessage($groupId, $_SESSION['user_id'], $msg);
    header("Location: group.php?id=$groupId&tab=chat");
    exit;
}



$canManageGroup =
    $_SESSION['user_role'] === 'admin' ||
    isGroupCreator($groupId, $_SESSION['user_id']);

    $canManageGroup =
    $_SESSION['user_role'] === 'admin' ||
    isGroupCreator($groupId, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_group'])
    && $canManageGroup) {

    deleteGroup($groupId);

    header('Location: groups.php');
    exit;
}

$tabs = [
    'tasks'   => ['icon'=>'bi-check2-square',    'label'=>'Tasks'],
    'members' => ['icon'=>'bi-people-fill',       'label'=>'Members'],
    'files'   => ['icon'=>'bi-folder-fill',       'label'=>'Files'],
    'chat'    => ['icon'=>'bi-chat-dots-fill',    'label'=>'Chat'],
];
?>

<!-- ── HERO ───────────────────────────────────────────────── -->
<div class="page-hero" style="margin-bottom:24px;">
    <div class="page-hero-eyebrow">Study Group</div>
    <div class="page-hero-title"><?= htmlspecialchars($group['group_name']) ?></div>
    <div class="page-hero-sub">
        <?= htmlspecialchars($group['description'] ?? 'Collaborate, share resources and achieve academic success together.') ?>
    </div>

<?php if ($canManageGroup): ?>
<form method="POST"
      onsubmit="return confirm('Delete this group permanently?')"
      style="margin-top:15px;">
    <button type="submit"
            name="delete_group"
            style="
                padding:8px 16px;
                border:none;
                border-radius:8px;
                background:#dc3545;
                color:white;
                font-weight:600;
                cursor:pointer;">
        Delete Group
    </button>
</form>
<?php endif; ?> 
</div>

<!-- ── TABS ──────────────────────────────────────────────── -->
<div style="display:flex;gap:4px;margin-bottom:20px;background:var(--card);
            border:1px solid var(--border);border-radius:10px;padding:5px;">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="?id=<?= $groupId ?>&tab=<?= $key ?>" style="
        flex:1;display:flex;align-items:center;justify-content:center;gap:7px;
        padding:9px 14px;border-radius:7px;font-size:13px;font-weight:500;
        text-decoration:none;transition:background .15s,color .15s;
        <?= $tab === $key
            ? 'background:var(--sidebar);color:#fff;'
            : 'color:var(--text-secondary);' ?>
    " onmouseover="<?= $tab !== $key ? "this.style.background='var(--cream)'" : '' ?>"
       onmouseout="<?= $tab !== $key ? "this.style.background=''" : '' ?>">
        <i class="bi <?= $t['icon'] ?>" style="font-size:14px;"></i>
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── TAB CONTENT: TASKS ─────────────────────────────────── -->
<?php if ($tab === 'tasks'): ?>

<div class="card">
    <?php if (count($tasks) > 0): ?>
    <div style="overflow-x:auto;">
        <table class="ss-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tasks as $t):
                $overdue = strtotime($t['due_date']) < strtotime('today') && $t['status'] === 'pending';
                $pillClass = $t['status']==='done' ? 'pill-done' : ($overdue ? 'pill-overdue' : 'pill-pending');
                $label     = $t['status']==='done' ? 'Completed' : ($overdue ? 'Overdue' : 'Pending');
            ?>
                <tr>
                    <td style="font-weight:500;"><?= htmlspecialchars($t['title']) ?></td>
                    <td style="color:var(--text-secondary);"><?= htmlspecialchars($t['assigned_to_name'] ?? 'Unassigned') ?></td>
                    <td style="<?= $overdue ? 'color:var(--red);font-weight:600;':'' ?>">
                        <?= date('d M Y', strtotime($t['due_date'])) ?>
                    </td>
                    <td><span class="pill <?= $pillClass ?>"><?= $label ?></span></td>
                    <td style="text-align:right;">
                        <?php if ($t['status'] !== 'done'): ?>
                        <form method="POST" action="tasks.php" style="display:inline;">
                            <input type="hidden" name="mark_done_id" value="<?= $t['task_id'] ?>">
                            <button type="submit" style="padding:5px 12px;border-radius:6px;border:1px solid var(--green-border);
                                    background:var(--green-bg);color:var(--green);font-size:12px;font-weight:600;
                                    cursor:pointer;font-family:'DM Sans',sans-serif;">Mark Done</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state"><p>No tasks yet.</p></div>
    <?php endif; ?>
</div>

<!-- ── TAB CONTENT: MEMBERS ──────────────────────────────── -->
<?php elseif ($tab === 'members'): ?>

<div class="card">
    <?php foreach ($members as $m): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:13px 20px;
                border-bottom:1px solid var(--border-soft);">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--copper-dim);
                    border:1px solid var(--copper-border);display:flex;align-items:center;
                    justify-content:center;font-weight:700;font-size:13px;color:var(--copper);flex-shrink:0;">
            <?= strtoupper(substr($m['name'], 0, 1)) ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:13.5px;font-weight:600;"><?= htmlspecialchars($m['name']) ?></div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;">
                <?= ucfirst($m['role']) ?> · Joined <?= date('d M Y', strtotime($m['joined_at'])) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── TAB CONTENT: FILES ────────────────────────────────── -->
<?php elseif ($tab === 'files'): ?>

<div class="card">
    <?php if (count($files) > 0): ?>
    <div style="overflow-x:auto;">
        <table class="ss-table">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Uploaded By</th>
                    <th>Size</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($files as $f): ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars($f['file_path']) ?>" download
                           style="color:var(--copper-dark);font-weight:600;text-decoration:none;font-size:13.5px;"
                           onmouseover="this.style.color='var(--copper)'"
                           onmouseout="this.style.color='var(--copper-dark)'">
                            <?= htmlspecialchars($f['file_name']) ?>
                        </a>
                    </td>
                    <td style="color:var(--text-secondary);"><?= htmlspecialchars($f['uploaded_by_name']) ?></td>
                    <td style="color:var(--text-muted);"><?= round($f['file_size'] / 1024) ?> KB</td>
                    <td style="text-align:right;">
                        <?php if ($f['uploaded_by'] == $_SESSION['user_id']): ?>
                        <form method="POST" action="files.php" style="display:inline;"
                              onsubmit="return confirm('Delete this file?')">
                            <input type="hidden" name="delete_file_id" value="<?= $f['file_id'] ?>">
                            <input type="hidden" name="group_id"       value="<?= $groupId ?>">
                            <button type="submit" style="padding:5px 12px;border-radius:6px;border:1px solid var(--red-border);
                                    background:var(--red-bg);color:var(--red);font-size:12px;font-weight:600;
                                    cursor:pointer;font-family:'DM Sans',sans-serif;">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state"><p>No files uploaded yet.</p></div>
    <?php endif; ?>
</div>

<!-- ── TAB CONTENT: CHAT ─────────────────────────────────── -->
<?php elseif ($tab === 'chat'): ?>

<div class="card" style="overflow:hidden;">
    <!-- Messages -->
    <div id="chatMessages" style="height:420px;overflow-y:auto;padding:18px 20px;background:var(--cream);">
        <?php if (empty($messages)): ?>
            <div style="text-align:center;padding:60px 20px;color:var(--text-muted);font-size:13px;">
                No messages yet. Say hello! 👋
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <div style="margin-bottom:14px;">
                <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:3px;">
                    <span style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($msg['user_name']) ?></span>
                    <span style="font-size:11px;color:var(--text-muted);"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                </div>
                <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.5;">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Input -->
    <div style="padding:14px 18px;border-top:1px solid var(--border);">
        <form method="POST" style="display:flex;gap:10px;">
            <input type="text" name="message" placeholder="Type a message…" required
                   class="ss-input" style="flex:1;">
            <button type="submit" class="btn-primary btn-copper">
                <i class="bi bi-send-fill"></i> Send
            </button>
        </form>
    </div>
</div>

<script>
const chat = document.getElementById('chatMessages');
if (chat) chat.scrollTop = chat.scrollHeight;
</script>

<?php endif; ?>

<?php $page_content = ob_get_clean(); require 'layout.php'; ?>