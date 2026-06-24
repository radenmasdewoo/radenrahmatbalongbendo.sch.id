<?php
/**
 * Konversi gambar (jpg/png/gif) ke WebP. Untuk upload otomatis.
 * Returns path ke file .webp atau false.
 */
function convertImageToWebp($sourcePath) {
    if (!file_exists($sourcePath)) return false;
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $dir = dirname($sourcePath);
    $base = pathinfo($sourcePath, PATHINFO_FILENAME);
    $webpPath = $dir . '/' . $base . '.webp';
    $img = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = @imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $img = @imagecreatefrompng($sourcePath);
            if ($img) {
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            break;
        case 'gif':
            $img = @imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    if (!$img) {
        $blob = @file_get_contents($sourcePath);
        if ($blob) $img = @imagecreatefromstring($blob);
        if ($img && $ext === 'png') {
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }
    }
    if (!$img) return false;
    $ok = imagewebp($img, $webpPath, 85);
    imagedestroy($img);
    return $ok ? $webpPath : false;
}