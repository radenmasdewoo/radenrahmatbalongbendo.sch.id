<?php
$instances = [
    'https://rsshub.app',
    'https://rsshub.rssforever.com',
    'https://rsshub.feeded.xyz',
    'https://rss.shab.fun',
    'https://rsshub.moeyy.cn',
    'https://rsshub.bloat.cat',
    'https://hub.allworldit.com/rsshub',
    'https://rsshub.pseudoyu.com'
];

$handle = 'osis_smprrahmat';

foreach ($instances as $instance) {
    $url = $instance . '/instagram/user/' . $handle;
    echo "Testing $url ... ";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED ($httpCode): $error\n";
    }
}
?>