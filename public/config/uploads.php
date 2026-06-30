<?php

/**
 * Validate and store a user upload beneath public/uploads.
 *
 * @return array{ok:bool,error?:string,file_name?:string,file_path?:string,file_type?:string,file_size?:int}
 */
function storeUploadedFile(array $file, string $category = 'group-files'): array
{
    $rules = [
        'group-files' => [
            'max' => 100 * 1024 * 1024,
            'extensions' => ['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp'],
        ],
        'avatars' => [
            'max' => 5 * 1024 * 1024,
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
        ],
    ];

    if (!isset($rules[$category])) {
        return ['ok' => false, 'error' => 'Unknown upload category.'];
    }

    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        $limit = (int)round($rules[$category]['max'] / 1024 / 1024);
        return ['ok' => false, 'error' => "The file is too large. The maximum size is {$limit} MB."];
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The upload did not complete. Please try again.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $rules[$category]['max']) {
        $limit = (int)round($rules[$category]['max'] / 1024 / 1024);
        return ['ok' => false, 'error' => "Files must be no larger than {$limit} MB."];
    }

    $originalName = basename((string)($file['name'] ?? 'upload'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $rules[$category]['extensions'], true)) {
        return ['ok' => false, 'error' => 'That file type is not supported.'];
    }

    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    if (in_array($extension, $imageExtensions, true) && @getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'The selected image is not valid.'];
    }

    $uploadDir = dirname(__DIR__) . '/uploads/' . $category;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'Upload storage is unavailable.'];
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'The file could not be saved.'];
    }

    return [
        'ok' => true,
        'file_name' => $originalName,
        'file_path' => '/uploads/' . $category . '/' . $storedName,
        'file_type' => $extension,
        'file_size' => $size,
    ];
}

function removeStoredUpload(?string $filePath): void
{
    if (!$filePath || !str_starts_with($filePath, '/uploads/')) {
        return;
    }

    $uploadsRoot = realpath(dirname(__DIR__) . '/uploads');
    $target = realpath(dirname(__DIR__) . $filePath);
    if ($uploadsRoot && $target && str_starts_with($target, $uploadsRoot . DIRECTORY_SEPARATOR) && is_file($target)) {
        @unlink($target);
    }
}
