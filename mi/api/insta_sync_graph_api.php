<?php
/**
 * Instagram Graph API Sync - MI Raden Rahmat
 * Menggunakan Instagram Basic Display API & Graph API resmi
 * Fallback ke RSS-Bridge jika Graph API gagal
 *
 * Jalankan: php insta_sync_graph_api.php key=radenrahmat_sync_2026
 * Atau via HTTP: ?key=radenrahmat_sync_2026
 */

header('Content-Type: text/plain; charset=utf-8');

// ── Configuration ─────────────────────────────────────────────────────────────
$configFile = __DIR__ . '/insta_config_mi.json';
$cacheDir   = __DIR__ . '/ig_cache/';
$dataJson   = __DIR__ . '/news_mi.json';
$logFile    = __DIR__ . '/sync_log_mi.txt';
$tokenFile  = __DIR__ . '/ig_access_token.json';

// Cleanup threshold
$CLEANUP_DAYS = 30;

// ── Compatibility Polyfills ───────────────────────────────────────────────────
mb_internal_encoding("UTF-8");
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return (string)$needle !== '' && strpos($haystack, $needle) !== false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function rr_log(string $msg): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

function rr_http_get($url, $timeout = 30) {
    if (!function_exists('curl_init')) return false;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 300) ? $response : false;
}

function rr_download(string $url, string $destPath): bool {
    $imgData = rr_http_get($url, 40);
    if ($imgData !== false && strlen($imgData) > 1000) {
        file_put_contents($destPath, $imgData);
        return true;
    }
    return false;
}

// ── Instagram Graph API Functions ─────────────────────────────────────────────

/**
 * Get Instagram Business Account ID from Page Access Token
 */
function get_instagram_account_id($accessToken) {
    $url = "https://graph.facebook.com/v18.0/me?fields=instagram_business_account&access_token={$accessToken}";
    $response = rr_http_get($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['instagram_business_account']['id'])) {
            return $data['instagram_business_account']['id'];
        }
    }
    return false;
}

/**
 * Fetch media from Instagram Graph API
 */
function fetch_instagram_media($accountId, $accessToken, $limit = 25) {
    $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username';
    $url = "https://graph.facebook.com/v18.0/{$accountId}/media?fields={$fields}&limit={$limit}&access_token={$accessToken}";
    
    rr_log("  [API] Fetching from Instagram Graph API...");
    $response = rr_http_get($url, 30);
    
    if (!$response) {
        rr_log("  [ERROR] Failed to connect to Instagram Graph API");
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['error'])) {
        rr_log("  [ERROR] API Error: " . ($data['error']['message'] ?? 'Unknown error'));
        return [];
    }
    
    return $data['data'] ?? [];
}

/**
 * Fetch child media (for carousel/albums)
 */
function fetch_child_media($mediaId, $accessToken) {
    $fields = 'id,media_type,media_url,thumbnail_url';
    $url = "https://graph.facebook.com/v18.0/{$mediaId}/children?fields={$fields}&access_token={$accessToken}";
    
    $response = rr_http_get($url);
    if ($response) {
        $data = json_decode($response, true);
        return $data['data'] ?? [];
    }
    return [];
}

/**
 * Convert Instagram media to our format
 */
function convert_to_our_format($media, $handle) {
    $shortcode = extract_shortcode_from_permalink($media['permalink'] ?? '');
    $timestamp = isset($media['timestamp']) ? strtotime($media['timestamp']) : time();
    $date = date('j M Y', $timestamp);
    
    $mediaType = $media['media_type'] ?? 'IMAGE';
    $isVideo = in_array($mediaType, ['VIDEO', 'IGTV']);
    
    $title = !empty($media['caption']) ? mb_substr(strip_tags($media['caption']), 0, 80) : 'Post Instagram';
    if (strlen($title) < 10) $title = 'Post @' . $handle;
    
    $imgUrl = $isVideo ? ($media['thumbnail_url'] ?? '') : ($media['media_url'] ?? '');
    $videoUrl = $isVideo ? ($media['media_url'] ?? '') : '';
    
    return [
        'shortcode' => $shortcode,
        'title' => $title,
        'excerpt' => !empty($media['caption']) ? mb_substr(strip_tags($media['caption']), 0, 200) : '',
        'img' => '', // Will be set after download
        'img_url_remote' => $imgUrl,
        'video' => $videoUrl,
        'is_video' => $isVideo,
        'date' => $date,
        'timestamp' => $timestamp,
        'link' => $media['permalink'] ?? "https://www.instagram.com/p/{$shortcode}/",
        'author' => $handle,
        'media_type' => $mediaType
    ];
}

function extract_shortcode_from_permalink($permalink) {
    if (preg_match('/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $permalink, $m)) {
        return $m[1];
    }
    return 'unknown_' . time();
}

// ── Cleanup Function ──────────────────────────────────────────────────────────
function rr_cleanup_old_news($dataJson, $cacheDir, $maxDays, $logCallback) {
    $cutoffTime = time() - ($maxDays * 24 * 60 * 60);
    $deletedCount = 0;
    $deletedFiles = 0;
    
    if (!file_exists($dataJson)) return ['deleted_news' => 0, 'deleted_files' => 0];
    
    $data = json_decode(file_get_contents($dataJson), true);
    if (!is_array($data)) return ['deleted_news' => 0, 'deleted_files' => 0];
    
    $validData = [];
    $validShortcodes = [];
    
    foreach ($data as $item) {
        $ts = $item['timestamp'] ?? 0;
        $maxValidTimestamp = time() + (7 * 24 * 60 * 60);
        
        if ($ts > 0 && $ts <= $maxValidTimestamp && $ts >= $cutoffTime) {
            $validData[] = $item;
            $validShortcodes[] = $item['shortcode'] ?? '';
        } else {
            $deletedCount++;
            $logCallback("[CLEANUP] Menghapus berita lama: " . ($item['shortcode'] ?? 'unknown'));
        }
    }
    
    if ($deletedCount > 0) {
        file_put_contents($dataJson, json_encode($validData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    if (is_dir($cacheDir)) {
        $files = scandir($cacheDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
            $shortcode = pathinfo($file, PATHINFO_FILENAME);
            if (!in_array($shortcode, $validShortcodes)) {
                $filePath = $cacheDir . $file;
                if (is_file($filePath) && unlink($filePath)) {
                    $deletedFiles++;
                    $logCallback("[CLEANUP] Menghapus file cache: " . $file);
                }
            }
        }
    }
    
    return ['deleted_news' => $deletedCount, 'deleted_files' => $deletedFiles];
}

// ── Main Sync Function ────────────────────────────────────────────────────────
function run_instagram_graph_sync() {
    global $APP_ID, $APP_SECRET, $PAGE_ACCESS_TOKEN, $INSTAGRAM_BUSINESS_ACCOUNT_ID;
    global $configFile, $cacheDir, $dataJson, $logFile, $CLEANUP_DAYS, $tokenFile;
    
    // Load config
    if (!file_exists($configFile)) {
        rr_log("[ERROR] Config file missing");
        return false;
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    $handle = $config['insta_handle'] ?? 'miradenrahmatbalongbendo';
    $limit = (int)($config['posts_limit'] ?? 12);
    
    rr_log("[START] Instagram Graph API Sync: @{$handle}");
    
    // Create cache directory
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Get Instagram Account ID if not set
    if (empty($INSTAGRAM_BUSINESS_ACCOUNT_ID)) {
        rr_log("  [INFO] Fetching Instagram Business Account ID...");
        $accountId = get_instagram_account_id($PAGE_ACCESS_TOKEN);
        if ($accountId) {
            $INSTAGRAM_BUSINESS_ACCOUNT_ID = $accountId;
            rr_log("  [SUCCESS] Account ID: {$accountId}");
        } else {
            rr_log("[ERROR] Cannot get Instagram Business Account ID");
            return false;
        }
    }
    
    // Fetch media from Instagram Graph API
    $mediaItems = fetch_instagram_media($INSTAGRAM_BUSINESS_ACCOUNT_ID, $PAGE_ACCESS_TOKEN, $limit * 2);
    
    if (empty($mediaItems)) {
        rr_log("[ERROR] No media fetched from Instagram Graph API");
        return false;
    }
    
    rr_log("  [SUCCESS] Fetched " . count($mediaItems) . " media items from Graph API");
    
    // Load existing data for comparison
    $oldData = [];
    if (file_exists($dataJson)) {
        $oldRaw = json_decode(file_get_contents($dataJson), true);
        if (is_array($oldRaw)) {
            foreach ($oldRaw as $item) {
                if (isset($item['shortcode'])) {
                    $oldData[$item['shortcode']] = $item;
                }
            }
        }
    }
    
    // Process media items
    $processedData = [];
    $newPostsCount = 0;
    
    foreach ($mediaItems as $media) {
        $item = convert_to_our_format($media, $handle);
        $shortcode = $item['shortcode'];
        
        // Check if this is a new post
        if (!isset($oldData[$shortcode])) {
            $newPostsCount++;
            rr_log("  [NEW] Found new post: {$shortcode}");
        }
        
        // Download media
        $imgFile = "{$shortcode}.jpg";
        $localImgPath = $cacheDir . $imgFile;
        
        if (!empty($item['img_url_remote'])) {
            if (file_exists($localImgPath) && filesize($localImgPath) > 2000) {
                $item['img'] = "api/ig_cache/{$imgFile}";
            } else {
                if (rr_download($item['img_url_remote'], $localImgPath)) {
                    $item['img'] = "api/ig_cache/{$imgFile}";
                }
            }
        }
        
        // Remove temporary field
        unset($item['img_url_remote']);
        
        $processedData[$shortcode] = $item;
    }
    
    // Sort by timestamp (newest first)
    $finalData = array_values($processedData);
    usort($finalData, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Limit to config limit
    $finalData = array_slice($finalData, 0, $limit);
    
    // Save to file
    file_put_contents($dataJson, json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    rr_log("[SUCCESS] Sync completed. Total items: " . count($finalData) . ", New posts: {$newPostsCount}");
    
    // Auto-cleanup
    rr_log("[AUTO-CLEANUP] Checking for old news (> {$CLEANUP_DAYS} days)...");
    $cleanupResult = rr_cleanup_old_news($dataJson, $cacheDir, $CLEANUP_DAYS, 'rr_log');
    if ($cleanupResult['deleted_news'] > 0 || $cleanupResult['deleted_files'] > 0) {
        rr_log("[AUTO-CLEANUP] Done. Deleted: {$cleanupResult['deleted_news']} news, {$cleanupResult['deleted_files']} files.");
    }
    
    return true;
}

// ── Execution ─────────────────────────────────────────────────────────────────
// Support CLI arguments
if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

// Verify sync key
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$syncKey = $config['sync_key'] ?? 'radenrahmat_sync_2026';

if (!isset($_GET['key']) || $_GET['key'] !== $syncKey) {
    http_response_code(403);
    exit("Unauthorized\n");
}

// Run sync
run_instagram_graph_sync();
