<?php
header('Content-Type: text/plain');
$root_pass = '.Kmzwa88saa';
try {
    $pdo = new PDO("mysql:host=localhost", "root", $root_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Attempting to create/update cms_user...\n";
    try {
        $pdo->exec("CREATE USER IF NOT EXISTS 'cms_user'@'localhost' IDENTIFIED BY 'CmsPro@2026!'");
        echo "CREATE/IDENTIFY: OK\n";
    } catch (Exception $e) { echo "CREATE IGNORED: " . $e->getMessage() . "\n"; }
    
    try {
        $pdo->exec("ALTER USER 'cms_user'@'localhost' IDENTIFIED BY 'CmsPro@2026!'");
        echo "ALTER: OK\n";
    } catch (Exception $e) { echo "ALTER FAILED: " . $e->getMessage() . "\n"; }

    try {
        $pdo->exec("GRANT ALL PRIVILEGES ON cms_pro_db.* TO 'cms_user'@'localhost'");
        echo "GRANT: OK\n";
    } catch (Exception $e) { echo "GRANT FAILED: " . $e->getMessage() . "\n"; }
    
    $pdo->exec("FLUSH PRIVILEGES");
    echo "SUCCESS\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
