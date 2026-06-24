<?php
session_start();
$_GET['action'] = 'login';
$_POST = ['user' => 'admin99', 'pass' => 'rrahmat123']; // Simplified for testing

// Re-implement logic here to avoid including files that send headers
$USERS_FILE = 'data/users.json';
$users = json_decode(file_get_contents($USERS_FILE), true);

$user = $_POST['user'];
$pass = $_POST['pass'];

if (isset($users[$user]) && password_verify($pass, $users[$user])) {
    $_SESSION['superadmin_logged_in'] = true;
    $_SESSION['current_user'] = $user;
    echo "LOGIN SUCCESS\n";
} else {
    echo "LOGIN FAILED\n";
}

// Check session
echo "SESSION superadmin_logged_in: " . ($_SESSION['superadmin_logged_in'] ? 'TRUE' : 'FALSE') . "\n";
echo "SESSION current_user: " . $_SESSION['current_user'] . "\n";
?>