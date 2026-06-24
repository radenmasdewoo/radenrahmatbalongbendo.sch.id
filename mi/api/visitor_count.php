<?php
header('Content-Type: application/json');

$statsFile = __DIR__ . '/../data/visitor_stats.json';

// Initialize stats if not exists
if (!file_exists($statsFile)) {
    $initialStats = [
        'total_visitors' => 0,
        'today_visitors' => 0,
        'last_reset' => date('Y-m-d'),
        'history' => [] // [ 'YYYY-MM-DD' => count ]
    ];
    if (!is_dir(dirname($statsFile))) {
        mkdir(dirname($statsFile), 0777, true);
    }
    file_put_contents($statsFile, json_encode($initialStats));
}

$stats = json_decode(file_get_contents($statsFile), true);
$today = date('Y-m-d');

// Reset today's count if date changed
if ($stats['last_reset'] !== $today) {
    // Save yesterday's count to history
    $stats['history'][$stats['last_reset']] = $stats['today_visitors'];
    
    // Keep only last 14 days of history
    if (count($stats['history']) > 14) {
        $stats['history'] = array_slice($stats['history'], -14, 14, true);
    }
    
    $stats['today_visitors'] = 0;
    $stats['last_reset'] = $today;
}

// Simple session tracking
session_start();
if (!isset($_SESSION['visited'])) {
    $_SESSION['visited'] = true;
    $stats['total_visitors']++;
    $stats['today_visitors']++;
}

file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

// Get last 7 days for the chart
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if ($date === $today) {
        $chartData[] = $stats['today_visitors'];
    } else {
        $chartData[] = $stats['history'][$date] ?? 0;
    }
}

echo json_encode([
    'total' => $stats['total_visitors'],
    'today' => $stats['today_visitors'],
    'online' => count(glob(session_save_path() . "/*")),
    'history' => $chartData
]);