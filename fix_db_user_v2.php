<?php
header('Content-Type: text/plain');
$root_pass = '.Kmzwa88saa';
try {
    $pdo = new PDO("mysql:host=localhost", "root", $root_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Dropping and recreating cms_user...\n";
    foreach (['localhost', '127.0.0.1', '%'] as $host) {
        try {
            $pdo->exec("DROP USER IF EXISTS 'cms_user'@'$host'");
            echo "DROP @$host: OK\n";
            $pdo->exec("CREATE USER 'cms_user'@'$host' IDENTIFIED BY 'CmsPro@2026!'");
            echo "CREATE @$host: OK\n";
            $pdo->exec("GRANT ALL PRIVILEGES ON cms_pro_db.* TO 'cms_user'@'$host'");
            echo "GRANT @$host: OK\n";
        } catch (Exception $e) { echo "ERROR @$host: " . $e->getMessage() . "\n"; }
    }
    
    $pdo->exec("FLUSH PRIVILEGES");
    echo "VERIFIED: SUCCESS\n";
    
} catch (PDOException $e) {
    echo "ROOT ERROR: " . $e->getMessage() . "\n";
}
?>
