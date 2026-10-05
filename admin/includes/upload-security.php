<?php

function upload_secure_image(array $file, string $uploadDir, string $prefix = 'image_'): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error' => 'آپلود تصویر با خطا مواجه شد.'
        ];
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return [
            'success' => false,
            'error' => 'فایل آپلودشده معتبر نیست.'
        ];
    }

    $maxSize = 5 * 1024 * 1024;

    if (($file['size'] ?? 0) > $maxSize) {
        return [
            'success' => false,
            'error' => 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.'
        ];
    }

    $imageInfo = @getimagesize($file['tmp_name']);

    if ($imageInfo === false) {
        return [
            'success' => false,
            'error' => 'فایل انتخاب‌شده تصویر معتبر نیست.'
        ];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimeTypes[$mime])) {
        return [
            'success' => false,
            'error' => 'فرمت تصویر فقط باید JPG، PNG یا WEBP باشد.'
        ];
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return [
            'success' => false,
            'error' => 'پوشه آپلود تصویر قابل ایجاد نیست.'
        ];
    }

    $extension = $allowedMimeTypes[$mime];
    $fileName = $prefix . bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => false,
            'error' => 'ذخیره تصویر انجام نشد.'
        ];
    }

    return [
        'success' => true,
        'file_name' => $fileName,
        'path' => $targetPath
    ];
}
