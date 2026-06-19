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
    if (isGroupMember($group_id, $user_id)) return false;

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO group_join_requests (group_id, user_id)
        VALUES (?, ?)
    ");
    return $stmt->execute([$group_id, $user_id]);
}

function getPendingJoinRequests(int $group_id): array {
    global $pdo;
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

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('approveJoinRequest failed: ' . $e->getMessage());
        return false;
    }
}

function rejectJoinRequest(int $request_id, int $manager_user_id): bool {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT group_id
        FROM group_join_requests
        WHERE request_id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || !canManageGroup((int)$request['group_id'], $manager_user_id)) {
        return false;
    }

    $delete = $pdo->prepare("DELETE FROM group_join_requests WHERE request_id = ?");
    return $delete->execute([$request_id]);
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

function uploadFile(int $group_id, int $uploaded_by, string $file_name, string $file_path, string $file_type, int $file_size): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO files (group_id, uploaded_by, file_name, file_path, file_type, file_size)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$group_id, $uploaded_by, $file_name, $file_path, $file_type, $file_size]);
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
        SELECT m.id, m.message, m.created_at, u.name AS user_name
        FROM messages m
        JOIN users u ON m.user_id = u.user_id
        WHERE m.group_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$group_id, $since_id]);
    return $stmt->fetchAll();
}

function sendMessage(int $group_id, int $user_id, string $message): bool {
    global $pdo;
    if (empty(trim($message))) return false;
    $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (?, ?, ?)");
    return $stmt->execute([$group_id, $user_id, $message]);
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
// ADMIN
// ============================================================
function getAllUsers(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT user_id, name, email, role, is_active, created_at FROM users ORDER BY user_id");
    return $stmt->fetchAll();
}
