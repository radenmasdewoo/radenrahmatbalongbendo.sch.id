<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'erapor');
define('DB_PASS', 'Absensi2026!');
define('DB_NAME', 'erapor_smp');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);
session_start();
?>
