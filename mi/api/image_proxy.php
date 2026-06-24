<?php
/**
 * Media proxy (image/video) untuk CDN Instagram/Facebook.
 * Mendukung streaming video dan range requests.
 * PHP 7.2 Compatible.
 */

// --- Compatibility Polyfills ---
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return (string)$needle !== '' && strpos($haystack, $needle) !== false;
    }
}

function rr_exit_with_status($status, $message)
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, noimageindex');
    exit($message);
}

function rr_decode_escaped_url($value)
{
    if (!is_string($value) || $value === '') return '';
    $decoded = json_decode('"' . addcslashes($value, '"\\') . '"');
    if (is_string($decoded) && $decoded !== '') {
        return $decoded;
    }
    return str_replace('\\/', '/', $value);
}

function rr_is_allowed_media_host($url)
{
    $parts = @parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host === '') return false;

    $allowedHostFragments = ['instagram', 'fbcdn', 'cdninstagram', 'scontent'];
    foreach ($allowedHostFragments as $frag) {
        if (strpos($host, $frag) !== false) return true;
    }
    return false;
}

function rr_resolve_media_from_shortcode($shortcode, $kind, $useCache = true)
{
    $shortcode = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$shortcode);
    if ($shortcode === '') {
        return '';
    }

    if ($useCache) {
        // Check multiple possible news files across different portals
        $files = [
            '/mnt/webdata/htdocs/api/news.json',
            '/mnt/webdata/htdocs/api/news_mi.json',
            '/mnt/webdata/htdocs/mi/api/news_mi.json',
            '/mnt/webdata/htdocs/smp/api/news.json'
        ];
        foreach ($files as $path) {
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (strpos($item['link'] ?? '', $shortcode) !== false) {
                            if ($kind === 'video') {
                                if (!empty($item['video'])) return $item['video'];
                                continue;
                            }
                            if (!empty($item['img'])) return $item['img'];
                        }
                    }
                }
            }
        }
    }

    // Fallback: Real-time resolution via Instagram API
    $apiUrl = "https://i.instagram.com/api/v1/media/shortcode_field/{$shortcode}/info/";
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'X-IG-App-ID: 936619743392459',
            'Referer: https://www.instagram.com/',
        ]
    ]);
    $resp = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode === 200 && $resp && ($json = json_decode($resp, true))) {
        $item = $json['items'][0] ?? null;
        if ($item) {
            if ($kind === 'video') {
                if (!empty($item['video_versions'])) return $item['video_versions'][0]['url'];
            }
            if (!empty($item['image_versions2']['candidates'])) return $item['image_versions2']['candidates'][0]['url'];
        }
    }

    // Alternative: Scrape from public viewer
    try {
        $scrapeUrl = "https://imginn.com/p/{$shortcode}/";
        $ch2 = curl_init($scrapeUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36']
        ]);
        $html = curl_exec($ch2);
        curl_close($ch2);
        
        if ($html) {
            if ($kind === 'video') {
                if (preg_match('/<video.*?src=["\']([^"\']+)["\']/', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            } else {
                if (preg_match('/<div class="media">.*?<img.*?src=["\']([^"\']+)["\']/', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            }
        }
    } catch (Exception $e) {}

    return '';
}

function rr_extract_shortcode_from_url($url) {
    if (preg_match('/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $url, $m)) return $m[1];
    return '';
}

// --- Range Request Implementation ---
function rr_serve_file_with_range($filePath, $mime) {
    if (!file_exists($filePath)) return false;
    
    $size = filesize($filePath);
    if ($size < 100) return false;

    $start = 0;
    $end = $size - 1;
    
    header("Accept-Ranges: bytes");
    header("Content-Type: " . $mime);
    header("Access-Control-Allow-Origin: *");
    header("Cache-Control: public, max-age=86400");
    
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (preg_match('/bytes=(\d+)-(\d+)?/', $range, $m)) {
            $start = intval($m[1]);
            if (isset($m[2]) && $m[2] !== '') $end = intval($m[2]);
            
            if ($start > $end || $start >= $size) {
                header("HTTP/1.1 416 Requested Range Not Satisfiable");
                header("Content-Range: bytes */$size");
                exit;
            }
            
            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: " . ($end - $start + 1));
        }
    } else {
        header("Content-Length: " . $size);
    }
    
    $fp = fopen($filePath, 'rb');
    if (!$fp) return false;
    
    fseek($fp, $start);
    $buffer = 8192;
    $bytes_to_send = $end - $start + 1;
    while (!feof($fp) && $bytes_to_send > 0) {
        $read = ($bytes_to_send > $buffer) ? $buffer : $bytes_to_send;
        echo fread($fp, $read);
        flush();
        $bytes_to_send -= $read;
        if (connection_aborted()) break;
    }
    fclose($fp);
    exit;
}

// --- Main execution ---
$mediaUrl = trim((string)($_GET['url'] ?? ''));
$shortcode = trim((string)($_GET['shortcode'] ?? ''));
$kind = strtolower(trim((string)($_GET['kind'] ?? 'image')));
if ($kind !== 'video') $kind = 'image';

if ($mediaUrl === '' && $shortcode !== '') {
    $mediaUrl = rr_resolve_media_from_shortcode($shortcode, $kind);
}

// --- Handle Local Cache Links ---
if ($mediaUrl !== '' && (strpos($mediaUrl, 'ig_cache/') !== false || strpos($mediaUrl, 'local://') !== false)) {
    $cleanPath = str_replace('local://', '', $mediaUrl);
    $filename = basename($cleanPath);
    
    $candidates = [
        '/mnt/webdata/htdocs/api/ig_cache/' . $filename,
        '/mnt/webdata/htdocs/mi/api/ig_cache/' . $filename,
        '/mnt/webdata/htdocs/smp/api/ig_cache/' . $filename,
        __DIR__ . '/ig_cache/' . $filename
    ];
    
    foreach ($candidates as $cp) {
        if (file_exists($cp)) {
            $mime = ($kind === 'video') ? 'video/mp4' : 'image/jpeg';
            if (rr_serve_file_with_range($cp, $mime)) exit;
        }
    }
    
    // Fallback if local file not found
    if ($shortcode) {
         $mediaUrl = rr_resolve_media_from_shortcode($shortcode, $kind, false);
    }
}

if ($mediaUrl === '') {
    rr_exit_with_status(404, 'No media URL found');
}

if (!rr_is_allowed_media_host($mediaUrl)) {
    rr_exit_with_status(403, 'Invalid media source');
}

// --- Fetch from Remote ---
$igHeaders = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    'Referer: https://www.instagram.com/',
    'Origin: https://www.instagram.com',
    'Connection: keep-alive',
];

if ($kind === 'video') {
    $igHeaders[] = 'Accept: video/webm,video/mp4,video/*;q=0.9,*/*;q=0.8';
} else {
    $igHeaders[] = 'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';
}

$ch = curl_init($mediaUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER     => $igHeaders,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$body = curl_exec($ch);
$statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// If remote URL is dead, try one last time to resolve fresh
if (($statusCode >= 400 || !$body) && $shortcode !== '') {
    $freshUrl = rr_resolve_media_from_shortcode($shortcode, $kind, false);
    if ($freshUrl && $freshUrl !== $mediaUrl) {
        $ch = curl_init($freshUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $igHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
    }
}

if (!$body || $statusCode >= 400) {
    rr_exit_with_status(404, 'Remote media failed');
}

header('Content-Type: ' . ($contentType ?: ($kind === 'video' ? 'video/mp4' : 'image/jpeg')));
header('Cache-Control: public, max-age=86400');
header('Access-Control-Allow-Origin: *');
header('X-Robots-Tag: noindex, noimageindex');
echo $body;