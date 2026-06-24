<?php
/**
 * Manual Post Adder - MI Raden Rahmat
 * Untuk menambahkan postingan Instagram yang tidak terambil oleh auto-sync
 * 
 * Usage:
 *   curl -X POST "https://radenrahmatbalongbendo.sch.id/mi/api/add_manual_post.php?key=radenrahmat_sync_2026" \
 *   -H "Content-Type: application/json" \
 *   -d '{"shortcode":"ABC123xyz","title":"Judul Postingan","img":"https://link-gambar.jpg"}'
 */

header('Content-Type: application/json; charset=utf-8');

$dataJson = __DIR__ . '/news_mi.json';
$cacheDir = __DIR__ . '/ig_cache/';
$configFile = __DIR__ . '/insta_config_mi.json';

// Verify sync key
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$syncKey = $config['sync_key'] ?? 'radenrahmat_sync_2026';

// Support CLI arguments
if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

if (!isset($_GET['key']) || $_GET['key'] !== $syncKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['shortcode', 'title'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

$shortcode = trim($input['shortcode']);
$title = trim($input['title']);
$imgUrl = trim($input['img'] ?? '');
$link = trim($input['link'] ?? "https://www.instagram.com/p/{$shortcode}/");
$isVideo = $input['is_video'] ?? false;
$videoUrl = trim($input['video'] ?? '');

// Load existing data
$existingData = file_exists($dataJson) ? json_decode(file_get_contents($dataJson), true) : [];
if (!is_array($existingData)) $existingData = [];

// Check if shortcode already exists
foreach ($existingData as $i => $item) {
    if (($item['shortcode'] ?? '') === $shortcode) {
        // Update existing
        $existingData[$i] = [
            'shortcode' => $shortcode,
            'title' => $title,
            'excerpt' => $input['excerpt'] ?? mb_substr($title, 0, 200),
            'img' => $input['img_path'] ?? "api/ig_cache/{$shortcode}.jpg",
            'video' => $videoUrl,
            'is_video' => $isVideo,
            'date' => $input['date'] ?? date('j M Y'),
            'timestamp' => $input['timestamp'] ?? time(),
            'link' => $link,
            'author' => 'miradenrahmatbalongbendo'
        ];
        echo json_encode(['success' => true, 'action' => 'updated', 'shortcode' => $shortcode]);
        file_put_contents($dataJson, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        exit;
    }
}

// Download image if URL provided
$imgPath = "api/ig_cache/{$shortcode}.jpg";
$localImgPath = $cacheDir . "{$shortcode}.jpg";

if ($imgUrl && !file_exists($localImgPath)) {
    $ch = curl_init($imgUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER => 'https://www.instagram.com/',
        CURLOPT_TIMEOUT => 30
    ]);
    $imgData = curl_exec($ch);
    curl_close($ch);
    
    if ($imgData && strlen($imgData) > 1000) {
        file_put_contents($localImgPath, $imgData);
    }
}

// Add new post at the beginning (newest first)
$newPost = [
    'shortcode' => $shortcode,
    'title' => $title,
    'excerpt' => $input['excerpt'] ?? mb_substr($title, 0, 200),
    'img' => file_exists($localImgPath) ? $imgPath : ($input['img_path'] ?? ''),
    'video' => $videoUrl,
    'is_video' => $isVideo,
    'date' => $input['date'] ?? date('j M Y'),
    'timestamp' => $input['timestamp'] ?? time(),
    'link' => $link,
    'author' => 'miradenrahmatbalongbendo'
];

array_unshift($existingData, $newPost);

// Limit to config limit
$configLimit = $config['posts_limit'] ?? 12;
$existingData = array_slice($existingData, 0, $configLimit);

// Sort by timestamp
usort($existingData, function($a, $b) {
    return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
});

// Save
file_put_contents($dataJson, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode([
    'success' => true, 
    'action' => 'added', 
    'shortcode' => $shortcode,
    'total_posts' => count($existingData)
]);
