<?php
// config/db_helpers.php
require_once __DIR__ . '/db_connect.php';

// ============================================================
// STATS & DASHBOARD
// ============================================================
function getAdminStats(): array {
    global $pdo;
    $stats = [];
    $stats['total_users']   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $stats['total_groups']  = (int)$pdo->query("SELECT COUNT(*) FROM study_groups")->fetchColumn();
    $stats['total_tasks']   = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $stats['pending_tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
    $stats['overdue_tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status = 'pending'")->fetchColumn();
    $stats['total_files']   = (int)$pdo->query("SELECT COUNT(*) FROM files")->fetchColumn();
    return $stats;
}

// ============================================================
// GROUPS
// ============================================================
function ensureGroupJoinRequestsTable(): void {
    global $pdo;
    static $checked = false;

    if ($checked) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_join_requests (
            request_id int(11) NOT NULL AUTO_INCREMENT,
            group_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            requested_at datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (request_id),
            UNIQUE KEY uq_join_request_group_user (group_id, user_id),
            KEY user_id (user_id),
            CONSTRAINT group_join_requests_ibfk_1 FOREIGN KEY (group_id) REFERENCES study_groups (group_id) ON DELETE CASCADE,
            CONSTRAINT group_join_requests_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $checked = true;
}

function getUserGroups(int $user_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT sg.*,
               (SELECT COUNT(*) FROM group_members WHERE group_id = sg.group_id) AS member_count
        FROM study_groups sg
        JOIN group_members gm ON sg.group_id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY sg.group_name
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getAllPublicGroups(int $user_id): array {
    global $pdo;
    ensureGroupJoinRequestsTable();

    $stmt = $pdo->prepare("
        SELECT sg.*,
               (SELECT COUNT(*) FROM group_members WHERE group_id = sg.group_id) AS member_count,
               gjr.request_id AS pending_request_id
        FROM study_groups sg
        LEFT JOIN group_join_requests gjr
               ON gjr.group_id = sg.group_id
              AND gjr.user_id = ?
        WHERE sg.is_private = 0
          AND sg.group_id NOT IN (
              SELECT group_id FROM group_members WHERE user_id = ?
          )
        ORDER BY sg.group_name
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll();
}

function createGroup(string $group_name, string $description, int $is_private, int $created_by): int|false {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO study_groups (group_name, description, is_private, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$group_name, $description, $is_private, $created_by]);
        $group_id = (int)$pdo->lastInsertId();
        // Creator automatically becomes first member
        $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)")
            ->execute([$group_id, $created_by]);
        $pdo->commit();
        return $group_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('createGroup failed: ' . $e->getMessage());
        return false;
    }
}

//to delete a group from the database
function deleteGroup(int $groupId): bool
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM study_groups
        WHERE group_id = ?
    ");

    return $stmt->execute([$groupId]);
}


function isAdmin(): bool
{
    return isset($_SESSION['user_role']) &&
           $_SESSION['user_role'] === 'admin';
}

function canManageGroup(int $groupId, int $userId): bool
{
    return isAdmin() || isGroupCreator($groupId, $userId);
}

function isGroupMember(int $group_id, int $user_id): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 1
        FROM group_members
        WHERE group_id = ?
          AND user_id = ?
    ");
    $stmt->execute([$group_id, $user_id]);
    return (bool)$stmt->fetch();
}

//to chech creation of the group and to add the creator as a member of the group
function isGroupCreator($groupId, $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 1
        FROM study_groups
        WHERE group_id = ?
        AND created_by = ?
    ");

    $stmt->execute([$groupId, $userId]);

    return (bool)$stmt->fetch();
}




function joinGroup(int $group_id, int $user_id): bool {
    global $pdo;
    // INSERT IGNORE silently skips if already a member (unique key on group_id + user_id)
    $stmt = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
    return $stmt->execute([$group_id, $user_id]);
}

function requestJoinGroup(int $group_id, int $user_id): bool {
    global $pdo;
    ensureGroupJoinRequestsTable();

    if (isGroupMember($group_id, $user_id)) return false;

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO group_join_requests (group_id, user_id)
        VALUES (?, ?)
    ");
    $ok = $stmt->execute([$group_id, $user_id]);

    if ($ok && $stmt->rowCount() > 0) {
        $details = $pdo->prepare("
            SELECT sg.group_name, sg.created_by, u.name AS requester_name
            FROM study_groups sg
            JOIN users u ON u.user_id = ?
            WHERE sg.group_id = ?
        ");
        $details->execute([$user_id, $group_id]);
        if ($row = $details->fetch()) {
            createNotification(
                (int)$row['created_by'],
                'join_request',
                'New group join request',
                $row['requester_name'] . ' wants to join ' . $row['group_name'] . '.',
                'group.php?id=' . $group_id . '&tab=members'
            );
        }
    }

    return $ok;
}

function getPendingJoinRequests(int $group_id): array {
    global $pdo;
    ensureGroupJoinRequestsTable();

    $stmt = $pdo->prepare("
        SELECT gjr.request_id, gjr.group_id, gjr.user_id, gjr.requested_at,
               u.name, u.email, u.role
        FROM group_join_requests gjr
        JOIN users u ON gjr.user_id = u.user_id
        WHERE gjr.group_id = ?
        ORDER BY gjr.requested_at ASC
    ");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll();
}

function approveJoinRequest(int $request_id, int $manager_user_id): bool {
    global $pdo;
    ensureGroupJoinRequestsTable();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT group_id, user_id
            FROM group_join_requests
            WHERE request_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request || !canManageGroup((int)$request['group_id'], $manager_user_id)) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare("
            INSERT IGNORE INTO group_members (group_id, user_id)
            VALUES (?, ?)
        ")->execute([(int)$request['group_id'], (int)$request['user_id']]);

        $pdo->prepare("DELETE FROM group_join_requests WHERE request_id = ?")
            ->execute([$request_id]);

        $groupStmt = $pdo->prepare("SELECT group_name FROM study_groups WHERE group_id = ?");
        $groupStmt->execute([(int)$request['group_id']]);
        $groupName = (string)$groupStmt->fetchColumn();

        $pdo->commit();
        createNotification(
            (int)$request['user_id'],
            'group_approved',
            'Group request approved',
            'You can now collaborate in ' . $groupName . '.',
            'group.php?id=' . (int)$request['group_id']
        );
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('approveJoinRequest failed: ' . $e->getMessage());
        return false;
    }
}

function rejectJoinRequest(int $request_id, int $manager_user_id): bool {
    global $pdo;
    ensureGroupJoinRequestsTable();

    $stmt = $pdo->prepare("
        SELECT group_id, user_id
        FROM group_join_requests
        WHERE request_id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || !canManageGroup((int)$request['group_id'], $manager_user_id)) {
        return false;
    }

    $groupStmt = $pdo->prepare("SELECT group_name FROM study_groups WHERE group_id = ?");
    $groupStmt->execute([(int)$request['group_id']]);
    $groupName = (string)$groupStmt->fetchColumn();

    $delete = $pdo->prepare("DELETE FROM group_join_requests WHERE request_id = ?");
    $ok = $delete->execute([$request_id]);
    if ($ok) {
        createNotification(
            (int)$request['user_id'],
            'group_declined',
            'Group request update',
            'Your request to join ' . $groupName . ' was declined.',
            'groups.php'
        );
    }
    return $ok;
}

function removeGroupMember(int $group_id, int $member_user_id, int $manager_user_id): bool {
    global $pdo;

    if (!canManageGroup($group_id, $manager_user_id) || isGroupCreator($group_id, $member_user_id)) {
        return false;
    }

    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = ?
          AND user_id = ?
    ");
    return $stmt->execute([$group_id, $member_user_id]);
}

function getGroupMembers(int $group_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name, u.email, u.role, gm.joined_at
        FROM group_members gm
        JOIN users u ON gm.user_id = u.user_id
        WHERE gm.group_id = ?
        ORDER BY gm.joined_at ASC
    ");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll();
}

// ============================================================
// TASKS
// ============================================================
function getTasksByUser(int $user_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*, sg.group_name
        FROM tasks t
        JOIN study_groups sg ON t.group_id = sg.group_id
        WHERE t.assigned_to = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getGroupTasks(int $group_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*, u.name AS assigned_to_name, sg.group_name
        FROM tasks t
        LEFT JOIN users u        ON t.assigned_to = u.user_id
        LEFT JOIN study_groups sg ON t.group_id   = sg.group_id
        WHERE t.group_id = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll();
}

// to delete a task from the database
function deleteTask($taskId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM tasks
        WHERE task_id = ?
    ");

    return $stmt->execute([$taskId]);
}

function getAllUserTasks(int $userId): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            t.*,
            sg.group_name,
            u.name AS assigned_to_name
        FROM tasks t
        INNER JOIN group_members gm
            ON gm.group_id = t.group_id
        LEFT JOIN study_groups sg
            ON sg.group_id = t.group_id
        LEFT JOIN users u
            ON u.user_id = t.assigned_to
        WHERE gm.user_id = ?
        ORDER BY t.due_date ASC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createTask(int $group_id, int $assigned_to, string $title, string $description, string $due_date): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO tasks (group_id, assigned_to, title, description, due_date, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    return $stmt->execute([$group_id, $assigned_to, $title, $description, $due_date]);
}

function updateTaskStatus(int $task_id, string $status): bool {
    global $pdo;
    if (!in_array($status, ['pending', 'done'])) return false;
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
    return $stmt->execute([$status, $task_id]);
}

function getOverdueTasks(): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*, sg.group_name, u.name AS assigned_to_name
        FROM tasks t
        JOIN study_groups sg ON t.group_id    = sg.group_id
        JOIN users u         ON t.assigned_to = u.user_id
        WHERE t.due_date < CURDATE() AND t.status = 'pending'
        ORDER BY t.due_date ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// FIX #9: New function — replaces direct $pdo query in calendar.php
function getCalendarTasks(array $groupIds, int $month, int $year): array {
    global $pdo;
    if (empty($groupIds)) return [];

    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("
        SELECT t.task_id, t.title, t.due_date, t.status, sg.group_name
        FROM tasks t
        JOIN study_groups sg ON t.group_id = sg.group_id
        WHERE t.group_id IN ($placeholders)
          AND MONTH(t.due_date) = ?
          AND YEAR(t.due_date)  = ?
        ORDER BY t.due_date ASC
    ");
    $params = array_merge($groupIds, [$month, $year]);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============================================================
// FILES
// ============================================================
function getGroupFiles(int $group_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT f.*, u.name AS uploaded_by_name
        FROM files f
        JOIN users u ON f.uploaded_by = u.user_id
        WHERE f.group_id = ?
        ORDER BY f.uploaded_at DESC
    ");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll();
}

function uploadFile(int $group_id, int $uploaded_by, string $file_name, string $file_path, string $file_type, int $file_size): int|false {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO files (group_id, uploaded_by, file_name, file_path, file_type, file_size)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt->execute([$group_id, $uploaded_by, $file_name, $file_path, $file_type, $file_size])) {
        return false;
    }
    return (int)$pdo->lastInsertId();
}

function getFileById(int $file_id): array|false {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT f.*, u.name AS uploaded_by_name
        FROM files f
        JOIN users u ON u.user_id = f.uploaded_by
        WHERE f.file_id = ?
        LIMIT 1
    ");
    $stmt->execute([$file_id]);
    return $stmt->fetch();
}

function deleteFile(int $file_id, int $user_id): bool {
    global $pdo;
    // Only the uploader can delete their own file
    $stmt = $pdo->prepare("DELETE FROM files WHERE file_id = ? AND uploaded_by = ?");
    return $stmt->execute([$file_id, $user_id]);
}

// ============================================================
// CHAT (MESSAGES)
// ============================================================
function getMessages(int $group_id, int $since_id = 0): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.id, m.user_id, m.message, m.created_at,
               u.name AS user_name, u.avatar_path AS user_avatar,
               f.file_id, f.file_name, f.file_type, f.file_size
        FROM messages m
        JOIN users u ON m.user_id = u.user_id
        LEFT JOIN files f ON m.file_id = f.file_id
        WHERE m.group_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$group_id, $since_id]);
    return $stmt->fetchAll();
}

function sendMessage(int $group_id, int $user_id, string $message, ?int $file_id = null): bool {
    global $pdo;
    if (empty(trim($message)) && !$file_id) return false;
    $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message, file_id) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$group_id, $user_id, trim($message), $file_id]);
}

// to delete a message from the database
function deleteMessage($messageId, $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM messages
        WHERE id = ?
        AND user_id = ?
    ");

    return $stmt->execute([
        $messageId,
        $userId
    ]);
}

// to clear all messages from a group
function clearGroupChat($groupId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM messages
        WHERE group_id = ?
    ");

    return $stmt->execute([$groupId]);
}

// ============================================================
// PROFILES
// ============================================================
function getUserProfile(int $user_id): array|false {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name, u.email, u.role, u.bio, u.institution,
               u.course, u.avatar_path, u.created_at,
               (SELECT COUNT(*) FROM group_members gm WHERE gm.user_id = u.user_id) AS group_count,
               (SELECT COUNT(*) FROM files f WHERE f.uploaded_by = u.user_id) AS file_count,
               (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.user_id AND t.status = 'done') AS completed_task_count
        FROM users u
        WHERE u.user_id = ? AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function updateUserProfile(int $user_id, string $name, string $institution, string $course, string $bio): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE users
        SET name = ?, institution = ?, course = ?, bio = ?
        WHERE user_id = ?
    ");
    return $stmt->execute([$name, $institution ?: null, $course ?: null, $bio ?: null, $user_id]);
}

function updateUserAvatar(int $user_id, ?string $avatar_path): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?");
    return $stmt->execute([$avatar_path, $user_id]);
}

// ============================================================
// GROUP INVITATIONS
// ============================================================
function createGroupInvitation(int $group_id, int $invited_by, string $email): array {
    global $pdo;
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !canManageGroup($group_id, $invited_by)) {
        return ['ok' => false, 'error' => 'Enter a valid email address.'];
    }

    $memberCheck = $pdo->prepare("
        SELECT u.user_id
        FROM users u
        JOIN group_members gm ON gm.user_id = u.user_id
        WHERE gm.group_id = ? AND LOWER(u.email) = ?
        LIMIT 1
    ");
    $memberCheck->execute([$group_id, $email]);
    if ($memberCheck->fetch()) {
        return ['ok' => false, 'error' => 'That person is already a group member.'];
    }

    $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = ? AND is_active = 1 LIMIT 1");
    $userStmt->execute([$email]);
    $invitedUserId = $userStmt->fetchColumn();
    $token = bin2hex(random_bytes(32));

    $pdo->prepare("
        UPDATE group_invitations
        SET status = 'declined'
        WHERE group_id = ? AND LOWER(email) = ? AND status = 'pending'
    ")->execute([$group_id, $email]);

    $stmt = $pdo->prepare("
        INSERT INTO group_invitations
            (group_id, invited_by, invited_user_id, email, token, expires_at)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
    ");
    $ok = $stmt->execute([$group_id, $invited_by, $invitedUserId ?: null, $email, $token]);

    if ($ok && $invitedUserId) {
        $groupStmt = $pdo->prepare("SELECT group_name FROM study_groups WHERE group_id = ?");
        $groupStmt->execute([$group_id]);
        $groupName = (string)$groupStmt->fetchColumn();
        createNotification(
            (int)$invitedUserId,
            'group_invite',
            'You were invited to a study group',
            'Open the invitation to join ' . $groupName . '.',
            'invite.php?token=' . $token
        );
    }

    return $ok
        ? ['ok' => true, 'token' => $token, 'registered' => (bool)$invitedUserId]
        : ['ok' => false, 'error' => 'The invitation could not be created.'];
}

function getGroupInvitation(string $token): array|false {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT gi.*, sg.group_name, sg.description,
               inviter.name AS inviter_name
        FROM group_invitations gi
        JOIN study_groups sg ON sg.group_id = gi.group_id
        JOIN users inviter ON inviter.user_id = gi.invited_by
        WHERE gi.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function acceptGroupInvitation(string $token, int $user_id): array {
    global $pdo;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            SELECT gi.*, u.email AS accepting_email, sg.group_name
            FROM group_invitations gi
            JOIN users u ON u.user_id = ?
            JOIN study_groups sg ON sg.group_id = gi.group_id
            WHERE gi.token = ?
            FOR UPDATE
        ");
        $stmt->execute([$user_id, $token]);
        $invite = $stmt->fetch();

        $isCorrectUser = $invite && (
            ((int)($invite['invited_user_id'] ?? 0) === $user_id) ||
            strtolower($invite['email']) === strtolower($invite['accepting_email'])
        );

        if (!$invite || $invite['status'] !== 'pending' || strtotime($invite['expires_at']) < time() || !$isCorrectUser) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'This invitation is invalid, expired, or belongs to another account.'];
        }

        $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)")
            ->execute([(int)$invite['group_id'], $user_id]);
        $pdo->prepare("UPDATE group_invitations SET status = 'accepted', invited_user_id = ? WHERE invitation_id = ?")
            ->execute([$user_id, (int)$invite['invitation_id']]);
        $pdo->commit();

        createNotification(
            (int)$invite['invited_by'],
            'invite_accepted',
            'Group invitation accepted',
            'A new member joined ' . $invite['group_name'] . ' through your invitation.',
            'group.php?id=' . (int)$invite['group_id'] . '&tab=members'
        );

        return ['ok' => true, 'group_id' => (int)$invite['group_id']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('acceptGroupInvitation failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'The invitation could not be accepted.'];
    }
}

// ============================================================
// NOTIFICATIONS
// ============================================================
function createNotification(int $user_id, string $type, string $title, ?string $body = null, ?string $link = null): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, body, link)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $type, $title, $body, $link]);
}

function getNotifications(int $user_id, int $limit = 50): array {
    global $pdo;
    $limit = max(1, min($limit, 100));
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT {$limit}
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getUnreadNotificationCount(int $user_id): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function markNotificationRead(int $notification_id, int $user_id): ?string {
    global $pdo;
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    $link = $stmt->fetchColumn();
    if ($link === false) return null;

    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?")
        ->execute([$notification_id, $user_id]);
    return $link ?: 'notifications.php';
}

function markAllNotificationsRead(int $user_id): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

// ============================================================
// PASSWORD RESET
// ============================================================
function createPasswordResetToken(string $email): ?string {
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1 LIMIT 1");
    $stmt->execute([trim($email)]);
    $userId = $stmt->fetchColumn();
    if (!$userId) return null;

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()")
        ->execute([(int)$userId]);
    $insert = $pdo->prepare("
        INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))
    ");
    return $insert->execute([(int)$userId, $tokenHash]) ? $rawToken : null;
}

function resetPasswordWithToken(string $rawToken, string $newPassword): bool {
    global $pdo;
    $tokenHash = hash('sha256', $rawToken);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            SELECT reset_id, user_id
            FROM password_reset_tokens
            WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
            FOR UPDATE
        ");
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();
        if (!$reset) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$reset['user_id']]);
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE reset_id = ?")
            ->execute([(int)$reset['reset_id']]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('resetPasswordWithToken failed: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
// ADMIN
// ============================================================
function getAllUsers(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT user_id, name, email, role, is_active, created_at FROM users ORDER BY user_id");
    return $stmt->fetchAll();
}
