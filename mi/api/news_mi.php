<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$file = __DIR__ . '/news_mi.json';

if (!file_exists($file)) {
    file_put_contents($file, '[]');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        echo '[]';
        exit;
    }

    // Deduplicate by shortcode (prefer first occurrence, typically /reel/ URL)
    $deduplicated = [];
    $seenShortcodes = [];
    foreach ($data as $item) {
        $sc = $item['shortcode'] ?? '';
        if ($sc && !in_array($sc, $seenShortcodes)) {
            $seenShortcodes[] = $sc;
            $deduplicated[] = $item;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($deduplicated);
    exit;
}

if ($method === 'POST') {
    session_start();
    if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['all_news']) || !is_array($input['all_news'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Format data tidak valid.']);
        exit;
    }
    $deduplicated = [];
    $seenShortcodes = [];
    foreach ($input['all_news'] as $item) {
        $link = $item['link'] ?? '';
        $shortcode = '';
        if (preg_match('/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $link, $m)) {
            $shortcode = $m[1];
        }
        else {
            $shortcode = md5($link . ($item['title'] ?? ''));
        }
        if (!in_array($shortcode, $seenShortcodes)) {
            $seenShortcodes[] = $shortcode;
            $deduplicated[] = $item;
        }
    }

    $result = file_put_contents($file, json_encode($deduplicated, JSON_PRETTY_PRINT));
    echo json_encode($result !== false ? ['success' => true, 'bytes' => $result] : ['success' => false, 'error' => 'Gagal menulis']);
    exit;
}

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);