<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);
$old = 'kl = "/api/storyblast/index.php?path="';
$new = 'kl = "/api/storyblast/api_v2.php?path="';

if (strpos($content, $old) !== false) {
    echo "Found old string.\n";
    $newContent = str_replace($old, $new, $content);
    file_put_contents($file, $newContent);
    echo "Successfully patched JS file.\n";
}
else {
    echo "Old string NOT found.\n";
    // Check if it already has V2
    if (strpos($content, 'api_v2.php') !== false) {
        echo "V2 already present.\n";
    }
}