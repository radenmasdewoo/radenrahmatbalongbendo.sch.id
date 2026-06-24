<?php
// /mnt/webdata/htdocs/api/user_manager.php
require_once '../auth_check.php';
require_once 'db_config.php';

header('Content-Type: application/json');

// Only superadmin can manage users
if ($_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Super Admin yang bisa mengelola User.']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $users]);
    exit();
}

// POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if ($action === 'add') {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'editor';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi.']);
            exit();
        }

        // Cek duplicate
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = :u");
        $chk->execute(['u' => $username]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan.']);
            exit();
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)");
        
        if ($ins->execute(['u' => $username, 'p' => $hash, 'r' => $role])) {
            echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan user.']);
        }
        exit();
    }

    if ($action === 'edit') {
        $id = $input['id'] ?? 0;
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'editor';
        
        // Cek user info (Cegah modifikasi ID 1 bila perlu, tp biar fleksibel kita biarkan asal superadmin)
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID User tidak valid.']);
            exit();
        }

        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = :p, role = :r WHERE id = :id");
            $res = $upd->execute(['p' => $hash, 'r' => $role, 'id' => $id]);
        } else {
            $upd = $pdo->prepare("UPDATE users SET role = :r WHERE id = :id");
            $res = $upd->execute(['r' => $role, 'id' => $id]);
        }
        
        if ($res) {
            echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui user.']);
        }
        exit();
    }

    if ($action === 'delete') {
        $id = $input['id'] ?? 0;
        
        // Proteksi: tidak bisa hapus diri sendiri (Session User ID)
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
            exit();
        }

        $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
        if ($del->execute(['id' => $id])) {
            echo json_encode(['success' => true, 'message' => 'User berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus user.']);
        }
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
?>