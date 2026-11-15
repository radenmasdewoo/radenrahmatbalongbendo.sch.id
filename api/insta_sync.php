<?php
/** Direct Instagram RSS sync - Root portal - 3 accounts: SMP, MI, SMARTBA - with auto-localization */
$configFile = __DIR__ . '/insta_config.json';
$newsFile = __DIR__ . '/news.json';

if (!file_exists($configFile)) { http_response_code(500); echo json_encode(['error' => 'insta_config.json tidak ditemukan']); exit; }
$config = json_decode(file_get_contents($configFile), true);
if (!is_array($config)) { http_response_code(500); echo json_encode(['error' => 'Konfigurasi tidak valid']); exit; }

$accounts = $config['accounts'] ?? [];
$postsPerAccount = max(1, (int)($config['posts_per_account'] ?? 5));
if (empty($accounts)) { http_response_code(500); echo json_encode(['error' => 'Belum ada accounts yang dikonfigurasi']); exit; }

function fetch_rss($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $httpCode >= 400) throw new Exception('HTTP gagal (' . $httpCode . '): ' . $url);
    return $body;
}

function parse_rss_items($xml, $handle, $limit) {
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml);
    if ($feed === false) throw new Exception('Format RSS tidak valid');
    $entries = isset($feed->channel->item) ? $feed->channel->item : (isset($feed->entry) ? $feed->entry : []);
    $items = [];
    foreach ($entries as $entry) {
        $pubDate = isset($entry->pubDate) ? (string)$entry->pubDate : ((isset($entry->published) ? (string)$entry->published : (isset($entry->updated) ? (string)$entry->updated : '')));
        $ts = strtotime($pubDate) ?: time();
        $link = (isset($entry->link['href']) ? (string)$entry->link['href'] : (string)$entry->link) ?: '';
        $title = isset($entry->title) ? (string)$entry->title : '';
        if (str_contains(strtolower($title), 'bridge returned error') || str_contains(strtolower($title), 'exception')) continue;
        $description = isset($entry->description) ? (string)$entry->description : ((isset($entry->content) ? (string)$entry->content : ''));
        $img = ''; $isVideo = false; $videoUrl = '';
        if (isset($entry->enclosure['url'])) {
            $enc = $entry->enclosure;
            $url = (string)$enc['url'];
            if (preg_match('/\.(mp4|webm|mov)/i', $url)) { $isVideo = true; $videoUrl = $url; }
            else { $img = $url; }
        }
        if ($img === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $description, $m)) {
            $img = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }
        $excerpt = strip_tags($description);
        $excerpt = str_replace('(no text)', '', $excerpt);
        $excerpt = trim(str_replace('▶', '', $excerpt));
        $excerpt = preg_replace('/\s+/', ' ', trim(mb_substr($excerpt, 0, 200)));
        $fallbackTitle = trim($title) ?: 'Post Instagram ' . $handle;
        $items[] = ['shortcode' => basename($link), 'title' => mb_substr($fallbackTitle, 0, 90), 'excerpt' => $excerpt ?: $fallbackTitle, 'caption' => $excerpt ?: $fallbackTitle, 'img' => $img, 'video' => $videoUrl, 'is_video' => $isVideo, 'media_type' => $isVideo ? 'video' : 'image', 'date' => date('j M Y', $ts), 'timestamp' => $ts, 'link' => $link, 'author' => $handle];
        if (count($items) >= $limit) break;
    }
    return $items;
}

$allItems = []; $details = [];
foreach ($accounts as $acc) {
    $handle = $acc['handle'] ?? ''; $rss = $acc['rss'] ?? '';
    if (!$handle) continue;
    $currentItems = []; $src = '';
    try {
        if (!empty($rss)) {
            $xml = fetch_rss($rss);
            $currentItems = parse_rss_items($xml, $handle, $postsPerAccount);
            $src = 'rss_app_feed';
        }
    } catch (Exception $e) { $src = 'failed: ' . $e->getMessage(); }
    $seen = []; $accountFinal = [];
    foreach ($currentItems as $ci) {
        if (in_array($ci['link'], $seen)) continue;
        $accountFinal[] = $ci; $seen[] = $ci['link'];
    }
    $allItems = array_merge($allItems, $accountFinal);
    $details[] = ['handle' => $handle, 'source' => $src, 'count' => count($accountFinal)];
}

$final = []; $seenGlobal = [];
foreach ($allItems as $item) {
    $key = $item['shortcode'] ?? $item['link'] ?? md5(json_encode($item));
    if (!in_array($key, $seenGlobal)) { $final[] = $item; $seenGlobal[] = $key; }
}
usort($final, function ($a, $b) { return (int)($b['timestamp'] ?? 0) <=> (int)($a['timestamp'] ?? 0); });
$final = array_slice($final, 0, $postsPerAccount * count($accounts));

file_put_contents($newsFile, json_encode($final, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// --- Auto-localize ---
require_once __DIR__ . '/../api/localize_media.php';
$cacheDir = __DIR__ . '/ig_cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
$localizedItems = rr_localize_news_images($newsFile, $cacheDir);
$localCount = count(array_filter($localizedItems, function($i) { return str_starts_with($i['img'] ?? '', 'api/ig_cache/'); }));
echo json_encode(['success' => true, 'message' => 'news.json + gambar lokal diperbarui', 'count' => count($final), 'localized' => $localCount, 'details' => $details], JSON_UNESCAPED_UNICODE);
