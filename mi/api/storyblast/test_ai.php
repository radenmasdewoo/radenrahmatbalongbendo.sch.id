<?php
header('Content-Type: text/plain');

$baseUrl = "https://mi.radenrahmatbalongbendo.sch.id/api/storyblast/api_v3.php?path=";

echo "--- Testing /auth/login ---\n";
$ch = curl_init($baseUrl . "/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'RaRa@2026']));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
$loginData = json_decode($response, true);
if (!$loginData || !isset($loginData['token'])) {
    die("Login failed.\n");
}
$token = $loginData['token'];

echo "\n--- Testing /ai ---\n";
$ch = curl_init($baseUrl . "/ai");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prompt' => 'Tulis satu kata saja: "Sukses"']));
$responseG = curl_exec($ch);
$httpcodeG = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcodeG\n";
echo "Response: $responseG\n";

echo "\nALL TESTS COMPLETED.\n";