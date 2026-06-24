<?php
// /mnt/webdata/htdocs/auth_check.php

// 1. Hardened Security: Atur konfigurasi Session HTTP-Only dan Secure sebelum session dimulai
ini_set('session.cookie_httponly', 1);
// Uncomment baris di bawah ini jika website Anda SUDAH menggunakan HTTPS secara penuh
// ini_set('session.cookie_secure', 1); 
ini_set('session.use_only_cookies', 1);

session_start();

// 2. Proteksi Session Hijacking / Fixation
// Regenerate ID session setiap kali halaman dimuat untuk menghindari Session Fixation, namun lakukan secara berkala atau hati-hati pada AJAX.
// Untuk CMS ini, kita wajib melindungi file utamanya:
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// 3. Pengecekan Login Utama
// Pastikan user sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Redirect ke halaman login yang amaan
    header("Location: login.php");
    exit();
}

// Optional: Cek waktu inactivity (Misal auto logout setelah 30 menit)
$timeout_duration = 1800; // 30 menit
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Middleware Lulus, izinkan akses ke bawah
?>