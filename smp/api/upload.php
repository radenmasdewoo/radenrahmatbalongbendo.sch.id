<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Define upload directory
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper to get full URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Assuming simple file structure: /radenrahmat/api/upload.php -> /radenrahmat/uploads/file.jpg
    // We need to return relative or absolute URL? Absolute is safer.
    // But $_SERVER['PHP_SELF'] gives the path to the script script.
    
    // Directory of the current script (inside /api)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); 
    // Parent directory (root of project)
    $rootDir = dirname($scriptDir);
    
    return $protocol . "://" . $host . $rootDir . '/uploads/';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        // Basic validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Only images and videos are allowed.']);
            exit;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('media_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $helper = __DIR__ . '/../../api/image_to_webp.php';
                $absPath = realpath($targetPath) ?: (__DIR__ . '/../' . $targetPath);
                if (file_exists($helper) && file_exists($absPath)) {
                    require_once $helper;
                    $webpPath = convertImageToWebp($absPath);
                    if ($webpPath) {
                        @unlink($absPath);
                        $filename = pathinfo($webpPath, PATHINFO_BASENAME);
                    }
                }
            }
            $url = getBaseUrl() . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded.']);
    }
} else {
    // allow CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
       http_response_code(200);
       exit; 
    }
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
