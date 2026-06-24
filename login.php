<?php
// /mnt/webdata/htdocs/login.php
session_start();

// Pengecekan apabila user sudah login, lewati login page
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: cms-pro-radenrahmat.html");
    exit();
}

$error_msg = '';

// Handling POST Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'api/db_config.php'; // Include konfig PDO
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Gunakan fungsi ip2long atau filter untuk IP
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // 1. HARDENED SECURITY: BRUTE FORCE PROTECTION
    // Cek percobaan gagal dalam 15 menit terakhir
    $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt_lock = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE ip_address = :ip AND status = 'failed' AND attempt_time > :time_lim");
    $stmt_lock->execute(['ip' => $ip_address, 'time_lim' => $time_limit]);
    $failed_attempts = $stmt_lock->fetchColumn();

    if ($failed_attempts >= 5) {
        $error_msg = "Akses Login Anda diblokir sementara karena terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.";
    } else {
        // 2. ANTI SQL INJECTION: PREPARED STATEMENTS
        $stmt_user = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :usr LIMIT 1");
        $stmt_user->execute(['usr' => $username]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // 3. SECURE PASSWORD VERIFICATION (BCRYPT)
        if ($user_data && password_verify($password, $user_data['password_hash'])) {
            // Login Berhasil
            
            // Catat log sukses
            $log_ins = $pdo->prepare("INSERT INTO login_logs (ip_address, username_attempt, status) VALUES (:ip, :usr, 'success')");
            $log_ins->execute(['ip' => $ip_address, 'usr' => $username]);

            // Regenerate session ID untuk anti-hijack/fixation
            session_regenerate_id(true);
            
            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role']; // superadmin atau editor
            $_SESSION['LAST_ACTIVITY'] = time();

            // Sesuai sistem default CMS (Backward compatibility untuk API lama bila masih diperlukan)
            $_SESSION['is_admin_logged_in'] = true;
            $_SESSION['superadmin_logged_in'] = ($user_data['role'] === 'superadmin');
            $_SESSION['current_user'] = $user_data['username'];
            $_SESSION['user_role'] = $user_data['role'];

            header("Location: cms-pro-radenrahmat.html");
            exit();
        } else {
            // Login Gagal
            $log_ins = $pdo->prepare("INSERT INTO login_logs (ip_address, username_attempt, status) VALUES (:ip, :usr, 'failed')");
            $log_ins->execute(['ip' => $ip_address, 'usr' => $username]);
            $error_msg = "Username atau Password Anda salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMS PRO Raden Rahmat</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --hijau: #1b5e3b;
            --hijau2: #2e7d52;
            --gelap: #0d1f16;
            --bg: #f4f9f6;
            --emas: #c9a84c;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--gelap), var(--hijau2));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: var(--emas);
        }
        .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--hijau2), var(--emas));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 16px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(46,125,82,0.3);
        }
        h1 {
            font-size: 1.4rem;
            color: var(--gelap);
            margin-bottom: 5px;
        }
        p.subtitle {
            font-size: 0.85rem;
            color: #5a7a64;
            margin-bottom: 25px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gelap);
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #ccddd5;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: 0.3s;
            outline: none;
        }
        input:focus {
            border-color: var(--hijau3);
            box-shadow: 0 0 0 3px rgba(76,175,125,0.1);
        }
        .btn-login {
            background: var(--hijau);
            color: #fff;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: var(--hijau2);
            box-shadow: 0 4px 12px rgba(27,94,59,0.3);
        }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            margin-bottom: 16px;
            border-left: 3px solid #c62828;
            text-align: left;
        }
        .footer-login {
            margin-top: 25px;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            position: absolute;
            bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo">🕌</div>
        <h1>CMS Authenticator</h1>
        <p class="subtitle">Secure Gateway - BP3NU Raden Rahmat</p>

        <?php if($error_msg): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <!-- Notifikasi Timeout misal dikirim headerLocation -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] === 'timeout'): ?>
            <div class="error" style="background: #fff8e1; color: #f57f17; border-color: #f57f17;">
                ⏱️ Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username Akses</label>
                <input type="text" id="username" name="username" placeholder="Masukkan ID Login Anda" required autofocus autocomplete="off">
            </div>
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Verifikasi Identitas & Masuk</button>
        </form>
    </div>

    <!-- Developer Signature -->
    <?php /* Developer Code: Raden Mas Dewoo for radenrahmatbalongbendo.sch.id */ ?>

</body>
</html>
