<?php
/**
 * Instagram sync via api-anything (no RSS).
 * Fetches posts from api-anything instagram getPosts, parses dates from
 * accessibility_caption, downloads images via curl with browser headers,
 * writes news.json with correct per-post dates.
 */

$configFile = __DIR__ . '/insta_config.json';
$newsFile   = __DIR__ . '/news.json';
$cacheDir   = __DIR__ . '/ig_cache';
$apiAnything = '/usr/local/bin/node /opt/node20/lib/node_modules/api-anything/dist/cli.js';

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'insta_config.json tidak ditemukan']);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);
if (!is_array($config)) {
    http_response_code(500);
    echo json_encode(['error' => 'Konfigurasi tidak valid']);
    exit;
}

$accounts = $config['accounts'] ?? [];
$postsPerAcc = max(1, (int)($config['posts_per_account'] ?? 5));
$maxPosts = max(5, (int)($config['max_posts'] ?? 15));

if (empty($accounts)) {
    http_response_code(500);
    echo json_encode(['error' => 'Belum ada accounts yang dikonfigurasi']);
    exit;
}

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

function callApiAnything(string $username): array {
    global $apiAnything;
    $cmd = escapeshellcmd($apiAnything) . ' call instagram getPosts username=' . escapeshellarg($username);
    $output = shell_exec($cmd . ' 2>&1');
    $decoded = json_decode($output, true);
    if (!$decoded || !isset($decoded['data']) || !is_array($decoded['data'])) {
        return [];
    }
    return $decoded['data'];
}

function parseIgDate(string $caption): string {
    // accessibility_caption: "Video by ACCOUNT on November 03, 2026. May be ..."
    if (preg_match('/\bon\s+([A-Z][a-z]+)\s+(\d{1,2}),\s+(\d{4})\b/', $caption, $m)) {
        $ts = strtotime($m[1] . ' ' . $m[2] . ' ' . $m[3]);
        if ($ts) return date('j M Y', $ts);
    }
    return date('j M Y');
}

function parseIgTimestamp(string $caption): int {
    if (preg_match('/\bon\s+([A-Z][a-z]+)\s+(\d{1,2}),\s+(\d{4})\b/', $caption, $m)) {
        $ts = strtotime($m[1] . ' ' . $m[2] . ' ' . $m[3]);
        if ($ts) return $ts;
    }
    return time();
}

function downloadImage(string $url, string $dest): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_REFERER => 'https://www.instagram.com/',
        CURLOPT_HTTPHEADER => [
            'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'sec-fetch-dest: image',
            'sec-fetch-mode: no-cors',
            'sec-fetch-site: cross-site',
        ],
    ]);
    $body = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $body !== false && strlen($body) > 1000) {
        file_put_contents($dest, $body);
        return true;
    }
    return false;
}

function n($arr, $key, $default = '') {
    return isset($arr[$key]) ? $arr[$key] : $default;
}

$allItems = [];
$details = [];

foreach ($accounts as $acc) {
    $handle = n($acc, 'handle');
    if (!$handle) continue;

    $posts = callApiAnything($handle);
    $src = 'api-anything';
    $imgCount = 0;

    foreach ($posts as $post) {
        $code = n($post, 'node.code');
        $caption = n($post, 'node.caption.text', '');
        $accCap = n($post, 'node.accessibility_caption', '');
        $media = (int)n($post, 'node.media_type', 1);
        $uri = n($post, 'node.display_uri', '');

        if (!$code) continue;

        $dateStr = parseIgDate($accCap);
        $timestamp = parseIgTimestamp($accCap);
        $isVideo = in_array($media, [2, 8], true);

        $imgWebPath = '';
        $imgLocal = $cacheDir . '/' . $code . '.jpg';
        if ($uri && downloadImage($uri, $imgLocal)) {
            $imgWebPath = '/api/ig_cache/' . $code . '.jpg';
            $imgCount++;
        }

        $plainCaption = strip_tags($caption);
        $allItems[] = [
            'shortcode' => $code,
            'title' => mb_substr(trim($caption) ?: 'Post Instagram ' . $handle, 0, 90),
            'excerpt' => mb_substr(trim($plainCaption) ?: 'Post Instagram ' . $handle, 0, 200),
            'caption' => mb_substr(trim($plainCaption) ?: 'Post Instagram ' . $handle, 0, 500),
            'img' => $imgWebPath,
            'video' => $isVideo ? $uri : '',
            'is_video' => $isVideo,
            'media_type' => $isVideo ? 'video' : 'image',
            'date' => $dateStr,
            'timestamp' => $timestamp,
            'link' => 'https://www.instagram.com/p/' . $code . '/',
            'author' => $handle,
        ];
    }

    $details[] = ['handle' => $handle, 'source' => $src, 'images_downloaded' => $imgCount, 'posts_fetched' => count($posts)];
}

// Dedup by shortcode
$seen = [];
$final = [];
foreach ($allItems as $item) {
    if (in_array($item['shortcode'], $seen)) continue;
    $final[] = $item;
    $seen[] = $item['shortcode'];
}

// Sort by timestamp descending (newest first)
usort($final, function ($a, $b) {
    return (int)($b['timestamp'] ?? 0) <=> (int)($a['timestamp'] ?? 0);
});

$final = array_slice($final, 0, $maxPosts);

file_put_contents($newsFile, json_encode($final, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo json_encode([
    'success' => true,
    'message' => 'news.json berhasil diperbarui via api-anything (tanpa RSS)',
    'count' => count($final),
    'details' => $details,
], JSON_UNESCAPED_UNICODE);
