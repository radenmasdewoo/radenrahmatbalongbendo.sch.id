<?php
$files = [
    '/mnt/webdata/htdocs/mi/api/storyblast/index.php',
    '/mnt/webdata/htdocs/mi/api/storyblast/config.php',
    '/mnt/webdata/htdocs/mi/api/storyblast/auth.php'
];

foreach ($files as $file) {
    if (touch($file)) {
        echo "Touched $file\n";
    }
    else {
        echo "Failed to touch $file\n";
    }
}