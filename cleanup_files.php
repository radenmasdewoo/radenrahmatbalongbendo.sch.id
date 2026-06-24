<?php
header('Content-Type: text/plain');
$files = [
    '/mnt/webdata/htdocs/api/sync_log.txt',
    '/mnt/webdata/htdocs/api/news.json',
    '/mnt/webdata/htdocs/mi/api/news_mi.json',
    '/mnt/webdata/htdocs/mi/api/sync_log_mi.txt',
    '/mnt/webdata/htdocs/errors.log'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Attempting to delete $file... ";
        if (@unlink($file)) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED (Try manually if possible)\n";
            // Check directory permissions
            $dir = dirname($file);
            echo "  Dir: $dir, Writable: " . (is_writable($dir) ? "YES" : "NO") . "\n";
            $owner = posix_getpwuid(fileowner($dir))['name'];
            echo "  Dir Owner: $owner\n";
        }
    } else {
        echo "$file already gone or not found.\n";
    }
}
?>
