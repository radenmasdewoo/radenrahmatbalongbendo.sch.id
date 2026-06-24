<?php
$files = [
    '/mnt/webdata/htdocs/abah-zuhdi.webp',
    '/mnt/webdata/htdocs/index.html',
    '/mnt/webdata/htdocs/bgfull_smp.webp'
];

foreach ($files as $file) {
    echo "File: $file\n";
    if (file_exists($file)) {
        echo "Exists: Yes\n";
        echo "Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . "\n";
        echo "Owner ID: " . fileowner($file) . "\n";
        echo "Group ID: " . filegroup($file) . "\n";
    } else {
        echo "Exists: No\n";
    }
    echo "-------------------\n";
}
?>
