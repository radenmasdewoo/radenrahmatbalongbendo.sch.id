<?php
session_start();
header('Content-Type: application/json');

$USERS_FILE = '../data/users.json';

function getUsers() {
    global $USERS_FILE;
    if (!file_exists($USERS_FILE)) return [];
    $content = file_get_contents($USERS_FILE);
    if (!$content) return [];
    return json_decode($content, true) ?: [];
}

function saveUsers($users) {
    global $USERS_FILE;
    $json = json_encode($users, JSON_PRETTY_PRINT);
    if ($json === false) return false;
    return file_put_contents($USERS_FILE, $json);
}

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    // Brute-force protection
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $last_attempt = $_SESSION['last_attempt_time'] ?? 0;
    
    if ($attempts >= 5 && (time() - $last_attempt) < 300) {
        echo json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan. Tunggu 5 menit.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $user = (string)($input['user'] ?? '');
    $pass = (string)($input['pass'] ?? '');

    if (!$user || !$pass) {
        echo json_encode(['success' => false, 'message' => 'Lengkapi data login']);
        exit;
    }

    $users = getUsers();

    if (isset($users[$user]) && password_verify($pass, $users[$user])) {
        // Success
        $_SESSION['is_admin_logged_in'] = true;
        $_SESSION['current_user'] = $user;
        $_SESSION['user_role'] = ($user === 'superadmin') ? 'superadmin' : 'admin';
        $_SESSION['login_attempts'] = 0; // reset
        echo json_encode(['success' => true]);
    } else {
        // Failure
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_attempt_time'] = time();
        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
    }
    exit;
}

if ($action === 'check') {
    $loggedIn = isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true;
    echo json_encode([
        'logged_in' => $loggedIn,
        'user' => $loggedIn ? ($_SESSION['current_user'] ?? 'unknown') : null,
        'role' => $loggedIn ? ($_SESSION['user_role'] ?? 'admin') : null
    ]);
    exit;
}

if ($action === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Protected Actions (Must be logged in)
if (!isset($_SESSION['is_admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'update_profile') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $oldUser = $_SESSION['current_user'];
    $newUser = (string)($input['new_user'] ?? '');
    $newPass = (string)($input['new_pass'] ?? '');

    if (!$newUser) {
        echo json_encode(['success' => false, 'message' => 'Username baru tidak boleh kosong']);
        exit;
    }

    $users = getUsers();

    // If username changes, check if new username exists
    if ($newUser !== $oldUser && isset($users[$newUser])) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit;
    }

    // Prepare data
    $passHash = $newPass ? password_hash($newPass, PASSWORD_BCRYPT) : ($users[$oldUser] ?? '');
    
    // Update logic
    if ($newUser !== $oldUser) {
        unset($users[$oldUser]);
    }
    $users[$newUser] = $passHash;
    
    saveUsers($users);

    // Update session
    $_SESSION['current_user'] = $newUser;
    $_SESSION['user_role'] = ($newUser === 'superadmin') ? 'superadmin' : 'admin';
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_pass') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $targetUser = (string)($input['username'] ?? '');
    $newPass = (string)($input['new_pass'] ?? '');

    if (!$targetUser || !$newPass) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    $users = getUsers();

    // Check permissions: Only superadmin can change others, or user can change self
    $currentUser = $_SESSION['current_user'];
    $role = $_SESSION['user_role'] ?? 'admin';
    if ($role !== 'superadmin' && $currentUser !== $targetUser) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $users[$targetUser] = password_hash($newPass, PASSWORD_BCRYPT);
    saveUsers($users);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'list_users') {
    if ($_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }
    $users = getUsers();
    $data = [];
    foreach($users as $u => $h) {
        $data[] = ['username' => $u, 'role' => ($u === 'superadmin' ? 'superadmin' : 'admin')];
    }
    echo json_encode(['success' => true, 'users' => $data]);
    exit;
}

if ($action === 'delete_user') {
    if ($_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $target = $input['username'] ?? '';
    if ($target === 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Tidak bisa menghapus superadmin']); exit;
    }
    $users = getUsers();
    if (isset($users[$target])) {
        unset($users[$target]);
        saveUsers($users);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
    }
    exit;
}

if ($action === 'add_user') {
    if ($_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $newUser = $input['username'] ?? '';
    $newPass = $input['password'] ?? '';

    if (!$newUser || !$newPass) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']); exit;
    }

    $users = getUsers();
    if (isset($users[$newUser])) {
        echo json_encode(['success' => false, 'message' => 'Username sudah terdaftar']); exit;
    }

    $users[$newUser] = password_hash($newPass, PASSWORD_BCRYPT);
    saveUsers($users);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_user') {
    if ($_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $oldUser = (string)($input['old_username'] ?? '');
    $newUser = (string)($input['new_username'] ?? '');
    $newPass = (string)($input['new_password'] ?? '');

    if (!$oldUser || !$newUser) {
        echo json_encode(['success' => false, 'message' => 'Username tidak valid']); exit;
    }

    $users = getUsers();
    if (!isset($users[$oldUser])) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']); exit;
    }

    // If username changing, check if new one exists
    if ($oldUser !== $newUser && isset($users[$newUser])) {
        echo json_encode(['success' => false, 'message' => 'Username baru sudah digunakan']); exit;
    }

    // Preserve hash if pass is empty
    $hash = $newPass ? password_hash($newPass, PASSWORD_BCRYPT) : $users[$oldUser];

    if ($oldUser !== $newUser) {
        unset($users[$oldUser]);
    }
    $users[$newUser] = $hash;
    saveUsers($users);
    
    // If we renamed ourselves, update current session
    if ($oldUser === ($_SESSION['current_user'] ?? '')) {
        $_SESSION['current_user'] = $newUser;
        $_SESSION['user_role'] = ($newUser === 'superadmin') ? 'superadmin' : 'admin';
    }
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
