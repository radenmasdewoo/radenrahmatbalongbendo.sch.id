<?php
header('Content-Type: text/plain');
echo "=== PERMISSION FIXER ===\n";

$paths = [
    '/mnt/webdata/htdocs/api/sync_log.txt',
    '/mnt/webdata/htdocs/api/news.json',
    '/mnt/webdata/htdocs/mi/api/news_mi.json',
    '/mnt/webdata/htdocs/mi/api/sync_log_mi.txt',
    '/mnt/webdata/htdocs/webmail_folder/logs/errors.log'
];

foreach ($paths as $path) {
    echo "Processing " . basename($path) . "... ";
    if (!file_exists($path)) {
        // Try to create it
        if (@file_put_contents($path, "")) {
            echo "CREATED. ";
        } else {
            echo "FAILED TO CREATE. ";
            continue;
        }
    }
    
    if (@chmod($path, 0666)) {
        echo "CHMOD 666 SUCCESS.\n";
    } else {
        echo "CHMOD FAILED. (Current owner: " . posix_getpwuid(fileowner($path))['name'] . ")\n";
    }
}

// Ensure ig_cache directory contents are writable
$cacheDir = '/mnt/webdata/htdocs/mi/api/ig_cache/';
if (is_dir($cacheDir)) {
    echo "\nProcessing ig_cache directory...\n";
    $files = glob($cacheDir . "*");
    foreach ($files as $file) {
        if (is_file($file)) {
            if (@chmod($file, 0666)) {
                // success
            }
        }
    }
    echo "Done processing cache files.\n";
}

echo "\nDone.\n";
?>
