<?php
/**
 * localize_media.php
 * ============================================================
 * 1. Download gambar Instagram CDN → simpan lokal di ig_cache/
 * 2. Update news.json: img field → path lokal
 * 3. Auto-cleanup: hapus post + file cache lama (> 30 hari)
 * 
 * Dipanggil otomatis oleh insta_sync.php setelah sync berhasil.
 * Bisa juga dipanggil manual: ?key=radenrahmat_sync_2026
 * ============================================================
 */

if (!defined('RR_CACHE_EXPIRE_DAYS')) define('RR_CACHE_EXPIRE_DAYS', 365); // Simpan berita hingga 1 tahun
define('RR_CACHE_SUBDIR', 'ig_cache');

/**
 * Download sebuah URL gambar ke folder cache lokal.
 * Return: URL lokal relatif, atau URL asli jika gagal.
 */
function rr_localize_image(string $remoteUrl, string $cacheDir, string $shortcode = ''): string
{
    if ($remoteUrl === '') return '';

    // Sudah lokal? Kembalikan langsung
    if (str_starts_with($remoteUrl, 'api/' . RR_CACHE_SUBDIR . '/') || str_starts_with($remoteUrl, '/')) {
        return $remoteUrl;
    }

    // Buat nama file unik berdasarkan path URL (stabil meski token query berubah)
    $urlPath = parse_url($remoteUrl, PHP_URL_PATH) ?? '';
    $filename  = ($shortcode ?: md5($urlPath)) . '.jpg';
    $localPath = $cacheDir . '/' . $filename;
    $localUrl  = 'api/' . RR_CACHE_SUBDIR . '/' . $filename;

    // Sudah ada di cache dengan ukuran valid? Skip download
    if (file_exists($localPath) && filesize($localPath) > 5000) {
        return $localUrl;
    }

    if (!function_exists('curl_init')) return $remoteUrl;

    $ch = curl_init($remoteUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => '', // Auto-decompress
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (compatible; school-bot/1.0)',
            'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding: gzip, deflate, br',
            'Referer: https://www.instagram.com/',
            'Origin: https://www.instagram.com',
            'sec-fetch-dest: image',
            'sec-fetch-mode: no-cors',
            'sec-fetch-site: cross-site',
        ],
    ]);

    $body    = curl_exec($ch);
    $status  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($status !== 200 || !$body || strlen($body) < 5000) {
        error_log("rr_localize_image FAILED [{$status}] size=" . strlen($body) . " " . substr($remoteUrl, 0, 100) . ($curlErr ? " cURL: {$curlErr}" : ''));
        return $remoteUrl; // Kembalikan URL asli jika gagal
    }

    if (file_put_contents($localPath, $body) === false) {
        error_log("rr_localize_image: Gagal tulis ke {$localPath}");
        return $remoteUrl;
    }

    chmod($localPath, 0664);
    return $localUrl;
}

/**
 * Auto-cleanup: Hapus entry dari news.json yang sudah > 30 hari,
 * sekaligus hapus file cache lokal terkait.
 * 
 * Return: jumlah post yang dihapus.
 */
function rr_cleanup_old_posts(array &$items, string $cacheDir, int $expireDays = RR_CACHE_EXPIRE_DAYS): int
{
    $cutoff  = time() - ($expireDays * 86400);
    $removed = 0;

    $items = array_filter($items, function ($item) use ($cacheDir, $cutoff, &$removed) {
        // Gunakan timestamp jika ada, fallback ke parse date string
        $ts = 0;
        if (!empty($item['timestamp'])) {
            $ts = (int)$item['timestamp'];
        } elseif (!empty($item['date'])) {
            $ts = (int)strtotime($item['date']);
        }

        // Jika timestamp tidak valid, simpan saja (jangan hapus)
        if ($ts <= 0) return true;

        $isOld = ($ts < $cutoff);

        if ($isOld) {
            // Hapus file cache lokal jika ada
            $imgPath = $item['img'] ?? '';
            if ($imgPath && str_starts_with($imgPath, 'api/' . RR_CACHE_SUBDIR . '/')) {
                $localFile = $cacheDir . '/' . basename($imgPath);
                if (file_exists($localFile)) {
                    @unlink($localFile);
                }
            }
            $removed++;
        }

        return !$isOld; // Simpan hanya post yang belum expired
    });

    $items = array_values($items); // Re-index array
    return $removed;
}

/**
 * Proses sebuah news JSON file:
 * 1. Cleanup post lama (> 30 hari)
 * 2. Localize img ke lokal untuk semua post yang tersisa
 * 3. Simpan kembali ke file
 */
function rr_localize_news_images(string $newsFile, string $cacheDir): array
{
    if (!file_exists($newsFile)) return [];

    $items = json_decode(file_get_contents($newsFile), true);
    if (!is_array($items)) return [];

    $updated = false;

    // --- STEP 1: Cleanup post yang expired (> 30 hari) ---
    $removedCount = rr_cleanup_old_posts($items, $cacheDir);
    if ($removedCount > 0) {
        $updated = true;
    }

    // --- STEP 2: Localize img ke lokal ---
    foreach ($items as &$item) {
        $img = $item['img'] ?? '';

        // Hanya proses URL remote Instagram (skip yang sudah lokal)
        if ($img && preg_match('/instagram|fbcdn|cdninstagram/i', $img) && !str_starts_with($img, 'api/' . RR_CACHE_SUBDIR . '/')) {
            $shortcode = $item['shortcode'] ?? '';
            $localImg = rr_localize_image($img, $cacheDir, $shortcode);
            if ($localImg !== $img) {
                $item['img'] = $localImg;
                $updated = true;
            }
        }
        // VIDEO tidak di-cache (terlalu besar, streaming langsung dari browser)
    }
    unset($item);

    // --- STEP 3: Simpan jika ada perubahan ---
    if ($updated) {
        file_put_contents(
            $newsFile,
            json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    return $items;
}

// ============================================================
// Entrypoint: dipanggil via browser dengan key, atau via CLI
// ============================================================
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (php_sapi_name() === 'cli' || (isset($_GET['key']) && $_GET['key'] === 'radenrahmat_sync_2026')) {
    header('Content-Type: application/json; charset=utf-8');

    $cacheDir = __DIR__ . '/' . RR_CACHE_SUBDIR;
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0775, true);

    $newsFiles = [
        'SMP' => __DIR__ . '/news.json',
        'MI'  => __DIR__ . '/news_mi.json',
    ];

    $results = [];
    foreach ($newsFiles as $label => $file) {
        if (!file_exists($file)) {
            $results[$label] = ['error' => 'File tidak ditemukan'];
            continue;
        }

        // Baca sebelum proses untuk hitung cleanup
        $before     = json_decode(file_get_contents($file), true) ?? [];
        $countBefore = count($before);

        $items      = rr_localize_news_images($file, $cacheDir);
        $localCount = count(array_filter($items, fn($i) => str_starts_with($i['img'] ?? '', 'api/' . RR_CACHE_SUBDIR . '/')));
        $removed    = $countBefore - count($items);

        $results[$label] = [
            'total_after'      => count($items),
            'localized_images' => $localCount,
            'remote_remaining' => count($items) - $localCount,
            'cleaned_up_old'   => max(0, $removed),
        ];
    }

    // Hapus file orphan di ig_cache (tidak direferensikan oleh news.json mana pun)
    $allRefImgs = [];
    foreach ($newsFiles as $file) {
        if (!file_exists($file)) continue;
        $data = json_decode(file_get_contents($file), true) ?? [];
        foreach ($data as $item) {
            $img = $item['img'] ?? '';
            if (str_starts_with($img, 'api/' . RR_CACHE_SUBDIR . '/')) {
                $allRefImgs[basename($img)] = true;
            }
        }
    }

    $orphanCount = 0;
    foreach (glob($cacheDir . '/*') as $cachedFile) {
        $fname = basename($cachedFile);
        if (!isset($allRefImgs[$fname])) {
            @unlink($cachedFile);
            $orphanCount++;
        }
    }

    echo json_encode([
        'success'        => true,
        'expire_days'    => RR_CACHE_EXPIRE_DAYS,
        'orphans_removed' => $orphanCount,
        'results'        => $results,
        'cache_dir'      => $cacheDir,
        'timestamp'      => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
    }
}