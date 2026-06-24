<?php
// StoryBlast Logging logic V2

require_once 'config_v2.php';

function saveLog($caption, $platforms, $results)
{
    $logData = [];
    if (file_exists(LOG_FILE)) {
        $logData = json_decode(@file_get_contents(LOG_FILE), true) ?: [];
    }

    $entry = [
        'id' => time() . rand(100, 999),
        'timestamp' => date('Y-m-d H:i:s'),
        'caption' => $caption,
        'platforms' => $platforms,
        'results' => $results
    ];

    array_unshift($logData, $entry);
    $logData = array_slice($logData, 0, 200); // Keep last 200

    @file_put_contents(LOG_FILE, json_encode($logData, JSON_PRETTY_PRINT));
}

function handleGetLogs()
{
    if (file_exists(LOG_FILE)) {
        echo @file_get_contents(LOG_FILE);
    }
    else {
        echo json_encode([]);
    }
    exit;
}