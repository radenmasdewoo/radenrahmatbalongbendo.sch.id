<?php
// /mnt/webdata/htdocs/api/db_config.php

// Ganti kredensial ini sesuai dengan database server Anda
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_pro_db'); // Ganti dengan nama database Anda
define('DB_USER', 'cms_user');       // Ganti dengan username database Anda
define('DB_PASS', 'RaRa@2026');           // Ganti dengan password database Anda

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Hardened Security: Gunakan Exception untuk error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Hardened Security: Hindari emulasi prepared statements agar murni ditangani oleh driver MySQL (Anti SQL Injection tangguh)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Pada environment produksi, simpan ke log, jangan tampilkan pesan error detail ke user
    die("Database Connection Failed. Please contact administrator.");
}
?>