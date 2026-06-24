<?php
require_once 'config.php';
if(!isset($_SESSION['guru_id'])) header('Location: login.php');
$guru_id = $_SESSION['guru_id'];
$guru_kelas = $_SESSION['guru_kelas'];
$today = date('Y-m-d');
$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $tanggal = $_POST['tanggal'];
    $conn->query("DELETE FROM absensi WHERE kelas='$guru_kelas' AND tanggal='$tanggal' AND guru_id='$guru_id'");
    foreach($_POST['status'] as $siswa_id => $status){
        $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, kelas, tanggal, status, guru_id) VALUES (?,?,?,?,?)");
        $stmt->bind_param("isssi", $siswa_id, $guru_kelas, $tanggal, $status, $guru_id);
        $stmt->execute();
    }
    $message = 'Absensi berhasil disimpan!';
}

$tanggal_pilih = isset($_GET['tanggal']) ? $_GET['tanggal'] : $today;
$siswa_list = $conn->query("SELECT * FROM siswa WHERE kelas='$guru_kelas' ORDER BY nama");
$absensi_hari = [];
$result = $conn->query("SELECT siswa_id, status FROM absensi WHERE kelas='$guru_kelas' AND tanggal='$tanggal_pilih'");
while($row = $result->fetch_assoc()) $absensi_hari[$row['siswa_id']] = $row['status'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Isi Absensi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f4f0; font-family:Arial,sans-serif; }
.header { background:#1a472a; color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:17px; }
.back { color:white; text-decoration:none; background:rgba(255,255,255,0.2); padding:6px 12px; border-radius:5px; font-size:13px; }
.container { padding:20px; max-width:800px; margin:0 auto; }
.card { background:white; padding:20px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.tanggal-form { display:flex; gap:10px; align-items:center; margin-bottom:15px; flex-wrap:wrap; }
input[type=date] { padding:8px 12px; border:2px solid #ddd; border-radius:6px; font-size:14px; }
.btn-cari { padding:8px 16px; background:#1a472a; color:white; border:none; border-radius:6px; cursor:pointer; }
table { width:100%; border-collapse:collapse; }
th { background:#1a472a; color:white; padding:10px; text-align:left; font-size:14px; }
td { padding:10px; border-bottom:1px solid #eee; font-size:14px; }
tr:hover { background:#f9f9f9; }
.status-group { display:flex; gap:8px; flex-wrap:wrap; }
.status-group label { display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px; padding:4px 10px; border-radius:15px; border:1px solid #ddd; }
.status-group input[type=radio] { display:none; }
.status-group input[type=radio]:checked + span { font-weight:bold; }
.hadir { background:#e8f5e9; border-color:#4caf50; color:#2e7d32; }
.sakit { background:#fff3e0; border-color:#ff9800; color:#e65100; }
.izin { background:#e3f2fd; border-color:#2196f3; color:#1565c0; }
.alpha { background:#fce4ec; border-color:#f44336; color:#c62828; }
.btn-simpan { width:100%; padding:13px; background:#1a472a; color:white; border:none; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; margin-top:15px; }
.success { background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:bold; }
.no-siswa { text-align:center; color:#999; padding:30px; }
</style>
</head>
<body>
<div class="header">
  <h1>✅ Isi Absensi Kelas <?= $guru_kelas ?></h1>
  <a href="index.php" class="back">← Kembali</a>
</div>
<div class="container">
  <?php if($message): ?><div class="success">✅ <?= $message ?></div><?php endif; ?>
  <div class="card">
    <form method="GET">
      <div class="tanggal-form">
        <label><b>Tanggal:</b></label>
        <input type="date" name="tanggal" value="<?= $tanggal_pilih ?>" max="<?= $today ?>">
        <button type="submit" class="btn-cari">Tampilkan</button>
      </div>
    </form>
    <?php if($siswa_list->num_rows > 0): ?>
    <form method="POST">
      <input type="hidden" name="tanggal" value="<?= $tanggal_pilih ?>">
      <table>
        <tr><th>No</th><th>Nama Siswa</th><th>NIS</th><th>Kehadiran</th></tr>
        <?php $no=1; while($s = $siswa_list->fetch_assoc()):
          $st = isset($absensi_hari[$s['id']]) ? $absensi_hari[$s['id']] : 'Hadir';
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $s['nama'] ?></td>
          <td><?= $s['nis'] ?></td>
          <td>
            <div class="status-group">
              <?php foreach(['Hadir','Sakit','Izin','Alpha'] as $opt): ?>
              <label class="<?= strtolower($opt) ?>">
                <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $opt ?>" <?= $st==$opt?'checked':'' ?>>
                <span><?= $opt ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
      <button type="submit" class="btn-simpan">💾 Simpan Absensi</button>
    </form>
    <?php else: ?>
    <div class="no-siswa">⚠️ Belum ada data siswa untuk kelas <?= $guru_kelas ?>.<br>Minta admin untuk menambahkan data siswa.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
