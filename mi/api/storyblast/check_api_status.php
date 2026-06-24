<?php
// StoryBlast API Status Checker
require_once 'config_v2.php';
header('Content-Type: application/json');

function curlGet($url, $headers = [])
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($headers)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true), 'raw' => $res, 'err' => $err];
}

$result = [
    'currentUser' => trim(shell_exec('whoami')),
];

// ============================================================
// 1. FACEBOOK - cek token & page info
// ============================================================
$fbDebug = curlGet(
    "https://graph.facebook.com/debug_token?input_token=" . FB_PAGE_ACCESS_TOKEN
    . "&access_token=" . FB_PAGE_ACCESS_TOKEN
);
$fbPage = curlGet(
    "https://graph.facebook.com/" . FB_API_VERSION . "/" . FB_PAGE_ID
    . "?fields=id,name,fan_count&access_token=" . FB_PAGE_ACCESS_TOKEN
);

$fbTokenData = $fbDebug['body']['data'] ?? [];
$fbExpires = isset($fbTokenData['expires_at']) ? date('Y-m-d H:i', $fbTokenData['expires_at']) : 'N/A';
$fbIsValid = $fbTokenData['is_valid'] ?? false;

$result['facebook'] = [
    'status' => $fbPage['code'] === 200 && !isset($fbPage['body']['error']) ? 'OK' : 'ERROR',
    'page_id' => FB_PAGE_ID,
    'page_name' => $fbPage['body']['name'] ?? null,
    'token_valid' => $fbIsValid ? 'YES' : 'NO',
    'token_expires' => $fbExpires,
    'token_type' => $fbTokenData['type'] ?? 'unknown',
    'error' => $fbPage['body']['error']['message'] ?? null,
    'http_code' => $fbPage['code'],
];

// ============================================================
// 2. INSTAGRAM - cek IG Business Account
// ============================================================
$igCheck = curlGet(
    "https://graph.facebook.com/" . FB_API_VERSION . "/" . IG_USER_ID
    . "?fields=id,username,name,followers_count&access_token=" . IG_ACCESS_TOKEN
);

$result['instagram'] = [
    'status' => $igCheck['code'] === 200 && !isset($igCheck['body']['error']) ? 'OK' : 'ERROR',
    'ig_user_id' => IG_USER_ID,
    'username' => $igCheck['body']['username'] ?? null,
    'name' => $igCheck['body']['name'] ?? null,
    'followers' => $igCheck['body']['followers_count'] ?? null,
    'error' => $igCheck['body']['error']['message'] ?? null,
    'http_code' => $igCheck['code'],
];

// ============================================================
// 3. WHATSAPP - cek Phone Number ID
// ============================================================
$waCheck = curlGet(
    "https://graph.facebook.com/" . FB_API_VERSION . "/" . WA_PHONE_NUMBER_ID
    . "?fields=id,display_phone_number,verified_name,quality_rating&access_token=" . WA_ACCESS_TOKEN
);

// Coba cari WA Business Accounts yang tersedia
$waAccounts = curlGet(
    "https://graph.facebook.com/" . FB_API_VERSION . "/me/whatsapp_business_accounts?access_token=" . WA_ACCESS_TOKEN
);

// Validasi WA_PHONE_NUMBER_ID (seharusnya BERBEDA dari FB_PAGE_ID)
$waIdSameAsFb = (WA_PHONE_NUMBER_ID === FB_PAGE_ID);

$result['whatsapp'] = [
    'status' => ($waCheck['code'] === 200 && !isset($waCheck['body']['error']) && !$waIdSameAsFb) ? 'OK' : 'ERROR',
    'phone_number_id' => WA_PHONE_NUMBER_ID,
    'id_same_as_fb' => $waIdSameAsFb ? 'MASALAH: WA_PHONE_NUMBER_ID sama dengan FB_PAGE_ID!' : 'OK',
    'display_number' => $waCheck['body']['display_phone_number'] ?? null,
    'verified_name' => $waCheck['body']['verified_name'] ?? null,
    'quality_rating' => $waCheck['body']['quality_rating'] ?? null,
    'available_accounts' => $waAccounts['body']['data'] ?? [],
    'target_numbers' => WA_TARGET_NUMBERS,
    'error' => $waCheck['body']['error']['message'] ?? null,
    'http_code' => $waCheck['code'],
];

// ============================================================
// 4. UPLOAD DIR & LOG
// ============================================================
$result['upload_dir'] = [
    'path' => UPLOAD_DIR,
    'exists' => is_dir(UPLOAD_DIR) ? 'YES' : 'NO',
    'writable' => is_writable(UPLOAD_DIR) ? 'YES' : 'NO',
];
$result['log_file'] = [
    'path' => LOG_FILE,
    'dir_exists' => is_dir(dirname(LOG_FILE)) ? 'YES' : 'NO',
    'dir_writable' => is_writable(dirname(LOG_FILE)) ? 'YES' : 'NO',
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);