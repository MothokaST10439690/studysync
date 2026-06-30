<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['mark_all_read'])) {
        markAllNotificationsRead((int)$_SESSION['user_id']);
        header('Location: notifications.php');
        exit;
    }
    if (isset($_POST['open_notification_id'])) {
        $link = markNotificationRead((int)$_POST['open_notification_id'], (int)$_SESSION['user_id']);
        header('Location: ' . safeLocalRedirect($link, 'notifications.php'));
        exit;
    }
}

$notifications = getNotifications((int)$_SESSION['user_id']);
$unreadCount = getUnreadNotificationCount((int)$_SESSION['user_id']);
$iconMap = [
    'group_invite' => 'bi-person-plus-fill',
    'invite_accepted' => 'bi-person-check-fill',
    'join_request' => 'bi-person-raised-hand',
    'group_approved' => 'bi-check-circle-fill',
    'group_declined' => 'bi-info-circle-fill',
    'task' => 'bi-check2-square',
];
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
    <div>
        <div style="font-family:'Playfair Display',serif;font-size:27px;font-weight:800;">Notifications</div>
        <div style="font-size:13px;color:var(--text-secondary);margin-top:3px;"><?= $unreadCount ?> unread update<?= $unreadCount === 1 ? '' : 's' ?></div>
    </div>
    <?php if ($unreadCount): ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
        <button class="btn-ghost" type="submit" name="mark_all_read"><i class="bi bi-check2-all"></i> Mark all read</button>
    </form>
    <?php endif; ?>
</div>

<div class="card" style="overflow:hidden;">
    <?php if (empty($notifications)): ?>
        <div class="empty-state" style="padding:70px 20px;"><i class="bi bi-bell-slash" style="font-size:30px;display:block;margin-bottom:10px;"></i><p>You are all caught up.</p></div>
    <?php else: ?>
        <?php foreach ($notifications as $notification):
            $icon = $iconMap[$notification['type']] ?? 'bi-bell-fill';
        ?>
        <form method="POST" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="open_notification_id" value="<?= (int)$notification['notification_id'] ?>">
            <button type="submit" style="width:100%;border:0;border-bottom:1px solid var(--border-soft);background:<?= $notification['is_read'] ? '#fff' : '#fdf8f2' ?>;padding:16px 19px;display:flex;gap:13px;text-align:left;cursor:pointer;font-family:'DM Sans',sans-serif;">
                <span style="width:39px;height:39px;border-radius:11px;background:var(--copper-dim);border:1px solid var(--copper-border);display:flex;align-items:center;justify-content:center;color:var(--copper);font-size:17px;flex-shrink:0;"><i class="bi <?= $icon ?>"></i></span>
                <span style="min-width:0;flex:1;">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <strong style="font-size:13.5px;color:var(--text-primary);"><?= htmlspecialchars($notification['title']) ?></strong>
                        <?php if (!$notification['is_read']): ?><span style="width:7px;height:7px;border-radius:50%;background:var(--copper);"></span><?php endif; ?>
                    </span>
                    <?php if ($notification['body']): ?><span style="display:block;font-size:12.5px;color:var(--text-secondary);margin-top:3px;line-height:1.5;"><?= htmlspecialchars($notification['body']) ?></span><?php endif; ?>
                    <small style="display:block;color:var(--text-muted);margin-top:5px;"><?= date('d M Y, H:i', strtotime($notification['created_at'])) ?></small>
                </span>
                <i class="bi bi-chevron-right" style="color:var(--text-muted);align-self:center;"></i>
            </button>
        </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $page_content = ob_get_clean(); require 'layout.php'; ?>
