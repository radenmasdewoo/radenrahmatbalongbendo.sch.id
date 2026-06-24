<?php
header('Content-Type: application/json');
$url = "https://mi.radenrahmatbalongbendo.sch.id/api/storyblast/api_v3.php?path=/auth/login";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'wrong']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo json_encode([
    'http_status' => $info['http_code'],
    'content_type' => $info['content_type'],
    'response_body' => $response,
    'included_files' => get_included_files()
], JSON_PRETTY_PRINT);