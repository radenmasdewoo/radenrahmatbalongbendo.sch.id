<?php
// StoryBlast API Router V3 (Clean / Modular)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'auth_v2.php';

$requestUri = $_SERVER['REQUEST_URI'];
$pathInput = $_GET['path'] ?? str_replace('/api/storyblast', '', explode('?', $requestUri)[0]);
$path = '/' . ltrim($pathInput, '/');
$path = rtrim($path, '/');
if ($path === '')
    $path = '/';

if ($path === '/health') {
    echo json_encode(['status' => 'ok', 'v3' => true, 'time' => date('Y-m-d H:i:s')]);
}
elseif ($path === '/auth/login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleLogin();
    }
}
elseif ($path === '/users') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        requireAuth();
        handleGetUsers();
    }
}
elseif (preg_match('#^/users/(\d+)/toggle$#', $path, $matches)) {
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        requireAuth();
        handleToggleUser($matches[1]);
    }
}
elseif ($path === '/post/story') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireAuth();
        require_once 'poster_v2.php';
        handlePostStory();
    }
}
elseif ($path === '/post/logs') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        requireAuth();
        require_once 'logger_v2.php';
        handleGetLogs();
    }
}
elseif ($path === '/ai') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireAuth();
        require_once 'ai.php';
        handleAiRequest();
    }
}
else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint not found', 'path' => $path]);
}