<?php
// StoryBlast API Router

// Disable all error reporting and display to guarantee clean JSON output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'auth.php';
require_once 'poster.php';
require_once 'logger.php';

$requestUri = $_SERVER['REQUEST_URI'];
// Logging disabled due to permission issues on this server
// Support both PathInfo and Query Parameter routing (Prefer Query Param for server compatibility)
$pathInput = $_GET['path'] ?? str_replace('/api/storyblast', '', explode('?', $requestUri)[0]);
$path = '/' . ltrim($pathInput, '/');
$path = rtrim($path, '/');
if ($path === '')
    $path = '/';

switch ($path) {
    case '/health':
        echo json_encode(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
        break;

    case '/auth/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleLogin();
        }
        break;

    case '/post/story':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireAuth();
            handlePostStory();
        }
        break;

    case '/post/logs':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            requireAuth();
            handleGetLogs();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found', 'path' => $path]);
        break;
}