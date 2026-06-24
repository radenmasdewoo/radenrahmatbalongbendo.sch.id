<?php
/**
 * Debug Script - Check why DWc-eb7gZ-z is not being saved
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate RSS-Bridge parsing
$xml = file_get_contents('https://rss.bloat.cat/?action=display&bridge=InstagramBridge&context=Username&u=miradenrahmatbalongbendo&format=Atom');

libxml_use_internal_errors(true);
$feed = simplexml_load_string($xml);

if (!$feed) {
    echo "Failed to parse XML\n";
    echo libxml_get_last_error()->message;
    exit;
}

$entries = isset($feed->channel->item) ? $feed->channel->item : (isset($feed->entry) ? $feed->entry : []);

echo "Total entries from RSS-Bridge: " . count($entries) . "\n\n";

foreach ($entries as $e) {
    $link = (isset($e->link['href']) ? (string)$e->link['href'] : (string)$e->link) ?: '';
    
    // Extract shortcode
    if (preg_match('/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $link, $m)) {
        $sc = $m[1];
    } else {
        $sc = 'N/A';
    }
    
    $pub = isset($e->pubDate) ? (string)$e->pubDate : (isset($e->published) ? (string)$e->published : '');
    $ts = strtotime($pub);
    
    $title = mb_substr(trim(strip_tags((string)$e->title)), 0, 80);
    
    $currentTime = time();
    $maxFutureSeconds = 7 * 24 * 60 * 60;
    $isValid = $ts > 0 && $ts <= ($currentTime + $maxFutureSeconds);
    
    echo "Shortcode: {$sc}\n";
    echo "  Link: {$link}\n";
    echo "  Published: {$pub}\n";
    echo "  Timestamp: {$ts} (".date('Y-m-d H:i:s', $ts).")\n";
    echo "  Current Time: {$currentTime} (".date('Y-m-d H:i:s', $currentTime).")\n";
    echo "  Max Future: ".($currentTime + $maxFutureSeconds)." (".date('Y-m-d H:i:s', $currentTime + $maxFutureSeconds).")\n";
    echo "  Valid: ".($isValid ? 'YES' : 'NO')."\n";
    echo "  Title: {$title}\n";
    echo "\n";
}
