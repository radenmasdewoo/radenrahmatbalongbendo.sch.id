<?php
// StoryBlast manual JWT Implementation (HS256)

header('Content-Type: application/json');

require_once 'config.php';

function base64UrlEncode($data)
{
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function generateToken($username)
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'username' => $username,
        'exp' => time() + (7 * 24 * 60 * 60), // 7 days
        'iat' => time()
    ]);

    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyToken($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return false;

    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];

    $validSignature = base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true));

    if ($signature !== $validSignature)
        return false;

    $payloadData = json_decode(base64_decode($payload), true);
    if (!$payloadData || $payloadData['exp'] < time())
        return false;

    return $payloadData;
}

function handleLogin()
{
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $token = generateToken($username);
        echo json_encode(['token' => $token]);
    }
    else {
        http_response_code(401);
        echo json_encode(['message' => 'Username atau password salah']);
    }
    exit;
}

function requireAuth()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $userData = verifyToken($matches[1]);
        if ($userData) {
            return $userData;
        }
    }

    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}