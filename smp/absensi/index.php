<?php
require_once 'config.php';
if(!isset($_SESSION['guru_id'])) header('Location: login.php');
$guru_nama = $_SESSION['guru_nama'];
$guru_kelas = $_SESSION['guru_kelas'];
$today = date('Y-m-d');
$total_siswa = $conn->query("SELECT COUNT(*) as total FROM siswa WHERE kelas='$guru_kelas'")->fetch_assoc()['total'];
$hadir_hari_ini = $conn->query("SELECT COUNT(*) as total FROM absensi WHERE kelas='$guru_kelas' AND tanggal='$today' AND status='Hadir'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Absensi SMP R Rahmat</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f4f0; font-family:Arial,sans-serif; }
.header { background:#1a472a; color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:18px; }
.logout { color:white; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 12px; border-radius:5px; font-size:13px; }
.container { padding:20px; max-width:800px; margin:0 auto; }
.welcome { background:white; padding:20px; border-radius:10px; margin-bottom:20px; border-left:5px solid #1a472a; }
.welcome h2 { color:#1a472a; margin-bottom:5px; }
.welcome p { color:#666; font-size:14px; }
.stats { display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px; }
.stat-card { background:white; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.stat-card .number { font-size:36px; font-weight:bold; color:#1a472a; }
.stat-card .label { color:#666; font-size:13px; margin-top:5px; }
.menu { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.menu-item { background:white; padding:25px 20px; border-radius:10px; text-align:center; text-decoration:none; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:transform 0.2s; }
.menu-item:hover { transform:translateY(-3px); }
.menu-item .icon { font-size:36px; margin-bottom:10px; }
.menu-item .title { color:#1a472a; font-weight:bold; font-size:15px; }
.menu-item .desc { color:#666; font-size:12px; margin-top:5px; }
</style>
</head>
<body>
<div class="header">
  <h1>🏫 Absensi SMP R Rahmat</h1>
  <a href="logout.php" class="logout">Keluar</a>
</div>
<div class="container">
  <div class="welcome">
    <h2>Selamat datang, <?= $guru_nama ?>!</h2>
    <p>Kelas: <?= $guru_kelas ?> &nbsp;|&nbsp; <?= date('l, d F Y') ?></p>
  </div>
  <div class="stats">
    <div class="stat-card">
      <div class="number"><?= $total_siswa ?></div>
      <div class="label">Total Siswa Kelas <?= $guru_kelas ?></div>
    </div>
    <div class="stat-card">
      <div class="number"><?= $hadir_hari_ini ?></div>
      <div class="label">Hadir Hari Ini</div>
    </div>
  </div>
  <div class="menu">
    <a href="absen.php" class="menu-item">
      <div class="icon">✅</div>
      <div class="title">Isi Absensi</div>
      <div class="desc">Input kehadiran siswa hari ini</div>
    </a>
    <a href="rekap.php" class="menu-item">
      <div class="icon">📊</div>
      <div class="title">Rekap Absensi</div>
      <div class="desc">Lihat rekap per tanggal</div>
    </a>
  </div>
</div>
</body>
</html>
