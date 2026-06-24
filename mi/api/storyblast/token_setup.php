<?php
/**
 * StoryBlast Token Setup Admin
 * Halaman ini mengonversi short-lived token → long-lived → permanent Page Token
 * Lalu auto-update config_v2.php
 */

define('APP_ID', '2018343639040281');
define('APP_SECRET', '957cac3555021533c473f18bd29ad054');
define('IG_APP_ID', '1247116110925821');
define('IG_SECRET', '653d65362cbb39a71dcc514c12457161');
define('CONFIG_FILE', __DIR__ . '/config_v2.php');

// Simple auth
define('SETUP_PASS', 'RaRa@2026');

function curlGet($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

function updateConfig($key, $value)
{
    $content = file_get_contents(CONFIG_FILE);
    // Ganti nilai define yang sudah ada
    $content = preg_replace(
        "/define\('" . preg_quote($key, '/') . "',\s*'[^']*'\);/",
        "define('$key', '$value');",
        $content
    );
    file_put_contents(CONFIG_FILE, $content);
}

// ----- Proses Form -----
$results = [];
$errors = [];
$success = false;
$authed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = trim($_POST['pass'] ?? '');
    $token = trim($_POST['short_token'] ?? '');
    $waId = trim($_POST['wa_phone_id'] ?? '');
    $waToken = trim($_POST['wa_token'] ?? '');
    $waNumbers = trim($_POST['wa_numbers'] ?? '');

    if ($pass !== SETUP_PASS) {
        $errors[] = 'Password salah!';
    }
    else {
        $authed = true;

        if ($token) {
            // STEP 1: Exchange short-lived → long-lived user token
            $r1 = curlGet("https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=" . APP_ID . "&client_secret=" . APP_SECRET . "&fb_exchange_token=" . urlencode($token));
            if (isset($r1['data']['access_token'])) {
                $longLived = $r1['data']['access_token'];
                $results[] = ['step' => '1. Long-Lived User Token', 'status' => 'OK', 'note' => substr($longLived, 0, 40) . '...'];

                // STEP 2: Dapatkan Page ID & Page Access Token (permanent)
                $r2 = curlGet("https://graph.facebook.com/v20.0/me/accounts?access_token=" . urlencode($longLived));
                if (!empty($r2['data']['data'])) {
                    $pages = $r2['data']['data'];
                    // Cari page dengan fan_count tertinggi atau ambil yang pertama
                    $page = $pages[0];
                    foreach ($pages as $p) {
                        if (stripos($p['name'], 'Raden Rahmat') !== false || stripos($p['name'], 'MI') !== false) {
                            $page = $p;
                            break;
                        }
                    }
                    $pageId = $page['id'];
                    $pageToken = $page['access_token'];
                    $pageName = $page['name'];

                    $results[] = ['step' => '2. Page Access Token (Permanent)', 'status' => 'OK', 'note' => "Page: $pageName (ID: $pageId)"];

                    updateConfig('FB_PAGE_ACCESS_TOKEN', $pageToken);
                    updateConfig('FB_PAGE_ID', $pageId);
                    updateConfig('IG_ACCESS_TOKEN', $pageToken);

                    // STEP 3: Cari IG User ID
                    $r3 = curlGet("https://graph.facebook.com/v20.0/$pageId?fields=instagram_business_account&access_token=" . urlencode($pageToken));
                    if (!empty($r3['data']['instagram_business_account']['id'])) {
                        $igId = $r3['data']['instagram_business_account']['id'];
                        updateConfig('IG_USER_ID', $igId);
                        $results[] = ['step' => '3. Instagram Business ID', 'status' => 'OK', 'note' => "IG User ID: $igId"];
                    }
                    else {
                        $results[] = ['step' => '3. Instagram Business ID', 'status' => 'SKIP', 'note' => 'Tidak ditemukan. Pastikan IG Business terhubung ke Facebook Page.'];
                    }

                    $success = true;
                }
                else {
                    $errors[] = 'Gagal ambil Page list: ' . ($r2['data']['error']['message'] ?? 'Unknown');
                }
            }
            else {
                $errors[] = 'Gagal exchange token: ' . ($r1['data']['error']['message'] ?? 'Token mungkin sudah expired. Generate token baru dari Explorer.');
            }
        }

        // Update WA config jika diisi
        if ($waId) {
            updateConfig('WA_PHONE_NUMBER_ID', $waId);
            $results[] = ['step' => '4. WA Phone Number ID', 'status' => 'OK', 'note' => $waId];
        }
        if ($waToken) {
            updateConfig('WA_ACCESS_TOKEN', $waToken);
            $results[] = ['step' => '5. WA Access Token', 'status' => 'OK', 'note' => substr($waToken, 0, 30) . '...'];
        }
        if ($waNumbers) {
            updateConfig('WA_TARGET_NUMBERS', $waNumbers);
            $results[] = ['step' => '6. WA Target Numbers', 'status' => 'OK', 'note' => $waNumbers];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StoryBlast Token Setup</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 30px 16px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
        }

        h1 {
            color: #38bdf8;
            font-size: 1.6rem;
            margin-bottom: 6px;
        }

        p.sub {
            color: #94a3b8;
            margin-bottom: 28px;
            font-size: .9rem;
        }

        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #334155;
        }

        .card h2 {
            color: #94a3b8;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: .85rem;
            color: #94a3b8;
            margin-bottom: 6px;
            margin-top: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e2e8f0;
            font-size: .9rem;
            outline: none;
            transition: border .2s;
        }

        input:focus,
        textarea:focus {
            border-color: #38bdf8;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
            word-break: break-all;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: opacity .2s;
        }

        .btn:hover {
            opacity: .88;
        }

        .result {
            margin-top: 20px;
        }

        .result-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #0f172a;
            border: 1px solid #1e3a5f;
        }

        .badge {
            padding: 2px 10px;
            border-radius: 99px;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .badge.ok {
            background: #166534;
            color: #86efac;
        }

        .badge.skip {
            background: #78350f;
            color: #fde68a;
        }

        .badge.err {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .step-name {
            font-weight: 600;
            font-size: .88rem;
        }

        .step-note {
            font-size: .8rem;
            color: #94a3b8;
            margin-top: 3px;
            word-break: break-all;
        }

        .error-box {
            background: #450a0a;
            border: 1px solid #7f1d1d;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            color: #fca5a5;
            font-size: .9rem;
        }

        .success-box {
            background: #052e16;
            border: 1px solid #166534;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            color: #86efac;
            font-size: .9rem;
        }

        .tip {
            background: #172554;
            border: 1px solid #1e3a8a;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: .82rem;
            color: #93c5fd;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        a {
            color: #38bdf8;
        }

        small {
            display: block;
            color: #64748b;
            font-size: .75rem;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔑 StoryBlast Token Setup</h1>
        <p class="sub">Generate token permanent & update config otomatis</p>

        <?php if ($errors): ?>
        <div class="error-box">
            <?= implode('<br>', array_map('htmlspecialchars', $errors))?>
        </div>
        <?php
endif; ?>

        <?php if ($success): ?>
        <div class="success-box">✅ Token berhasil diperbarui! <a href="check_api_status.php">→ Cek Status API</a></div>
        <?php
endif; ?>

        <?php if (!empty($results)): ?>
        <div class="result card">
            <h2>📋 Hasil Proses</h2>
            <?php foreach ($results as $r): ?>
            <div class="result-item">
                <span
                    class="badge <?= strtolower($r['status']) === 'ok' ? 'ok' : (strtolower($r['status']) === 'skip' ? 'skip' : 'err')?>">
                    <?= htmlspecialchars($r['status'])?>
                </span>
                <div>
                    <div class="step-name">
                        <?= htmlspecialchars($r['step'])?>
                    </div>
                    <div class="step-note">
                        <?= htmlspecialchars($r['note'])?>
                    </div>
                </div>
            </div>
            <?php
    endforeach; ?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="card">
                <h2>🔐 Autentikasi</h2>
                <label>Password Admin</label>
                <input type="password" name="pass" placeholder="RaRa@2026" required>
            </div>

            <div class="card">
                <h2>📘 Facebook + Instagram Token</h2>
                <div class="tip">
                    1. Buka <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Meta Graph API
                        Explorer</a><br>
                    2. Pilih App <strong>MI-RaRa-StoryBlast</strong><br>
                    3. Klik <strong>Generate Access Token</strong> → centang semua permission FB & IG<br>
                    4. Copy token yang muncul, paste di bawah ini<br>
                    <strong>Token ini akan otomatis dikonversi ke Page Token yang PERMANENT (tidak expire)</strong>
                </div>
                <label>Short-Lived User Token (dari Explorer)</label>
                <textarea name="short_token" placeholder="EAAcrrDZBjwRkBQ..."></textarea>
                <small>Paste token yang baru saja di-generate. Token ini akan dikonversi ke permanent Page
                    Token.</small>
            </div>

            <div class="card">
                <h2>💬 WhatsApp API</h2>
                <div class="tip">
                    Cari <strong>WA Phone Number ID</strong> di:<br>
                    → <a href="https://developers.facebook.com/" target="_blank">developers.facebook.com</a> → App →
                    WhatsApp → API Setup<br>
                    → Lihat bagian <em>"Phone number ID"</em> (bukan nomor HP-nya, tapi ID-nya yang berupa angka
                    panjang)<br>
                    <strong>BUKAN</strong> sama dengan Facebook Page ID (
                    <?='993015473896539'?>)
                </div>
                <label>WhatsApp Phone Number ID</label>
                <input type="text" name="wa_phone_id" placeholder="Contoh: 123456789012345">
                <small>Biarkan kosong jika tidak ingin mengubah</small>

                <label>WhatsApp Access Token (System User Token)</label>
                <textarea name="wa_token" placeholder="EAAcrrDZBjwRkBQ..."></textarea>
                <small>Token khusus WhatsApp. Bisa berbeda dari token Facebook. Kosongkan jika tidak berubah.</small>

                <label>Nomor Target WA (pisah koma, format internasional tanpa +)</label>
                <input type="text" name="wa_numbers" value="6282221571005" placeholder="6281234567890,6285712345678">
            </div>

            <button type="submit" class="btn">⚡ Generate Permanent Token & Save Config</button>
        </form>
    </div>
</body>

</html>