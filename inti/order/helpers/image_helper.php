<?php
/**
 * Upload + convert image to WEBP + resize
 * Return: file name (string) atau null jika gagal
 */

function uploadImageWebp(
    array $file,
    string $targetDir,
    int $maxWidth = 800,
    int $maxHeight = 800,
    int $quality = 80
): ?string {

    if (
        empty($file['name']) ||
        $file['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        return null;
    }

    $mime = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mime, $allowed, true)) {
        return null;
    }

    switch ($mime) {
        case 'image/jpeg':
            $srcImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $srcImage = imagecreatefrompng($file['tmp_name']);
            imagealphablending($srcImage, true);
            imagesavealpha($srcImage, true);
            break;
        case 'image/webp':
            $srcImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return null;
    }

    if (!$srcImage) {
        return null;
    }

    $srcWidth  = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);

    $ratio = min(
        $maxWidth / $srcWidth,
        $maxHeight / $srcHeight,
        1
    );

    $newWidth  = (int)($srcWidth * $ratio);
    $newHeight = (int)($srcHeight * $ratio);

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    imagealphablending($dstImage, false);
    imagesavealpha($dstImage, true);

    imagecopyresampled(
        $dstImage,
        $srcImage,
        0, 0, 0, 0,
        $newWidth,
        $newHeight,
        $srcWidth,
        $srcHeight
    );

    $name = pathinfo($file['name'], PATHINFO_FILENAME);
    $cleanName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($name));
    $fileName = time() . '_' . $cleanName . '.webp';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = rtrim($targetDir, '/') . '/' . $fileName;

    $saved = imagewebp($dstImage, $targetPath, $quality);

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $saved ? $fileName : null;
}
