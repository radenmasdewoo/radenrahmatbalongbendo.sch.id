<?php
// TEMPORARY PASSWORD RESET SCRIPT
// Run this script via web browser or command line to reset superadmin password

$USERS_FILE = 'data/users.json';
$new_pass = '.Kmzwa88saa';
$target_user = 'superadmin';

if (!file_exists($USERS_FILE)) {
    die("Error: users.json not found at $USERS_FILE");
}

$users = json_decode(file_get_contents($USERS_FILE), true);
$users[$target_user] = password_hash($new_pass, PASSWORD_BCRYPT);

if (file_put_contents($USERS_FILE, json_encode($users, JSON_PRETTY_PRINT))) {
    echo "<h1>Password Reset Successful!</h1>";
    echo "<p>User: <b>$target_user</b> has been updated with the new password.</p>";
    echo "<p><i>Please DELETE this script immediately for security.</i></p>";
} else {
    echo "<h1>Error</h1><p>Failed to update users.json.</p>";
}
?>