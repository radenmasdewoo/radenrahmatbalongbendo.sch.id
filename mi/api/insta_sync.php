<?php
/**
 * Instagram Robust Sync - SMP (@osis_smprrahmat)
 * Sinkronisasi ke news.json.
 * Priority:
 * 1. Instagram Basic Display API (Access Token) - Paling stabil & resmi.
 * 2. Web Profile Info (Scraping) - Sering kena limit/block.
 * 3. RSS Feed (Third Party) - Fallback terakhir.
 */

header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/insta_config.json';
$newsFile = __DIR__ . '/news.json';

// --- PHP 8 Compatibility Polyfills for PHP 7.2 ---
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

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

$syncKey = $config['sync_key'] ?? 'radenrahmat_sync_2026';
$syncKeyFromEnv = php_sapi_name() === 'cli' ? $syncKey : ($_GET['key'] ?? '');
if ($syncKeyFromEnv !== $syncKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$accounts = $config['accounts'] ?? [];
$postsPerAccount = max(1, (int)($config['posts_per_account'] ?? 5));

if (empty($accounts)) {
    http_response_code(500);
    echo json_encode(['error' => 'Belum ada accounts yang dikonfigurasi']);
    exit;
}

/**
 * HTTP GET dengan Exponential Backoff & Retry Logic
 *
 * Retry otomatis dipicu saat menerima status HTTP: 429, 500, 502, 503, 504.
 * Jika server mengirim header Retry-After, nilai tersebut akan dihormati.
 *
 * @param string $url     URL yang akan di-request
 * @param array  $headers Header tambahan
 * @param int    $timeout Timeout per request (detik)
 * @param int    $maxRetry Jumlah maksimal percobaan ulang (default: 3)
 * @param int    $baseDelay Delay awal dalam milidetik sebelum retry pertama (default: 1000ms)
 * @return string Body respons
 * @throws Exception jika semua percobaan gagal
 */
function rr_http_get($url, array $headers = [], $timeout = 20, $maxRetry = 3, $baseDelay = 1000)
{
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept: */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: close'
    ];
    $allHeaders = array_values(array_unique(array_merge($defaultHeaders, $headers)));

    // Status HTTP yang layak untuk di-retry
    $retryableStatuses = [429, 500, 502, 503, 504];

    $lastException = null;

    for ($attempt = 0; $attempt <= $maxRetry; $attempt++) {

        // Exponential Backoff: tunda sebelum retry (bukan sebelum percobaan pertama)
        if ($attempt > 0) {
            // Delay: baseDelay * 2^(attempt-1) + jitter acak (0–500ms)
            $delayMs = ($baseDelay * pow(2, $attempt - 1)) + rand(0, 500);
            // Batasi maks 30 detik
            $delayMs = min($delayMs, 30000);
            usleep($delayMs * 1000);
        }

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_HTTPHEADER     => $allHeaders,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_HEADER         => true,  // Ambil header untuk baca Retry-After
                ]);
                $rawResponse  = curl_exec($ch);
                $status       = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $curlError    = curl_error($ch);
                curl_close($ch);

                if ($rawResponse === false) {
                    throw new Exception("cURL error pada percobaan ke-{$attempt}: " . $curlError);
                }

                $responseHeaders = substr($rawResponse, 0, $headerSize);
                $body            = substr($rawResponse, $headerSize);

                if (in_array($status, $retryableStatuses) && $attempt < $maxRetry) {
                    // Hormati Retry-After jika ada
                    if (preg_match('/^Retry-After:\s*(\d+)/mi', $responseHeaders, $m)) {
                        $retryAfterSec = (int)$m[1];
                        // Batasi maksimum 60 detik
                        sleep(min($retryAfterSec, 60));
                    }
                    throw new Exception("HTTP {$status} - akan retry (percobaan " . ($attempt + 1) . "/{$maxRetry})");
                }

                if ($status >= 400) {
                    throw new Exception("HTTP gagal ({$status}): " . ($curlError ?: $url));
                }

                return $body;

            } else {
                // Fallback: file_get_contents
                $ctx = stream_context_create([
                    'http' => [
                        'method'        => 'GET',
                        'timeout'       => $timeout,
                        'header'        => implode("\r\n", $allHeaders) . "\r\n",
                        'ignore_errors' => true,
                    ]
                ]);

                $body = @file_get_contents($url, false, $ctx);
                if ($body === false) {
                    throw new Exception('file_get_contents gagal: ' . $url);
                }

                // Cek status dari $http_response_header
                $status = 200;
                if (!empty($http_response_header)) {
                    if (preg_match('/HTTP\/\d+(?:\.\d+)?\s+(\d+)/', $http_response_header[0], $sm)) {
                        $status = (int)$sm[1];
                    }
                }

                if (in_array($status, $retryableStatuses) && $attempt < $maxRetry) {
                    throw new Exception("HTTP {$status} via stream - akan retry (percobaan " . ($attempt + 1) . "/{$maxRetry})");
                }

                if ($status >= 400) {
                    throw new Exception("HTTP gagal ({$status}): " . $url);
                }

                return $body;
            }

        } catch (Exception $e) {
            $lastException = $e;
            // Jika error bukan dari status retryable dan bukan percobaan awal, hentikan lebih awal
            if ($attempt >= $maxRetry) {
                break;
            }
            // Log retry ke error_log untuk debugging
            error_log("[rr_http_get] Retry {$attempt}/{$maxRetry} untuk URL: {$url} | Alasan: " . $e->getMessage());
        }
    }

    throw new Exception(
        "Semua {$maxRetry} percobaan gagal untuk URL: {$url}. Error terakhir: " . ($lastException ? $lastException->getMessage() : 'unknown')
    );
}

// --- PARSERS ---

// 1. Basic Display API Parser
function rr_parse_from_basic_api($accessToken, $limit) {
    if (!$accessToken) throw new Exception('Access Token kosong');
    
    // Get fields: id, caption, media_type, media_url, permalink, thumbnail_url, timestamp, username
    $url = "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username&access_token={$accessToken}&limit={$limit}";
    
    try {
        $json = rr_http_get($url);
        $data = json_decode($json, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Response API tidak valid: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        $items = [];
        foreach ($data['data'] as $post) {
            $id = $post['id'];
            $caption = $post['caption'] ?? '';
            $mediaType = $post['media_type']; // IMAGE, VIDEO, CAROUSEL_ALBUM
            $mediaUrl = $post['media_url'] ?? '';
            $permalink = $post['permalink'] ?? '';
            $thumbnailUrl = $post['thumbnail_url'] ?? '';
            $timestamp = $post['timestamp'] ?? '';
            $username = $post['username'] ?? '';
            
            $ts = strtotime($timestamp);
            
            // Logic for image/video source
            $imgSrc = '';
            $videoSrc = '';
            $isVideo = false;
            
            if ($mediaType === 'VIDEO') {
                $isVideo = true;
                $videoSrc = $mediaUrl; // Original video file
                $imgSrc = $thumbnailUrl ?: $mediaUrl; // Fallback to video url if no thumbnail (rare)
            } else {
                // IMAGE or CAROUSEL_ALBUM
                $imgSrc = $mediaUrl; 
            }
            
            // Title & Excerpt
            $title = $caption ?: 'Post Instagram ' . $username;
            $title = preg_replace('/\s+/', ' ', trim($title));
            $titleShort = mb_substr($title, 0, 90);
            
            $excerpt = preg_replace('/\s+/', ' ', trim($caption));
            $excerpt = mb_substr($excerpt, 0, 220);
            if (mb_strlen($excerpt) >= 220) $excerpt .= '...';
            
            // Extract shortcode from permalink if possible
            $shortcode = '';
            if (preg_match('/\/p\/([^\/]+)\//', $permalink, $m)) {
                $shortcode = $m[1];
            } elseif (preg_match('/\/reel\/([^\/]+)\//', $permalink, $m)) {
                $shortcode = $m[1];
            }
            
            $items[] = [
                'shortcode' => $shortcode,
                'title' => $titleShort,
                'excerpt' => $excerpt,
                'caption' => $caption,
                'img' => $imgSrc,
                'video' => $videoSrc,
                'is_video' => $isVideo,
                'media_type' => strtolower($mediaType),
                'date' => date('j M Y', $ts),
                'timestamp' => $ts,
                'link' => $permalink,
                'author' => $username,
                'id' => $id
            ];
        }
        
        return $items;
        
    } catch (Exception $e) {
        throw new Exception('Basic API Error: ' . $e->getMessage());
    }
}

// 2. Web Profile Info Parser (Scraping)
function rr_parse_caption(array $node)
{
    if (!empty($node['edge_media_to_caption']['edges'][0]['node']['text'])) {
        return trim((string)$node['edge_media_to_caption']['edges'][0]['node']['text']);
    }
    if (!empty($node['accessibility_caption'])) {
        return trim((string)$node['accessibility_caption']);
    }
    return '';
}

function rr_get_author_name($handle) {
    $map = [
        'osis_smprrahmat' => 'OSIS SMP R Rahmat',
        'miradenrahmatbalongbendo' => 'MI Raden Rahmat',
        'smartba.official' => 'SMA R Rahmat'
    ];
    return $map[$handle] ?? $handle;
}

function rr_parse_post_node(array $node, $instaHandle)
{
    $shortcode = (string)($node['shortcode'] ?? '');
    if ($shortcode === '') {
        return null;
    }

    $isVideo = (bool)($node['is_video'] ?? false);
    $displayUrl = (string)($node['display_url'] ?? '');
    $videoUrl = (string)($node['video_url'] ?? '');

    // Sidecar support
    if (!empty($node['edge_sidecar_to_children']['edges']) && is_array($node['edge_sidecar_to_children']['edges'])) {
        foreach ($node['edge_sidecar_to_children']['edges'] as $childEdge) {
            $child = $childEdge['node'] ?? [];
            $childIsVideo = (bool)($child['is_video'] ?? false);
            $childVideoUrl = (string)($child['video_url'] ?? '');
            $childDisplayUrl = (string)($child['display_url'] ?? '');

            if ($childIsVideo && $childVideoUrl !== '') {
                $isVideo = true;
                $videoUrl = $childVideoUrl;
                if ($displayUrl === '') {
                    $displayUrl = $childDisplayUrl;
                }
                break;
            }
            if ($displayUrl === '' && $childDisplayUrl !== '') {
                $displayUrl = $childDisplayUrl;
            }
        }
    }

    $caption = rr_parse_caption($node);
    $authorName = rr_get_author_name($instaHandle);
    $title = $caption !== '' ? $caption : 'Post Instagram ' . $authorName;
    $title = preg_replace('/\s+/', ' ', trim($title));
    $title = mb_substr($title, 0, 90);

    $excerpt = $caption !== '' ? preg_replace('/\s+/', ' ', trim($caption)) : $title;
    $excerpt = mb_substr($excerpt, 0, 220);
    if (mb_strlen($excerpt) >= 220) {
        $excerpt .= '...';
    }

    $ts = (int)($node['taken_at_timestamp'] ?? time());
    $productType = strtolower((string)($node['product_type'] ?? ''));
    $pathType = ($isVideo || $productType === 'clips') ? 'reel' : 'p';
    $postLink = "https://www.instagram.com/{$pathType}/{$shortcode}/";

    return [
        'shortcode' => $shortcode,
        'title' => $title,
        'excerpt' => $excerpt,
        'caption' => $caption,
        'img' => $displayUrl,
        'video' => $videoUrl,
        'is_video' => $isVideo,
        'media_type' => $isVideo ? 'video' : 'image',
        'date' => date('j M Y', $ts),
        'timestamp' => $ts,
        'link' => $postLink,
        'author' => $instaHandle
    ];
}

function rr_parse_from_web_profile_info($instaHandle)
{
    $url = 'https://i.instagram.com/api/v1/users/web_profile_info/?username=' . rawurlencode($instaHandle);
    $headers = [
        'X-IG-App-ID: 936619743392459',
        'X-Requested-With: XMLHttpRequest',
        'Referer: https://www.instagram.com/' . $instaHandle . '/',
        'Origin: https://www.instagram.com'
    ];

    $raw = rr_http_get($url, $headers);
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new Exception('Response Instagram web_profile_info bukan JSON valid');
    }

    $edges = $json['data']['user']['edge_owner_to_timeline_media']['edges'] ?? [];
    if (!is_array($edges) || empty($edges)) {
        throw new Exception('Postingan Instagram tidak ditemukan dari web_profile_info');
    }

    $items = [];
    foreach ($edges as $edge) {
        if (!is_array($edge) || !isset($edge['node']) || !is_array($edge['node'])) {
            continue;
        }
        $item = rr_parse_post_node($edge['node'], $instaHandle);
        if ($item) {
            $items[] = $item;
        }
    }

    return $items;
}

// 3. RSS Parser
function rr_parse_from_rss($rssUrl, $instaHandle)
{
    if ($rssUrl === '') {
        throw new Exception('RSS URL belum dikonfigurasi');
    }

    $xml = rr_http_get($rssUrl, ['Referer: https://www.instagram.com/']);
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml);
    if ($feed === false) {
        throw new Exception('Format RSS tidak valid');
    }

    $entries = [];
    if (isset($feed->channel->item)) {
        $entries = $feed->channel->item;
    } elseif (isset($feed->entry)) {
        $entries = $feed->entry;
    }

    $items = [];
    foreach ($entries as $entry) {
        $pubDate = isset($entry->pubDate) ? (string)$entry->pubDate : (isset($entry->published) ? (string)$entry->published : (isset($entry->updated) ? (string)$entry->updated : ''));
        $ts = strtotime($pubDate) ?: time();
        $link = (isset($entry->link['href']) ? (string)$entry->link['href'] : (string)$entry->link) ?: '';
        $title = isset($entry->title) ? (string)$entry->title : '';
        $description = isset($entry->description) ? (string)$entry->description : (isset($entry->content) ? (string)$entry->content : '');

        $img = '';
        $isVideo = false;
        $videoUrl = '';

        if (isset($entry->enclosure['url'])) {
            $enc = $entry->enclosure;
            $url = (string)$enc['url'];
            if (preg_match('/\.(mp4|webm|mov)/i', $url)) {
                $isVideo = true;
                $videoUrl = $url;
            } else {
                $img = $url;
            }
        }

        // Try parsing description for img/video
        if ($img === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $description, $m)) {
            $img = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        $excerpt = strip_tags($description);
        // Strip common "no text" markers
        $excerpt = str_replace('(no text)', '', $excerpt);
        $excerpt = trim(str_replace('▶', '', $excerpt));
        
        $excerpt = preg_replace('/\s+/', ' ', trim(mb_substr($excerpt, 0, 200)));
        if (mb_strlen($excerpt) >= 200) {
            $excerpt .= '...';
        }

        $authorName = rr_get_author_name($instaHandle);
        $fallbackTitle = trim($title ?: $excerpt ?: '') ?: ('Post Instagram ' . $authorName);
        // Remove play icon from title if it's just a placeholder
        $strippedTitle = trim(str_replace(['▶', '(no text)'], '', $fallbackTitle));
        if ($strippedTitle === '') $fallbackTitle = 'Post Instagram ' . $authorName;

        // Extract shortcode from link
        $shortcode = '';
        if (preg_match('/\/p\/([^\/]+)\//', $link, $m)) {
            $shortcode = $m[1];
        } elseif (preg_match('/\/reel\/([^\/]+)\//', $link, $m)) {
            $shortcode = $m[1];
        }

        // Video detection enhancement
        if (!$isVideo && (str_contains($title, '▶') || str_contains($description, '▶') || str_contains($link, '/reel/'))) {
            $isVideo = true;
        }

        // Blacklist test post
        if ($shortcode === 'DBq6_48yT_7') continue;

        $items[] = [
            'shortcode' => $shortcode,
            'title' => mb_substr($fallbackTitle, 0, 90),
            'excerpt' => $excerpt ?: $fallbackTitle,
            'caption' => $excerpt ?: $fallbackTitle,
            'img' => $img,
            'video' => $videoUrl,
            'is_video' => $isVideo,
            'media_type' => $isVideo ? 'video' : 'image',
            'date' => date('j M Y', $ts),
            'timestamp' => $ts,
            'link' => $link,
            'author' => $instaHandle
        ];
    }

    return $items;
}

function rr_fetch_from_rss_instances($handle, $limit) {
    // RSS-Bridge usually provides better results for Instagram when authenticated.
    // We found several public instances that currently work.
    $instances = [
        'https://rss-bridge.org/bridge01',
        'https://rss.bloat.cat',
        'https://rssbridge.prenghy.org',
        'https://rb.vern.cc',
        'https://wtf.roflcopter.fr/rss-bridge'
    ];
    
    foreach ($instances as $instance) {
        try {
            // RSS-Bridge Instagram Bridge URL format
            $url = rtrim($instance, '/') . "/?action=display&bridge=InstagramBridge&context=Username&u=" . urlencode($handle) . "&format=Atom";
            
            // Re-use existing rr_parse_from_rss but check for Atom (which simplexml handles fine)
            $items = rr_parse_from_rss($url, $handle);
            if (!empty($items)) return $items;
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Last ditch: try old RSSHub instances just in case one recovered
    $rsshubInstances = [
        'https://rsshub.app',
        'https://rsshub.rss.tc',
        'https://rsshub.icu'
    ];
    foreach ($rsshubInstances as $instance) {
        try {
            $url = rtrim($instance, '/') . '/instagram/user/' . $handle;
            $items = rr_parse_from_rss($url, $handle);
            if (!empty($items)) return $items;
        } catch (Exception $e) {
            continue;
        }
    }
    
    throw new Exception('Semua instance RSS gagal.');
}

/**
 * 4. Public Viewer: Imginn (Scraping)
 * Fallback jika scraping langsung & RSS gagal.
 */
function rr_fetch_from_imginn($handle, $limit)
{
    $url = "https://imginn.com/" . rawurlencode($handle) . "/";
    $html = rr_http_get($url);
    
    // Parse menggunakan regex sederhana (Imginn HTML structure)
    // <div class="item"> ... <a href="/p/SHORTCODE/"> ... <img src="URL" ...
    preg_match_all('/<div class="item">.*?<a href="\/p\/([^\/]+)\/".*?<img.*?src="([^"]+)".*?<div class="caption">([^<]*)<\/div>/s', $html, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        // Coba pattern lain jika Imginn update layout
        preg_match_all('/<a href="\/p\/([^\/]+)\/".*?data-src="([^"]+)".*?alt="([^"]*)"/s', $html, $matches, PREG_SET_ORDER);
    }

    if (empty($matches)) {
        throw new Exception('Gagal parse Imginn untuk ' . $handle);
    }

    $items = [];
    foreach ($matches as $m) {
        $shortcode = $m[1];
        $img = $m[2];
        $caption = trim($m[3] ?? '');
        
        $title = $caption ?: 'Post Instagram ' . $handle;
        $title = preg_replace('/\s+/', ' ', $title);
        $titleShort = mb_substr($title, 0, 90);
        
        $excerpt = mb_substr($title, 0, 220);
        if (mb_strlen($excerpt) >= 220) $excerpt .= '...';

        $items[] = [
            'shortcode' => $shortcode,
            'title'     => $titleShort,
            'excerpt'   => $excerpt,
            'caption'   => $caption,
            'img'       => $img,
            'video'     => '', // Imginn list usually doesn't give video URL directly
            'is_video'  => false, 
            'media_type' => 'image',
            'date'      => date('j M Y'), // Imginn sometimes hides exact date in list
            'timestamp' => time(), // Fallback timestamp
            'link'      => "https://www.instagram.com/p/{$shortcode}/",
            'author'    => $handle
        ];
        
        if (count($items) >= $limit) break;
    }

    return $items;
}

// --- MAIN EXECUTION ---

try {
    $syncDetails = [];
    $allNewItems = [];

    // Load existing items for fallback
    $existingItems = [];
    if (file_exists($newsFile)) {
        $existingItems = json_decode(file_get_contents($newsFile), true);
        if (!is_array($existingItems)) $existingItems = [];
    }

    foreach ($accounts as $acc) {
        $handle = $acc['handle'] ?? '';
        $rss = $acc['rss'] ?? '';
        $token = $acc['access_token'] ?? '';
        
        if (!$handle) continue;

        $currentItems = [];
        $src = '';

        // Try sources for each account
        try {
            if (!empty($token)) {
                $currentItems = rr_parse_from_basic_api($token, $postsPerAccount);
                $src = 'basic_api';
            } else {
                // No token, skip to next source
                throw new Exception('No token');
            }
        } catch (Exception $e1) {
            try {
                $currentItems = rr_parse_from_web_profile_info($handle);
                $src = 'web_profile';
            } catch (Exception $e2) {
                try {
                    $currentItems = rr_fetch_from_imginn($handle, $postsPerAccount);
                    $src = 'imginn_scraper';
                } catch (Exception $e3) {
                    try {
                        $currentItems = rr_fetch_from_rss_instances($handle, $postsPerAccount);
                        $src = 'rss_bridge_pool';
                    } catch (Exception $e4) {
                        $src = 'failed';
                    }
                }
            }
        }

        // --- ACCOUNT LEVEL MERGE AND SYNC ---
        // If we fetched new items, we trust them as the current state of Instagram.
        // If we failed to fetch anything (src='failed'), we keep the old ones for this account.
        
        $accountFinal = [];
        if (!empty($currentItems)) {
            // Dedup by link
            $seen = [];
            foreach ($currentItems as $ci) {
                $link = $ci['link'];
                if (in_array($link, $seen)) continue;
                
                // FILTER: Remove generic titles that don't look like real posts
                if (trim($ci['title']) === 'Post Instagram MI Raden Rahmat') continue;
                if (trim($ci['title']) === 'Post Instagram osis_smprrahmat') continue;
                if (trim($ci['title']) === 'Post Instagram smartba.official') continue;

                $accountFinal[] = $ci;
                $seen[] = $link;
            }
            // Sort by TS desc
            usort($accountFinal, function ($a, $b) { return (int)($b['timestamp'] ?? 0) <=> (int)($a['timestamp'] ?? 0); });
            // Take exactly 5
            $accountFinal = array_slice($accountFinal, 0, $postsPerAccount);
        } else {
            // Fallback: use old ones for this account if they exist
            $oldForAcc = array_filter($existingItems, function($i) use ($handle) { return ($i['author'] ?? '') === $handle; });
            $accountFinal = array_slice($oldForAcc, 0, $postsPerAccount);
            $src .= ' (reused_old)';
        }

        $allNewItems = array_merge($allNewItems, $accountFinal);
        $syncDetails[] = ['handle' => $handle, 'source' => $src, 'count' => count($accountFinal)];
    }

    // Final Deduplication and Limit for the whole news feed
    $finalDeduplicated = [];
    $seenGlobalKeys = [];
    
    foreach ($allNewItems as $item) {
        $key = $item['shortcode'] ?? $item['link'] ?? md5(json_encode($item));
        if (!in_array($key, $seenGlobalKeys)) {
            $finalDeduplicated[] = $item;
            $seenGlobalKeys[] = $key;
        }
    }
    
    // Sort globally by timestamp
    usort($finalDeduplicated, function ($a, $b) {
        return (int)($b['timestamp'] ?? 0) <=> (int)($a['timestamp'] ?? 0);
    });

    // Enforce global limit of 15 (5 per account * 3 accounts)
    $finalDeduplicated = array_slice($finalDeduplicated, 0, 15);

    // Save to news.json
    file_put_contents($newsFile, json_encode($finalDeduplicated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    // --- REPORTING TO CMS ---
    $cmsFile = dirname(__DIR__) . '/cms-pro-radenrahmat.html';
    if (file_exists($cmsFile)) {
        $cmsHtml = file_get_contents($cmsFile);
        $syncTime = date('Y-m-d H:i:s');
        $displayDate = date('j M Y');

        // Update Log Block
        $logEntry = "[$syncTime] Sync started\n";
        foreach ($syncDetails as $det) {
            $logEntry .= "[$syncTime] @{$det['handle']}: {$det['count']} items ({$det['source']})\n";
        }
        $logEntry .= "[$syncTime] Sync completed. Total: " . count($allNewItems) . " (max 5/acc)";

        $cmsHtml = preg_replace(
            '/<div class="code-block" id="ig-sync-log"[^>]*>.*?<\/div>/s',
            '<div class="code-block" id="ig-sync-log" style="max-height:120px;font-size:.68rem">' . htmlspecialchars($logEntry) . '</div>',
            $cmsHtml
        );

        foreach ($syncDetails as $det) {
            $handle = $det['handle'];
            $idPart = '';
            if (str_contains($handle, 'smartba')) $idPart = 'smartba';
            elseif (str_contains($handle, 'miradenrahmat')) $idPart = 'miradenrahmat';
            elseif (str_contains($handle, 'osis')) $idPart = 'osissmp';

            if ($idPart) {
                $cmsHtml = preg_replace(
                    '/<span id="sync-date-' . $idPart . '">.*?<\/span>/',
                    '<span id="sync-date-' . $idPart . '">' . $displayDate . '</span>',
                    $cmsHtml
                );
            }
        }
        file_put_contents($cmsFile, $cmsHtml);
    }

    // --- AUTO-LOCALIZE ---
    $cacheDir = __DIR__ . '/ig_cache';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0775, true);
    
    require_once __DIR__ . '/localize_media.php';
    $localizedItems = rr_localize_news_images($newsFile, $cacheDir);

    echo json_encode([
        'success'    => true,
        'message'    => 'news.json berhasil diperbarui & laporan dikirim ke CMS',
        'count'      => count($allNewItems),
        'details'    => $syncDetails
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}