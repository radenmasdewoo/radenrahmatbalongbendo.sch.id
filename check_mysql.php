<?php
header('Content-Type: text/plain');
$root_pass = '.Kmzwa88saa';
try {
    $pdo = new PDO("mysql:host=localhost", "root", $root_pass);
    
    echo "MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "MySQL Info: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
