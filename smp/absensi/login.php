<?php
require_once 'config.php';
if(isset($_SESSION['guru_id'])) header('Location: index.php');
$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM guru WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if($result && password_verify($password, $result['password'])){
        $_SESSION['guru_id'] = $result['id'];
        $_SESSION['guru_nama'] = $result['nama'];
        $_SESSION['guru_kelas'] = $result['kelas'];
        header('Location: index.php');
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Absensi SMP R Rahmat</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background: linear-gradient(135deg, #1a472a, #2d6a4f); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:Arial,sans-serif; }
.card { background:white; padding:40px; border-radius:15px; width:100%; max-width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.3); }
.logo { text-align:center; margin-bottom:25px; }
.logo h2 { color:#1a472a; font-size:20px; margin-top:10px; }
.logo p { color:#666; font-size:13px; }
.form-group { margin-bottom:20px; }
label { display:block; margin-bottom:6px; color:#333; font-weight:bold; font-size:14px; }
input { width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:15px; outline:none; }
input:focus { border-color:#1a472a; }
.btn { width:100%; padding:13px; background:#1a472a; color:white; border:none; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; }
.btn:hover { background:#2d6a4f; }
.error { background:#ffe0e0; color:#c00; padding:10px; border-radius:6px; margin-bottom:15px; font-size:14px; text-align:center; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h2>🏫 SMP R Rahmat</h2>
    <p>Sistem Absensi Siswa</p>
  </div>
  <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Masukkan username" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password" required>
    </div>
    <button type="submit" class="btn">🔐 Login</button>
  </form>
</div>
</body>
</html>
