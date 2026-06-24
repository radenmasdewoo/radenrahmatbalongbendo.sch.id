<?php
/**
 * Test Script - Verify All Instagram Sources
 * Run: php test_all_sources.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$handle = 'miradenrahmatbalongbendo';

function http_get($url, $timeout = 30) {
    if (!function_exists('curl_init')) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

echo "==============================================\n";
echo "TEST ALL INSTAGRAM SOURCES\n";
echo "Profile: @{$handle}\n";
echo "==============================================\n\n";

// 1. RSS-Bridge
echo "[1] RSS-Bridge Instances:\n";
$instances = [
    'https://rss-bridge.org/bridge01',
    'https://rss.bloat.cat',
    'https://rb.vern.cc',
    'https://wtf.roflcopter.fr/rss-bridge',
    'https://rssbridge.prenghy.org',
];
foreach ($instances as $ins) {
    $url = rtrim($ins, '/') . "/?action=display&bridge=InstagramBridge&context=Username&u={$handle}&format=Atom";
    echo "  Testing: " . parse_url($ins, PHP_URL_HOST) . " ... ";
    $xml = http_get($url, 20);
    if ($xml && strpos($xml, '<entry') !== false) {
        preg_match_all('/<entry>/', $xml, $m);
        echo "SUCCESS - " . count($m[0]) . " entries\n";
    } else {
        echo "FAILED\n";
    }
}

// 2. Imginn
echo "\n[2] Imginn:\n";
$url = "https://imginn.com/{$handle}/";
echo "  Testing: imginn.com ... ";
$html = http_get($url, 30);
if ($html) {
    preg_match_all('/\/p\/([A-Za-z0-9_-]+)\//', $html, $matches);
    $shortcodes = array_unique($matches[1] ?? []);
    echo "SUCCESS - Found " . count($shortcodes) . " shortcodes\n";
    if (count($shortcodes) > 0) {
        echo "  Shortcodes: " . implode(', ', array_slice($shortcodes, 0, 10)) . "\n";
    }
} else {
    echo "FAILED - No response\n";
}

// 3. Picuki
echo "\n[3] Picuki:\n";
$url = "https://www.picuki.com/profile/{$handle}";
echo "  Testing: picuki.com ... ";
$html = http_get($url, 30);
if ($html) {
    preg_match_all('/\/media\/([A-Za-z0-9_-]+)/', $html, $matches);
    $ids = array_unique($matches[1] ?? []);
    echo "SUCCESS - Found " . count($ids) . " media IDs\n";
    if (count($ids) > 0) {
        echo "  IDs: " . implode(', ', array_slice($ids, 0, 10)) . "\n";
    }
} else {
    echo "FAILED - No response\n";
}

// 4. Instagram Embed
echo "\n[4] Instagram Embed:\n";
$url = "https://www.instagram.com/{$handle}/channel/";
echo "  Testing: instagram.com ... ";
$html = http_get($url, 30);
if ($html) {
    preg_match_all('/\/p\/([A-Za-z0-9_-]+)\//', $html, $matches);
    $shortcodes = array_unique($matches[1] ?? []);
    echo "SUCCESS - Found " . count($shortcodes) . " shortcodes\n";
    if (count($shortcodes) > 0) {
        echo "  Shortcodes: " . implode(', ', array_slice($shortcodes, 0, 10)) . "\n";
    }
} else {
    echo "FAILED - No response (expected - Instagram blocks bots)\n";
}

// 5. Current news_mi.json
echo "\n[5] Current Local Data:\n";
$dataFile = __DIR__ . '/news_mi.json';
if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
    if (is_array($data)) {
        echo "  Total posts: " . count($data) . "\n";
        echo "  Shortcodes:\n";
        foreach ($data as $item) {
            $sc = $item['shortcode'] ?? 'N/A';
            $date = $item['date'] ?? 'N/A';
            $title = mb_substr($item['title'] ?? 'N/A', 0, 40);
            echo "    - {$sc} ({$date}): {$title}...\n";
        }
    }
} else {
    echo "  FILE NOT FOUND\n";
}

echo "\n==============================================\n";
echo "TEST COMPLETED\n";
echo "==============================================\n";
