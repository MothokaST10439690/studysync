<?php
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$fileId = (int)($_GET['id'] ?? 0);
$file = $fileId ? getFileById($fileId) : false;

if (!$file || !isGroupMember((int)$file['group_id'], (int)$_SESSION['user_id'])) {
    http_response_code(404);
    exit('File not found.');
}

$absolutePath = realpath(__DIR__ . $file['file_path']);
$uploadsRoot = realpath(__DIR__ . '/uploads');
if (!$absolutePath || !$uploadsRoot || !str_starts_with($absolutePath, $uploadsRoot . DIRECTORY_SEPARATOR) || !is_file($absolutePath)) {
    http_response_code(404);
    exit('File not found.');
}

$downloadName = str_replace(["\r", "\n", '"'], '', basename($file['file_name']));
$previewTypes = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];
$isPreview = isset($_GET['preview']) && isset($previewTypes[strtolower($file['file_type'])]);
header('Content-Type: ' . ($isPreview ? $previewTypes[strtolower($file['file_type'])] : 'application/octet-stream'));
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: ' . ($isPreview ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
