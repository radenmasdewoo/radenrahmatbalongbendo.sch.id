<?php
// StoryBlast manual JWT Implementation V2 (Clean)
header('Content-Type: application/json');
require_once 'config_v2.php';

function getDb()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    catch (Exception $e) {
        return null;
    }
}

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
    return base64UrlEncode($header) . "." . base64UrlEncode($payload) . "." . base64UrlEncode(hash_hmac('sha256', base64UrlEncode($header) . "." . base64UrlEncode($payload), JWT_SECRET, true));
}

function verifyToken($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return false;
    $validSignature = base64UrlEncode(hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true));
    if ($parts[2] !== $validSignature)
        return false;
    $payloadData = json_decode(base64_decode($parts[1]), true);
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
        $userObj = ['username' => $username, 'role' => 'superadmin'];
        echo json_encode([
            'token' => generateToken($username),
            'user' => $userObj
        ]);
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
        if ($userData = verifyToken($matches[1]))
            return $userData;
    }
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

function handleGetUsers()
{
    $db = getDb();
    if (!$db) {
        echo json_encode([]);
        exit;
    }
    $stmt = $db->query("SELECT id, username, role, is_active, created_at FROM users");
    echo json_encode($stmt->fetchAll());
    exit;
}

function handleToggleUser($id)
{
    $db = getDb();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['message' => 'DB error']);
        exit;
    }
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}