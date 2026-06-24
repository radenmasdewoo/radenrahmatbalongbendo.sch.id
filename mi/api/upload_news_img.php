<?php
/**
 * Upload gambar berita (admin): terima base64 atau file, simpan ke api/uploads/, return URL relatif.
 * Sinkronisasi Instagram tidak diubah; hanya dipakai saat admin mengubah gambar berita.
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$urlPrefix = 'api/uploads/';

function mi_news_convert_to_webp($uploadDir, $name, $urlPrefix) {
    $path = $uploadDir . $name;
    if (!file_exists($path)) return null;
    $absPath = realpath($path) ?: $path;
    $helper = __DIR__ . '/../../api/image_to_webp.php';
    if (!file_exists($helper)) return $urlPrefix . $name;
    require_once $helper;
    $webpPath = convertImageToWebp($absPath);
    if (!$webpPath) return $urlPrefix . $name;
    @unlink($absPath);
    return $urlPrefix . pathinfo($webpPath, PATHINFO_BASENAME);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$imgUrl = null;

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Tipe file tidak diizinkan']);
        exit;
    }
    $ext = (array_search($mime, ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp']) ?: 'jpg');
    $name = 'news_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $name)) {
        $imgUrl = mi_news_convert_to_webp($uploadDir, $name, $urlPrefix);
    }
}

if (!$imgUrl && isset($_POST['image']) && is_string($_POST['image'])) {
    $data = $_POST['image'];
    if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $data, $m)) {
        $ext = strtolower($m[1]);
        if ($ext === 'jpeg') $ext = 'jpg';
        $raw = base64_decode($m[2], true);
        if ($raw !== false && strlen($raw) > 0 && strlen($raw) < 10 * 1024 * 1024) {
            $name = 'news_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
            if (file_put_contents($uploadDir . $name, $raw)) {
                $imgUrl = mi_news_convert_to_webp($uploadDir, $name, $urlPrefix);
            }
        }
    }
}

if (!$imgUrl) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (is_array($input) && isset($input['image']) && is_string($input['image'])) {
        $data = $input['image'];
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $data, $m)) {
            $ext = strtolower($m[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
            $raw = base64_decode($m[2], true);
            if ($raw !== false && strlen($raw) > 0 && strlen($raw) < 10 * 1024 * 1024) {
                $name = 'news_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                if (file_put_contents($uploadDir . $name, $raw)) {
                    $imgUrl = mi_news_convert_to_webp($uploadDir, $name, $urlPrefix);
                }
            }
        }
    }
}

if ($imgUrl) {
    echo json_encode(['success' => true, 'url' => $imgUrl]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Gagal upload atau data tidak valid']);
}
