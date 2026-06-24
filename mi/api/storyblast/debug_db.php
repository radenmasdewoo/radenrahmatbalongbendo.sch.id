<?php
header('Content-Type: text/plain');
require_once 'config.php';

// Try to connect to mi_storyblast first
$host = 'localhost';
$db = 'mi_storyblast';
$user = 'sb_admin';
$pass = 'RaRa@2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "Connected to $db\n";
    echo "Tables in $db:\n";
    $stmtT = $pdo->query("SHOW TABLES");
    while ($t = $stmtT->fetch(PDO::FETCH_NUM)) {
        echo " - $t[0]\n";
    }

    echo "\nPlatforms Data:\n";
    $stmtP = $pdo->query("SELECT * FROM platforms");
    while ($p = $stmtP->fetch(PDO::FETCH_ASSOC)) {
        print_r($p);
    }

    echo "\nUsers Data:\n";
    $stmtU = $pdo->query("SELECT id, username, role FROM users");
    while ($u = $stmtU->fetch(PDO::FETCH_ASSOC)) {
        print_r($u);
    }

    echo "\nLatest Logs:\n";
    $stmt = $pdo->query("SELECT * FROM post_logs ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
}
catch (Exception $e) {
    echo "Failed to connect to $db: " . $e->getMessage() . "\n";
}

// Try cms_pro_db as well
$db2 = 'cms_pro_db';
$user2 = 'cms_user';
$pass2 = 'CmsPro@2026!';
try {
    $pdo2 = new PDO("mysql:host=$host;dbname=$db2;charset=utf8mb4", $user2, $pass2);
    echo "\nConnected to $db2\n";
    $stmt2 = $pdo2->query("SHOW TABLES");
    while ($row = $stmt2->fetch(PDO::FETCH_NUM)) {
        echo "Table: " . $row[0] . "\n";
    }
}
catch (Exception $e) {
    echo "Failed to connect to $db2: " . $e->getMessage() . "\n";
}
?>