<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$file = __DIR__ . '/news.json';

// Ensure file exists
if (!file_exists($file)) {
    file_put_contents($file, '[]');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    echo file_get_contents($file);
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

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $currentData = json_decode(file_get_contents($file), true);

    // If 'index' is present in input (and not 'new'), we update.
    // However, the frontend might send the whole array or a single item.
    // Let's stick to the plan: The frontend will send the news to be saved.

    // Actually, to support "Edit", we need to know if we are updating a specific index or adding new.
    // BUT, simpler approach for JSON file: Frontend sends 'action' parameter.

    // Better yet, let's just make the frontend send the COMPLETELY NEW ARRAY of news.
    // This is inefficient for huge data, but for a school website with < 100 news items, it's perfectly fine and robust.
    // It avoids race conditions? No, actually it makes them worse, but for a single admin or few admins, it's acceptable.
    // AND it matches the previous localStorage logic (save entire array).

    // Wait, the previous logic in `Admin.save` updated the array and saved to localStorage.
    // So if I accept the full array here, it's a drop-in replacement.

    if (isset($input['all_news']) && is_array($input['all_news'])) {
        // Enforce deduplication by shortcode before saving
        $deduplicated = [];
        $seenShortcodes = [];

        foreach ($input['all_news'] as $item) {
            $link = $item['link'] ?? '';
            $shortcode = '';
            if (preg_match('/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/i', $link, $m)) {
                $shortcode = $m[1];
            }
            else {
                $shortcode = md5($link . ($item['title'] ?? '')); // Fallback if not an IG link
            }

            if (!in_array($shortcode, $seenShortcodes)) {
                $seenShortcodes[] = $shortcode;
                $deduplicated[] = $item;
            }
        }

        $json = json_encode($deduplicated, JSON_PRETTY_PRINT);
        if ($json === false) {
            http_response_code(500);
            echo json_encode(['error' => 'JSON Encode failed: ' . json_last_error_msg()]);
            exit;
        }

        $result = file_put_contents($file, $json);
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Gagal menulis ke file news.json. Pastikan izin file benar.']);
        }
        else {
            echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan', 'bytes' => $result]);
        }
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Format data tidak valid.']);
    }
    exit;
}

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>