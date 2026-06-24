<?php
header('Content-Type: text/plain');

$baseUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/storyblast/api_v3.php?path=";

echo "--- Testing /auth/login ---\n";
$ch = curl_init($baseUrl . "/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'RaRa@2026']));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
echo "Response: $response\n";

$loginData = json_decode($response, true);
if (!$loginData || !isset($loginData['token'])) {
    die("\nTests aborted: Login failed or invalid JSON output.\n");
}

$token = $loginData['token'];
echo "\n--- Testing /users ---\n";
$ch = curl_init($baseUrl . "/users");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$responseU = curl_exec($ch);
$httpcodeU = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcodeU\n";
echo "Response: " . substr($responseU, 0, 200) . "...\n";

echo "\n--- Testing /post/logs ---\n";
$ch = curl_init($baseUrl . "/post/logs");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$responseL = curl_exec($ch);
$httpcodeL = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcodeL\n";
echo "Response: " . substr($responseL, 0, 200) . "...\n";

echo "\nALL TESTS COMPLETED.\n";