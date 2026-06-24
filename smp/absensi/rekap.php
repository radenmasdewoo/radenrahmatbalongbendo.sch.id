<?php
require_once 'config.php';
if(!isset($_SESSION['guru_id'])) header('Location: login.php');
$guru_kelas = $_SESSION['guru_kelas'];
$today = date('Y-m-d');
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : $today;

$result = $conn->query("
    SELECT s.nama, s.nis, a.status 
    FROM siswa s 
    LEFT JOIN absensi a ON s.id=a.siswa_id AND a.tanggal='$tanggal' AND a.kelas='$guru_kelas'
    WHERE s.kelas='$guru_kelas' 
    ORDER BY s.nama
");
$rows = [];
$counts = ['Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpha'=>0,'Belum'=>0];
while($r = $result->fetch_assoc()){
    $rows[] = $r;
    $st = $r['status'] ?? 'Belum';
    $counts[$st]++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Absensi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f4f0; font-family:Arial,sans-serif; }
.header { background:#1a472a; color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:17px; }
.back { color:white; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 12px; border-radius:5px; font-size:13px; }
.container { padding:20px; max-width:800px; margin:0 auto; }
.card { background:white; padding:20px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.filter { display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
input[type=date] { padding:8px 12px; border:2px solid #ddd; border-radius:6px; font-size:14px; }
.btn { padding:8px 16px; background:#1a472a; color:white; border:none; border-radius:6px; cursor:pointer; }
.summary { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
.sum-item { text-align:center; padding:12px; border-radius:8px; }
.sum-hadir { background:#e8f5e9; color:#2e7d32; }
.sum-sakit { background:#fff3e0; color:#e65100; }
.sum-izin { background:#e3f2fd; color:#1565c0; }
.sum-alpha { background:#fce4ec; color:#c62828; }
.sum-item .num { font-size:24px; font-weight:bold; }
.sum-item .lbl { font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { background:#1a472a; color:white; padding:10px; text-align:left; font-size:14px; }
td { padding:10px; border-bottom:1px solid #eee; font-size:14px; }
.badge { padding:3px 10px; border-radius:12px; font-size:12px; font-weight:bold; }
.b-hadir { background:#e8f5e9; color:#2e7d32; }
.b-sakit { background:#fff3e0; color:#e65100; }
.b-izin { background:#e3f2fd; color:#1565c0; }
.b-alpha { background:#fce4ec; color:#c62828; }
.b-belum { background:#f5f5f5; color:#999; }
.btn-print { width:100%; padding:12px; background:#1565c0; color:white; border:none; border-radius:8px; font-size:15px; cursor:pointer; margin-top:10px; }
@media print { .header,.filter,.btn-print { display:none; } }
</style>
</head>
<body>
<div class="header">
  <h1>📊 Rekap Absensi Kelas <?= $guru_kelas ?></h1>
  <a href="index.php" class="back">← Kembali</a>
</div>
<div class="container">
  <div class="card">
    <form method="GET" class="filter">
      <label><b>Tanggal:</b></label>
      <input type="date" name="tanggal" value="<?= $tanggal ?>" max="<?= $today ?>">
      <button type="submit" class="btn">Tampilkan</button>
    </form>
    <div class="summary">
      <div class="sum-item sum-hadir"><div class="num"><?= $counts['Hadir'] ?></div><div class="lbl">Hadir</div></div>
      <div class="sum-item sum-sakit"><div class="num"><?= $counts['Sakit'] ?></div><div class="lbl">Sakit</div></div>
      <div class="sum-item sum-izin"><div class="num"><?= $counts['Izin'] ?></div><div class="lbl">Izin</div></div>
      <div class="sum-item sum-alpha"><div class="num"><?= $counts['Alpha'] ?></div><div class="lbl">Alpha</div></div>
    </div>
    <table>
      <tr><th>No</th><th>Nama Siswa</th><th>NIS</th><th>Status</th></tr>
      <?php $no=1; foreach($rows as $r): $st = $r['status'] ?? 'Belum'; ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= $r['nama'] ?></td>
        <td><?= $r['nis'] ?></td>
        <td><span class="badge b-<?= strtolower($st) ?>"><?= $st ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Rekap</button>
  </div>
</div>
</body>
</html>
