<?php
require_once 'auth.php';
require_once 'config.php';

echo "Testing login logic...\n";
echo "ADMIN_USERNAME: " . ADMIN_USERNAME . "\n";
echo "JWT_SECRET length: " . strlen(JWT_SECRET) . "\n";

// Mock php://input for handleLogin
// Since we can't easily mock it without global variables or class overrides,
// let's just test generateToken directly and then simulates the logic.

$username = ADMIN_USERNAME;
$password = ADMIN_PASSWORD;

if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
    echo "Password match OK\n";
    $token = generateToken($username);
    echo "Generated Token: " . $token . "\n";
    $verified = verifyToken($token);
    echo "Verified: " . ($verified ? 'YES' : 'NO') . "\n";
}
else {
    echo "Password match FAILED\n";
}