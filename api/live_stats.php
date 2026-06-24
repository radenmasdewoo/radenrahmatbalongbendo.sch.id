<?php
/**
 * live_stats.php — GA4 Traffic Widget (Native PHP 7.2 cURL REST API implementation)
 * Bypasses Composer to avoid PHP 8.2+ platform_check fatal errors on the live server.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

// ─── CONFIG ──────────────────────────────────────────────────────────────────
$CREDENTIALS_FILE = realpath(__DIR__ . '/website-analytics-488408-8c215fdffaff.json');
$PROPERTY_ID      = '525727804'; // Dari URL GA4
$CACHE_FILE       = sys_get_temp_dir() . '/ga4_live_stats_native.json';
$CACHE_TTL        = 120; // 2 minutes

// ─── HELPER: BASE64 URL ENCODE ───────────────────────────────────────────────
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// ─── HELPER: FORMAT ANGKA ────────────────────────────────────────────────────
function fmt($n) {
    if ($n >= 1000000) return number_format($n / 1000000, 1) . 'M';
    if ($n >= 1000)     return number_format($n / 1000, 1)     . 'K';
    return (string) $n;
}

// ─── HELPER: CACHE ───────────────────────────────────────────────────────────
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) <= $CACHE_TTL) {
    $cached = json_decode(@file_get_contents($CACHE_FILE), true);
    if (is_array($cached)) {
        $cached['_cached'] = true;
        $cached['_cache_age_sec'] = time() - filemtime($CACHE_FILE);
        echo json_encode($cached, JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ─── 1. OAUTH2: GET ACCESS TOKEN ─────────────────────────────────────────────
try {
    if (!file_exists($CREDENTIALS_FILE)) throw new Exception("Credentials file missing.");
    $keyData = json_decode(file_get_contents($CREDENTIALS_FILE), true);
    
    $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $payload = base64url_encode(json_encode([
        'iss'   => $keyData['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud'   => $keyData['token_uri'],
        'exp'   => $now + 3600,
        'iat'   => $now
    ]));
    
    $signature = '';
    openssl_sign("$header.$payload", $signature, $keyData['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$header.$payload." . base64url_encode($signature);
    
    $ch = curl_init($keyData['token_uri']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]));
    $tokenRes = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($tokenRes, true);
    if (empty($tokenData['access_token'])) throw new Exception("Failed to get GA4 Access Token.");
    $accessToken = $tokenData['access_token'];

    // ─── 2. CALL GA4 REST API ────────────────────────────────────────────────
    function callGa4Api($endpoint, $postData, $token) {
        $ch = curl_init("https://analyticsdata.googleapis.com/v1beta/" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    $property = "properties/$PROPERTY_ID";

    // A. Realtime Active Users
    $activeUsers = 0;
    $rtData = callGa4Api("$property:runRealtimeReport", ["metrics" => [["name" => "activeUsers"]]], $accessToken);
    if (!empty($rtData['rows'][0]['metricValues'][0]['value'])) {
        $activeUsers = (int) $rtData['rows'][0]['metricValues'][0]['value'];
    }

    // B. Today Sessions
    $todaySessions = 0;
    $todayData = callGa4Api("$property:runReport", [
        "dateRanges" => [["startDate" => "today", "endDate" => "today"]],
        "metrics" => [["name" => "sessions"]]
    ], $accessToken);
    if (!empty($todayData['rows'][0]['metricValues'][0]['value'])) {
        $todaySessions = (int) $todayData['rows'][0]['metricValues'][0]['value'];
    }

    // C. Total Sessions (from 2024-01-01)
    $totalSessions = 0;
    $totalData = callGa4Api("$property:runReport", [
        "dateRanges" => [["startDate" => "2024-01-01", "endDate" => "today"]],
        "metrics" => [["name" => "sessions"]]
    ], $accessToken);
    if (!empty($totalData['rows'][0]['metricValues'][0]['value'])) {
        $totalSessions = (int) $totalData['rows'][0]['metricValues'][0]['value'];
    }

    // D. Chart 7 Days Sessions
    $chartBars = [];
    $chartData = callGa4Api("$property:runReport", [
        "dateRanges" => [["startDate" => "6daysAgo", "endDate" => "today"]],
        "dimensions" => [["name" => "date"]],
        "metrics" => [["name" => "sessions"]]
    ], $accessToken);

    $dayMap = [];
    if (!empty($chartData['rows'])) {
        foreach ($chartData['rows'] as $row) {
            $rawDate = $row['dimensionValues'][0]['value']; // YYYYMMDD
            $dateKey = substr($rawDate, 0, 4) . '-' . substr($rawDate, 4, 2) . '-' . substr($rawDate, 6, 2);
            $dayMap[$dateKey] = (int) $row['metricValues'][0]['value'];
        }
    }

    $maxSessions = 1;
    for ($i = 6; $i >= 0; $i--) {
        $ts = strtotime("-$i days");
        $dateKey = date('Y-m-d', $ts);
        $sessions = isset($dayMap[$dateKey]) ? $dayMap[$dateKey] : 0;
        $maxSessions = max($maxSessions, $sessions);
        $chartBars[] = [
            'date' => $dateKey,
            'label' => date('D', $ts),
            'sessions' => $sessions
        ];
    }

    foreach ($chartBars as &$bar) {
        $bar['height_pct'] = max(5, (int) round(($bar['sessions'] / $maxSessions) * 100));
    }
    unset($bar);

    // ─── BUILD RESPONSE ──────────────────────────────────────────────────────
    $result = [
        'active_users'     => $activeUsers,
        'active_users_fmt' => (string) $activeUsers,
        'today_sessions'   => $todaySessions,
        'today_fmt'        => fmt($todaySessions),
        'total_sessions'   => $totalSessions,
        'total_fmt'        => fmt($totalSessions),
        'chart_7d'         => $chartBars,
        'property_id'      => $PROPERTY_ID,
        'generated_at'     => date('Y-m-d H:i:s'),
        '_cached'          => false,
        '_cache_age_sec'   => 0
    ];

    @file_put_contents($CACHE_FILE, json_encode($result, JSON_UNESCAPED_SLASHES));
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'API Client Error: ' . $e->getMessage()]);
}