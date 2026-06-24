<?php
session_start();
header('Content-Type: application/json');

// Security Check: Only superadmin
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['user_role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database configuration (from absensi/config.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'erapor');
define('DB_PASS', 'Absensi2026!');
define('DB_NAME', 'erapor_smp');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database e-Raport gagal: ' . $conn->connect_error]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'list_guru') {
    $result = $conn->query("SELECT id, username, nama, kelas FROM guru ORDER BY nama ASC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($action === 'add_guru') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $nama = $input['nama'] ?? '';
    $kelas = $input['kelas'] ?? '';

    if (!$username || !$password || !$nama || !$kelas) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO guru (username, password, nama, kelas) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hash, $nama, $kelas);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambah guru: ' . $stmt->error]);
    }
    exit;
}

if ($action === 'delete_guru') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM guru WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus guru']);
    }
    exit;
}

if ($action === 'update_guru') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $username = $input['username'] ?? '';
    $nama = $input['nama'] ?? '';
    $kelas = $input['kelas'] ?? '';
    $password = $input['password'] ?? '';

    if (!$id || !$username || !$nama || !$kelas) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE guru SET username=?, password=?, nama=?, kelas=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $hash, $nama, $kelas, $id);
    } else {
        $stmt = $conn->prepare("UPDATE guru SET username=?, nama=?, kelas=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $nama, $kelas, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui guru']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
