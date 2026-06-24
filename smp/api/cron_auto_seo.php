<?php
/**
 * Cron: Auto-Optimize SEO & Meta Tags + Simpan 1x per hari
 * Memperbarui data/site_config.json (smp.seo_*) sehingga situs live
 * https://smp.radenrahmatbalongbendo.sch.id/ memakai meta terbaru via dynamic_loader.js
 *
 * Cara pakai (jalankan 1x per hari via cron):
 *   0 3 * * * curl -s "https://smp.radenrahmatbalongbendo.sch.id/api/cron_auto_seo.php?key=GANTI_DENGAN_KODE_RAHASIA"
 * atau PHP-CLI:
 *   0 3 * * * cd /path/to/smp/api && php -r "file_get_contents('https://smp.radenrahmatbalongbendo.sch.id/api/cron_auto_seo.php?key=GANTI_DENGAN_KODE_RAHASIA');"
 */
header('Content-Type: application/json; charset=utf-8');

$CRON_SECRET = 'smp_auto_seo_2026'; // Ganti dengan kode rahasia Anda, lalu pakai di URL ?key=...

$key = isset($_GET['key']) ? trim($_GET['key']) : '';
if ($key !== $CRON_SECRET) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing key']);
    exit;
}

$configPath = __DIR__ . '/../data/site_config.json';
if (!file_exists($configPath)) {
    echo json_encode(['success' => false, 'message' => 'site_config.json not found']);
    exit;
}

$cfg = json_decode(file_get_contents($configPath), true);
if (!is_array($cfg)) {
    echo json_encode(['success' => false, 'message' => 'Invalid config JSON']);
    exit;
}

if (!isset($cfg['pages'])) {
    $cfg['pages'] = [];
}
if (!isset($cfg['pages']['smp'])) {
    $cfg['pages']['smp'] = [];
}

$year = (int) date('Y');
$nextYear = $year + 1;
$schoolName = 'SMP R Rahmat';
$location = 'Sidoarjo';

// Judul wajib 50–70 karakter agar Rating Kesiapan Google 100 (>70 = hanya 18 poin)
$titleLong = $schoolName . ' | SMP Terbaik ' . $location . ' – PPDB ' . $year . '/' . $nextYear;
$title = (strlen($titleLong) <= 70) ? $titleLong : ($schoolName . ' | SMP Terbaik ' . $location . ' – PPDB ' . $year);

$cfg['pages']['smp']['seo_title'] = $title;
$cfg['pages']['smp']['seo_desc'] = 'SMP R Rahmat Balongbendo (NPSN 20501766) adalah SMP terbaik di Sidoarjo dengan Program Tahfidz Al-Qur\'an, karakter Islami & teknologi. PPDB ' . $year . '/' . $nextYear . ' dibuka. Daftar sekarang!';
$cfg['pages']['smp']['seo_keywords'] = 'SMP R Rahmat, SMP Balongbendo, SMP terbaik Sidoarjo, SMP Sidoarjo, Sekolah Islam Balongbendo, SMP Terakreditasi A Sidoarjo, PPDB SMP Sidoarjo ' . $year . ', NPSN 20501766, SMP NU Sidoarjo, Tahfidz SMP Sidoarjo, SMP Balongbendo Sidoarjo, smp.radenrahmatbalongbendo.sch.id';
$cfg['pages']['smp']['seo_url'] = 'https://smp.radenrahmatbalongbendo.sch.id/';

// og_image tidak di-overwrite; tetap pakai yang sudah ada bila ada
if (empty($cfg['pages']['smp']['seo_og_image'])) {
    $cfg['pages']['smp']['seo_og_image'] = '../logo-smp.webp';
}

$written = file_put_contents($configPath, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
if ($written === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to write config']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'SEO & Meta Tags auto-optimized and saved (1x daily). Live site will use new meta on next load.',
    'updated' => date('c'),
    'seo_title' => $cfg['pages']['smp']['seo_title'],
]);
