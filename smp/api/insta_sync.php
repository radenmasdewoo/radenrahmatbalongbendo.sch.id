<?php
/**
 * Instagram Sync - SMP R Rahmat (Free Scraper Pool)
 * Mengunduh gambar & video ke lokal ig_cache/ tanpa bergantung pada RSS berbayar.
 * Jalankan: api/insta_sync.php?key=radenrahmat_sync_2026
 */

header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/insta_config.json';
$cacheDir   = __DIR__ . '/ig_cache/';
$dataJson   = $cacheDir . 'data.json';
$logFile    = __DIR__ . '/sync_log_smp.txt';

// ── Auth & CLI support ────────────────────────────────────────────────────────
if (PHP_SAPI === 'cli' && empty($_GET['key']) && isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function rr_log(string $msg): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

function rr_http_get($url, array $headers = [], $timeout = 25) {
    if (!function_exists('curl_init')) return false;
    $ch = curl_init($url);
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept: */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    ];
    $allHeaders = array_merge($defaultHeaders, $headers);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => $allHeaders,
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code >= 200 && $code < 300) ? $body : false;
}

function rr_download(string $url, string $destPath, bool $isVideo = false): bool {
    $headers = [ 'Referer: https://www.instagram.com/' ];
    if ($isVideo) $headers[] = 'Accept: video/mp4,video/*;q=0.9,*/*;q=0.8';
    
    $body = rr_http_get($url, $headers, 40);
    if ($body !== false && strlen($body) > 200) {
        file_put_contents($destPath, $body);
        return true;
    }
    return false;
}

function rr_shortcode(string $url): string {
    if (preg_match('/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $url, $m)) return $m[1];
    return '';
}

// ── Scrapers ──────────────────────────────────────────────────────────────────
function rr_fetch_rss_bridge(string $handle) {
    $instances = [
        'https://rss-bridge.org/bridge01',
        'https://rss.bloat.cat',
        'https://rb.vern.cc',
        'https://wtf.roflcopter.fr/rss-bridge',
        'https://rssbridge.prenghy.org'
    ];
    foreach ($instances as $ins) {
        $url = rtrim($ins, '/') . "/?action=display&bridge=InstagramBridge&context=Username&u=" . urlencode($handle) . "&format=Atom";
        rr_log("  [SCAN] Mencoba RSS-Bridge: " . parse_url($ins, PHP_URL_HOST));
        $xml = rr_http_get($url, [], 15);
        if ($xml) {
            libxml_use_internal_errors(true);
            $feed = @simplexml_load_string($xml);
            if ($feed) return $feed;
        }
    }
    return false;
}

function rr_fetch_imginn(string $handle, int $limit) {
    $url = "https://imginn.com/" . rawurlencode($handle) . "/";
    $html = rr_http_get($url);
    if (!$html) return [];
    
    preg_match_all('/<div class="item">.*?<a href="\/p\/([^\/]+)\/".*?<img.*?src="([^"]+)".*?<div class="caption">([^<]*)<\/div>/s', $html, $matches, PREG_SET_ORDER);
    if (empty($matches)) preg_match_all('/<a href="\/p\/([^\/]+)\/".*?data-src="([^"]+)".*?alt="([^"]*)"/s', $html, $matches, PREG_SET_ORDER);

    $items = [];
    foreach ($matches as $m) {
        $sc = $m[1];
        $items[] = [
            'shortcode' => $sc,
            'title'     => mb_substr(trim($m[3] ?? 'Post Instagram'), 0, 80),
            'img_url'   => $m[2],
            'link'      => "https://www.instagram.com/p/{$sc}/",
            'date'      => date('j M Y'),
            'ts'        => time()
        ];
        if (count($items) >= $limit) break;
    }
    return $items;
}

function rr_fetch_picuki(string $handle, int $limit = 10) {
    $url = 'https://www.picuki.com/profile/' . rawurlencode($handle);
    $html = rr_http_get($url, [], 30);
    if ($html === false) return [];
    $items = [];
    if (preg_match_all('#<img[^>]+src="(https://scontent[^"]+)"#i', $html, $m)) {
        $items = array_unique($m[1]);
    }
    if (!count($items)) return [];
    $out = [];
    foreach ($items as $img) {
        $out[] = [
            'shortcode' => '',
            'title' => 'Post Instagram',
            'img_url' => $img,
            'link' => 'https://www.instagram.com/' . rawurlencode($handle) . '/',
            'date' => date('j M Y'),
            'ts' => time(),
        ];
        if (count($out) >= $limit) break;
    }
    return $out;
}

function rr_fetch_instanda(string $handle, int $limit = 10) {
    $url = 'https://instanda.me/' . rawurlencode($handle);
    $html = rr_http_get($url, [], 30);
    if ($html === false) return [];
    $items = [];
    if (preg_match_all('#"display_url":"(https:\\\\u002F\\\\u002Fscontent[^"]+)"#i', $html, $m)) {
        foreach ($m[1] as $u) $items[] = str_replace(['\u002F','\\\\u002F'], '/', $u);
    }
    if (!count($items) && preg_match_all('#<img[^>]+src="(https://scontent[^"]+)"#i', $html, $m2)) {
        $items = array_unique($m2[1]);
    }
    if (!count($items)) return [];
    $out = [];
    foreach ($items as $img) {
        $out[] = [
            'shortcode' => '',
            'title' => 'Post Instagram',
            'img_url' => $img,
            'link' => 'https://www.instagram.com/' . rawurlencode($handle) . '/',
            'date' => date('j M Y'),
            'ts' => time(),
        ];
        if (count($out) >= $limit) break;
    }
    return $out;
}

// ── Execution ─────────────────────────────────────────────────────────────────
if (!file_exists($configFile)) exit("Config missing\n");
$config  = json_decode(file_get_contents($configFile), true);
$syncKey = $config['sync_key'] ?? 'radenrahmat_sync_2026';

if (!isset($_GET['key']) || $_GET['key'] !== $syncKey) {
    http_response_code(403);
    exit("Unauthorized\n");
}

$handle = $config['insta_handle'] ?? 'smprrahmatbalongbendo';
$limit  = (int)($config['posts_limit'] ?? 10);
$rssUrl = trim($config['rss_url'] ?? '');

rr_log("[START] Memulai Sinkronisasi SMP (FREE MODE): @{$handle}");

$entries = [];
$mode = 'RSS-BRIDGE';

// 1. Coba RSS-Bridge Pool
$feed = rr_fetch_rss_bridge($handle);
if ($feed) {
    $entries = isset($feed->channel->item) ? $feed->channel->item : (isset($feed->entry) ? $feed->entry : []);
}

// 2. Fallback ke Imginn / Picuki / Instanda
$manual = [];
if (empty($entries)) {
    rr_log("[STEP 1] RSS-Bridge gagal, mencoba Imginn...");
    $manual = rr_fetch_imginn($handle, $limit);
    if ($manual) {
        $mode = 'IMGINN';
    } else {
        rr_log("[STEP 2] Imginn gagal, mencoba Picuki...");
        $manual = rr_fetch_picuki($handle, $limit);
        if ($manual) {
            $mode = 'PICUKI';
        } else {
            rr_log("[STEP 3] Picuki gagal, mencoba Instanda...");
            $manual = rr_fetch_instanda($handle, $limit);
            if ($manual) {
                $mode = 'INSTANDA';
            }
        }
    }
}

if (empty($entries) && empty($manual)) {
    rr_log("[ERROR] Semua sumber gagal.");
    exit(1);
}

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
$finalData = [];

if ($mode === 'RSS-BRIDGE') {
    foreach ($entries as $e) {
        if (count($finalData) >= $limit) break;
        $link = (isset($e->link['href']) ? (string)$e->link['href'] : (string)$e->link) ?: '';
        $sc = rr_shortcode($link);
        if (!$sc) continue;

        $desc = isset($e->description) ? (string)$e->description : (isset($e->content) ? (string)$e->content : '');
        $title = mb_substr(trim(strip_tags((string)$e->title ?: $desc)), 0, 80) ?: 'Post Instagram';
        $pub = isset($e->pubDate) ? (string)$e->pubDate : (isset($e->published) ? (string)$e->published : '');
        $ts = strtotime($pub) ?: time();

        // Download image (prioritize size=l trick if normal fails or is missing)
        $imgTarget = "https://www.instagram.com/p/{$sc}/media/?size=l";
        $imgFile = $sc . ".jpg";
        $imgOk = rr_download($imgTarget, $cacheDir . $imgFile);

        $finalData[] = [
            'shortcode' => $sc,
            'title'     => $title,
            'excerpt'   => mb_substr(strip_tags($desc), 0, 200),
            'img'       => $imgOk ? "api/ig_cache/{$imgFile}" : "",
            'video'     => "", // Free scapers rarely provide direct MP4 without more work
            'is_video'  => str_contains($link, '/reel/'),
            'date'      => date('j M Y', $ts),
            'timestamp' => $ts,
            'link'      => $link,
            'author'    => $handle
        ];
    }
} else {
    foreach ($manual as $m) {
        $sc = $m['shortcode'];
        $imgFile = $sc . ".jpg";
        $imgOk = rr_download($m['img_url'], $cacheDir . $imgFile);
        if (!$imgOk) $imgOk = rr_download("https://www.instagram.com/p/{$sc}/media/?size=l", $cacheDir . $imgFile);

        $finalData[] = [
            'shortcode' => $sc,
            'title'     => $m['title'],
            'excerpt'   => $m['title'],
            'img'       => $imgOk ? "api/ig_cache/{$imgFile}" : "",
            'video'     => "",
            'is_video'  => false,
            'date'      => $m['date'],
            'timestamp' => $m['ts'],
            'link'      => $m['link'],
            'author'    => $handle
        ];
    }
}

usort($finalData, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
file_put_contents($dataJson, json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
@file_put_contents(__DIR__ . '/news.json', json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

rr_log("[SUCCESS] Sinkronisasi selesai via $mode. Total items: " . count($finalData));
