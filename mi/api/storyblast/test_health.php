<?php
// Test script for StoryBlast API health and config

header('Content-Type: text/plain');
require_once 'config.php';

echo "StoryBlast Backend Health Check\n";
echo "==============================\n";

$checks = [
    'ADMIN_USERNAME' => ADMIN_USERNAME,
    'ADMIN_PASSWORD' => ADMIN_PASSWORD,
    'JWT_SECRET' => JWT_SECRET,
    'FB_PAGE_ACCESS_TOKEN' => FB_PAGE_ACCESS_TOKEN,
    'FB_PAGE_ID' => FB_PAGE_ID,
    'IG_USER_ID' => IG_USER_ID,
    'WA_PHONE_NUMBER_ID' => WA_PHONE_NUMBER_ID,
    'UPLOAD_DIR' => UPLOAD_DIR,
    'LOG_FILE' => LOG_FILE
];

$allOk = true;
foreach ($checks as $key => $value) {
    $status = "OK";
    if (strpos($value, 'GANTI_') === 0) {
        $status = "MISSING (Placeholder detected)";
        $allOk = false;
    }
    echo "$key: $status\n";
}

echo "\nResult: " . ($allOk ? "OK - All constants set" : "WARNING - Some IDs are still placeholders") . "\n";