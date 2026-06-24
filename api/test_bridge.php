<?php
$instances = [
    'https://rss-bridge.org/bridge01',
    'https://wtf.roflcopter.fr/rss-bridge',
    'https://rss.nixnet.services',
    'https://bridge.suumitsu.eu',
    'https://rss.bloat.cat',
    'https://rssbridge.bus-hit.me',
    'https://rb.vern.cc',
    'https://rssbridge.prenghy.org'
];

$handle = 'osis_smprrahmat';

foreach ($instances as $instance) {
    $url = rtrim($instance, '/') . "/?action=display&bridge=InstagramBridge&context=Username&u=" . $handle . "&format=Atom";
    echo "Testing $url ... ";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($httpCode === 200 && strpos($result, '<entry>') !== false) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED ($httpCode): " . substr(strip_tags($result), 0, 50) . "...\n";
    }
    curl_close($ch);
}
?>