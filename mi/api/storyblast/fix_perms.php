<?php
$dirs = [
    '/mnt/webdata/htdocs/mi/api/storyblast/uploads/',
    '/mnt/webdata/htdocs/mi/api/storyblast/logs/'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    chmod($dir, 0777);
    echo "$dir: " . (is_writable($dir) ? "WRITABLE" : "NOT WRITABLE") . "\n";
}