<?php
/**
 * Instagram Direct Sync - MI (@miradenrahmatbalongbendo)
 * Sinkronisasi ke news_mi.json langsung dari profil Instagram publik.
 * Jalankan: api/insta_sync_mi.php?key=radenrahmat_sync_2026
 */

header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/insta_config_mi.json';
$newsFile = __DIR__ . '/news_mi.json';

// ── Compatibility Polyfills (PHP 7.2) ─────────────────────────────────────────
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return (string)$needle !== '' && strpos($haystack, $needle) !== false;
    }
}

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'insta_config_mi.json tidak ditemukan']);
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

$instaHandle = trim((string)($config['insta_handle'] ?? 'miradenrahmatbalongbendo'));
$postsLimit = max(1, (int)($config['posts_limit'] ?? 10));
$rssFallbackUrl = trim((string)($config['rss_url'] ?? ''));

if ($instaHandle === '') {
    http_response_code(500);
    echo json_encode(['error' => 'insta_handle belum dikonfigurasi']);
    exit;
}

function rr_http_get($url, array $headers = [], $timeout = 20)
{
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept: */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: close'
    ];
    $allHeaders = array_values(array_unique(array_merge($defaultHeaders, $headers)));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_ENCODING => ''
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status >= 400) {
            throw new Exception('HTTP gagal (' . $status . '): ' . ($error ?: $url));
        }
        return $body;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'header' => implode("\r\n", $allHeaders) . "\r\n",
            'ignore_errors' => true
        ]
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new Exception('Gagal request: ' . $url);
    }
    return $body;
}

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

function rr_parse_post_node(array $node, $instaHandle)
{
    $shortcode = (string)($node['shortcode'] ?? '');
    if ($shortcode === '') {
        return null;
    }

    $isVideo = (bool)($node['is_video'] ?? false);
    $displayUrl = (string)($node['display_url'] ?? '');
    $videoUrl = (string)($node['video_url'] ?? '');

    // Sidecar support: prioritaskan video jika tersedia.
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
    $title = $caption !== '' ? $caption : 'Post Instagram MI Raden Rahmat';
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

    if (empty($items)) {
        throw new Exception('Gagal memproses postingan Instagram dari web_profile_info');
    }

    return $items;
}

function rr_parse_from_profile_html($instaHandle)
{
    $url = 'https://www.instagram.com/' . rawurlencode($instaHandle) . '/';
    $html = rr_http_get($url, ['Referer: https://www.instagram.com/']);

    $jsonBlob = '';
    if (preg_match('/"edge_owner_to_timeline_media"\s*:\s*(\{.*?\})\s*,\s*"edge_saved_media"/s', $html, $m)) {
        $jsonBlob = '{"edge_owner_to_timeline_media":' . $m[1] . '}';
    }

    if ($jsonBlob === '') {
        throw new Exception('Tidak menemukan data timeline di HTML profil Instagram');
    }

    $parsed = json_decode($jsonBlob, true);
    $edges = $parsed['edge_owner_to_timeline_media']['edges'] ?? [];
    if (!is_array($edges) || empty($edges)) {
        throw new Exception('Postingan Instagram tidak ditemukan dari HTML profil');
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

    if (empty($items)) {
        throw new Exception('Gagal memproses postingan Instagram dari HTML profil');
    }

    return $items;
}

function rr_parse_from_rss_fallback($rssUrl, $instaHandle)
{
    if ($rssUrl === '') {
        throw new Exception('RSS fallback tidak dikonfigurasi');
    }

    $xml = rr_http_get($rssUrl, ['Referer: https://www.instagram.com/']);
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml);
    if ($feed === false) {
        throw new Exception('Format RSS fallback tidak valid');
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

        if (str_contains(strtolower($title), 'bridge returned error') || str_contains(strtolower($title), 'exception')) {
            continue;
        }

        $description = isset($entry->description) ? (string)$entry->description : (isset($entry->content) ? (string)$entry->content : '');

        $img = '';
        $isVideo = false;
        $videoUrl = '';

        if (isset($entry->enclosure['url'])) {
            $enc = $entry->enclosure;
            $url = (string)$enc['url'];
            $type = isset($enc['type']) ? strtolower((string)$enc['type']) : '';
            if (strpos($type, 'video') !== false || preg_match('/\.(mp4|webm|mov)/i', $url)) {
                $isVideo = true;
                $videoUrl = $url;
            } else {
                $img = $url;
            }
        }

        if ($img === '') {
            $mediaUri = 'http://search.yahoo.com/mrss/';
            $media = @$entry->children($mediaUri);
            if ($media) {
                foreach ($media->content as $mc) {
                    $attr = $mc->attributes();
                    $url = (string)$attr->url;
                    $medium = isset($attr->medium) ? strtolower((string)$attr->medium) : '';
                    $type = isset($attr->type) ? strtolower((string)$attr->type) : '';
                    if ($url !== '') {
                        if ($medium === 'video' || strpos($type, 'video') !== false || preg_match('/\.(mp4|webm|mov)/i', $url)) {
                            $isVideo = true;
                            $videoUrl = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
                        } else {
                            $img = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
                        }
                        break;
                    }
                }
                if ($img === '' && isset($media->thumbnail['url'])) {
                    $img = html_entity_decode((string)$media->thumbnail['url'], ENT_QUOTES, 'UTF-8');
                }
            }
        }

        if ($img === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $description, $m)) {
            $img = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        $excerpt = strip_tags($description);
        if (trim($excerpt) === '(no text)') $excerpt = '';
        
        $excerpt = preg_replace('/\s+/', ' ', trim(mb_substr($excerpt, 0, 200)));
        if (mb_strlen($excerpt) >= 200) {
            $excerpt .= '...';
        }

        $fallbackTitle = trim($title ?: $excerpt ?: '') ?: ('Post Instagram ' . date('j M Y', $ts));
        if ($fallbackTitle === '(no text)') $fallbackTitle = 'Post Instagram ' . date('j M Y', $ts);

        // Extract shortcode from link
        $shortcode = '';
        if (preg_match('/\/p\/([^\/]+)\//', $link, $m)) {
            $shortcode = $m[1];
        } elseif (preg_match('/\/reel\/([^\/]+)\//', $link, $m)) {
            $shortcode = $m[1];
        }

        // Video detection enhancement
        if (!$isVideo && (str_contains($title, '▶') || str_contains($description ?? '', '▶') || str_contains($link, '/reel/'))) {
            $isVideo = true;
        }

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

    if (empty($items)) {
        throw new Exception('RSS fallback tidak menghasilkan postingan');
    }

    return $items;
}

function rr_fetch_from_rss_instances($handle, $limit) {
    $instances = [
        'https://rss-bridge.org/bridge01',
        'https://rss.bloat.cat',
        'https://rssbridge.prenghy.org',
        'https://rb.vern.cc',
        'https://wtf.roflcopter.fr/rss-bridge'
    ];
    
    foreach ($instances as $instance) {
        try {
            $url = rtrim($instance, '/') . "/?action=display&bridge=InstagramBridge&context=Username&u=" . urlencode($handle) . "&format=Atom";
            $items = rr_parse_from_rss_fallback($url, $handle);
            if (!empty($items)) return $items;
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Fallback: RSSHub pool
    $rsshubSearch = [
        'https://rsshub.app',
        'https://rsshub.rss.tc',
        'https://rsshub.icu'
    ];
    foreach ($rsshubSearch as $instance) {
        try {
            $url = rtrim($instance, '/') . '/instagram/user/' . $handle;
            $items = rr_parse_from_rss_fallback($url, $handle);
            if (!empty($items)) return $items;
        } catch (Exception $e) {
            continue;
        }
    }
    
    throw new Exception('Semua instance RSS gagal.');
}

/**
 * 4. Public Viewer: Imginn (Scraping)
 */
function rr_fetch_from_imginn($handle, $limit)
{
    $url = "https://imginn.com/" . rawurlencode($handle) . "/";
    $html = rr_http_get($url);
    
    preg_match_all('/<div class="item">.*?<a href="\/p\/([^\/]+)\/".*?<img.*?src="([^"]+)".*?<div class="caption">([^<]*)<\/div>/s', $html, $matches, PREG_SET_ORDER);
    if (empty($matches)) {
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
            'video'     => '',
            'is_video'  => false, 
            'media_type' => 'image',
            'date'      => date('j M Y'),
            'timestamp' => time(),
            'link'      => "https://www.instagram.com/p/{$shortcode}/",
            'author'    => $handle
        ];
        if (count($items) >= $limit) break;
    }
    return $items;
}

try {
    $items = [];
    $sourceUsed = '';

    try {
        $items = rr_parse_from_web_profile_info($instaHandle);
        $sourceUsed = 'instagram_web_profile_info';
    } catch (Exception $e1) {
        try {
            $items = rr_parse_from_profile_html($instaHandle);
            $sourceUsed = 'instagram_profile_html';
        } catch (Exception $e2) {
            try {
                $items = rr_fetch_from_imginn($instaHandle, $postsLimit);
                $sourceUsed = 'imginn_scraper';
            } catch (Exception $e3) {
                $items = rr_fetch_from_rss_instances($instaHandle, $postsLimit);
                $sourceUsed = 'rss_bridge_pool';
            }
        }
    }

    usort($items, function ($a, $b) {
        $ta = (int)($a['timestamp'] ?? 0);
        $tb = (int)($b['timestamp'] ?? 0);
        return $tb <=> $ta;
    });

    $items = array_slice($items, 0, $postsLimit);

    // --- MERGE LOGIC ---
    $existingItems = [];
    if (file_exists($newsFile)) {
        $existingItems = json_decode(file_get_contents($newsFile), true);
        if (!is_array($existingItems)) $existingItems = [];
    }

    $mergedMap = [];
    foreach ($existingItems as $old) {
        if (!is_array($old)) continue;
        $key = !empty($old['link']) ? $old['link'] : ($old['title'] ?? '');
        if ($key) $mergedMap[$key] = $old;
    }

    foreach ($items as $new) {
        if (!is_array($new)) continue;
        $key = !empty($new['link']) ? $new['link'] : ($new['title'] ?? '');
        if ($key) $mergedMap[$key] = $new;
    }

    $finalItems = array_values($mergedMap);

    // Sort by timestamp descending
    usort($finalItems, function ($a, $b) {
        $ta = (int)($a['timestamp'] ?? 0);
        $tb = (int)($b['timestamp'] ?? 0);
        return $tb <=> $ta;
    });

    // Limit to 100 entries
    $finalItems = array_slice($finalItems, 0, 100);

    file_put_contents($newsFile, json_encode($finalItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    // --- AUTO-LOCALIZE ---
    $cacheDir = __DIR__ . '/ig_cache';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0775, true);
    
    require_once __DIR__ . '/localize_media.php';
    $localizedItems = rr_localize_news_images($newsFile, $cacheDir);
    $localizedCount = count(array_filter($localizedItems, function($i) { return str_starts_with($i['img'] ?? '', 'api/ig_cache/'); }));

    echo json_encode([
        'success' => true,
        'message' => 'news_mi.json berhasil diperbarui & gambar dilokalkan',
        'count' => count($items),
        'localized' => $localizedCount,
        'posts_limit' => $postsLimit,
        'source' => $sourceUsed,
        'instagram_profile' => 'https://www.instagram.com/' . $instaHandle . '/'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}