<?php
/**
 * news_inline.php
 * Outputs the current news.json data as an inline JavaScript variable.
 * Included as <script src="api/news_inline.php"> to guarantee articles
 * render on page load without requiring an async fetch.
 */
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$newsFile   = __DIR__ . '/news.json';
$newsMiFile = __DIR__ . '/news_mi.json';

function rr_read_json(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

$mainNews = rr_read_json($newsFile);
$miNews   = rr_read_json($newsMiFile);

// Merge with deduplication by shortcode
$merged = $mainNews;
$seenShortcodes = [];
foreach ($mainNews as $item) {
    $link = $item['link'] ?? '';
    if (preg_match('/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $link, $m)) {
        $seenShortcodes[$m[1]] = true;
    }
}
foreach ($miNews as $item) {
    $link = $item['link'] ?? '';
    $sc = '';
    if (preg_match('/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $link, $m)) {
        $sc = $m[1];
    }
    if ($sc && isset($seenShortcodes[$sc])) continue;
    if ($sc) $seenShortcodes[$sc] = true;
    $merged[] = $item;
}

$json = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "window.RR_NEWS_INLINE = " . $json . ";\n";
?>
