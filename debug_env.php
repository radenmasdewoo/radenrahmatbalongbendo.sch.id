<?php
header('Content-Type: text/plain');
echo "=== SYSTEM DIAGNOSTIC ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "User: " . get_current_user() . " (UID: " . getmyuid() . ")\n";
echo "Process User: " . posix_getpwuid(posix_geteuid())['name'] . " (EUID: " . posix_geteuid() . ")\n";

$extensions = ['curl', 'pdo_mysql', 'gd', 'mbstring', 'xml', 'openssl', 'iconv', 'json', 'session', 'zip', 'imagick'];
echo "\n=== EXTENSIONS ===\n";
foreach ($extensions as $ext) {
    echo str_pad($ext, 12) . ": " . (extension_loaded($ext) ? "OK" : "MISSING") . "\n";
}

echo "\n=== FILE AUDIT ===\n";
$paths = [
    '/mnt/webdata/htdocs/api/sync_log.txt',
    '/mnt/webdata/htdocs/api/news.json',
    '/mnt/webdata/htdocs/mi/api/ig_cache/',
    '/mnt/webdata/htdocs/mi/api/news_mi.json',
    '/mnt/webdata/htdocs/mi/api/sync_log_mi.txt',
    '/mnt/webdata/htdocs/webmail_folder/logs/errors.log'
];

foreach ($paths as $path) {
    $exists = file_exists($path);
    $writable = is_writable($path);
    $owner = $exists ? posix_getpwuid(fileowner($path))['name'] : 'N/A';
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    echo str_pad(basename($path), 20) . ": " . 
         ($exists ? ($writable ? "WRITABLE   " : "NOT WRITABLE") : "NOT FOUND   ") . 
         " [Owner: $owner, Perms: $perms]\n";
}

echo "\n=== DATABASE AUDIT ===\n";
$db_config_path = '/mnt/webdata/htdocs/api/db_config.php';
if (file_exists($db_config_path)) {
    // We parse it manually to avoid die()
    $content = file_get_contents($db_config_path);
    preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $m_host);
    preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $m_name);
    preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $m_user);
    preg_match("/define\('DB_PASS',\s*'([^']+)'\)/", $content, $m_pass);
    
    $host = $m_host[1] ?? '';
    $name = $m_name[1] ?? '';
    $user = $m_user[1] ?? '';
    $pass = $m_pass[1] ?? '';
    
    $creds = [
        ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass],
        ['host' => 'localhost', 'name' => 'mysql', 'user' => 'root', 'pass' => '.Kmzwa88saa'],
        ['host' => 'localhost', 'name' => 'cms_pro_db', 'user' => 'root', 'pass' => '.Kmzwa88saa'],
        ['host' => 'localhost', 'name' => 'cms_pro_db', 'user' => 'adminrr', 'pass' => '.Kmzwa88saa'],
        ['host' => 'localhost', 'name' => 'cms_pro_db', 'user' => 'cms_user', 'pass' => '.Kmzwa88saa'],
        ['host' => 'localhost', 'name' => 'cms_pro_db', 'user' => 'cms_user', 'pass' => 'rrahmat123'],
        ['host' => 'localhost', 'name' => 'mi_storyblast', 'user' => 'sb_admin', 'pass' => 'RaRa@2026'],
        ['host' => 'localhost', 'name' => 'roundcube_db', 'user' => 'roundcube_user', 'pass' => 'Kmzwa88saa']
    ];

    foreach ($creds as $i => $c) {
        $cHost = $c['host'];
        $cName = $c['name'];
        $cUser = $c['user'];
        $cPass = $c['pass'];
        echo "Test #$i: Testing $cName on $cHost as $cUser... ";
        try {
            $test_pdo = new PDO("mysql:host=$cHost;dbname=$cName;charset=utf8mb4", $cUser, $cPass);
            $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "PDO SUCCESS. ";
        } catch (PDOException $e) {
            echo "PDO FAILED [" . $e->getCode() . "]. ";
        }
        
        $mysqli = @new mysqli($cHost, $cUser, $cPass, $cName);
        if ($mysqli->connect_error) {
            echo "MYSQLI FAILED [" . $mysqli->connect_errno . "]\n";
        } else {
            echo "MYSQLI SUCCESS\n";
            $mysqli->close();
        }
    }
}

// Roundcube
$rc_config = '/mnt/webdata/htdocs/webmail_folder/config/config.inc.php';
if (file_exists($rc_config)) {
    $config = [];
    @include $rc_config; // Roundcube config often has recursive dependencies, be careful
    if (isset($config['db_dsnw'])) {
        echo "\nRoundcube DB Test:\n";
        $dsn = $config['db_dsnw'];
        // Parse mysql://user:pass@host/db
        if (preg_match('/mysql:\/\/([^:]+):([^@]+)@([^\/]+)\/(.+)/', $dsn, $m)) {
            $rc_user = $m[1];
            $rc_pass = $m[2];
            $rc_host = $m[3];
            $rc_db = $m[4];
            echo "Connecting to $rc_db on $rc_host as $rc_user...\n";
            try {
                $rc_pdo = new PDO("mysql:host=$rc_host;dbname=$rc_db", $rc_user, $rc_pass);
                echo "Roundcube DB: SUCCESS\n";
            } catch (PDOException $e) {
                echo "Roundcube DB: FAILED - " . $e->getMessage() . "\n";
            }
        } else {
            echo "Roundcube DSN format not recognized: $dsn\n";
        }
    }
}
?>
