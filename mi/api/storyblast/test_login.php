<?php
$url = "https://mi.radenrahmatbalongbendo.sch.id/api/storyblast/api_v3.php?path=/auth/login";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'wrong']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
]);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP Status: " . $info['http_code'] . "\n";
echo "Content Type: " . $info['content_type'] . "\n";
echo "RESPONSE BODY:\n";
echo $response;
curl_close($ch);