<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
echo "File: $file\n";
echo "Is Link: " . (is_link($file) ? 'YES' : 'NO') . "\n";
if (is_link($file)) {
    echo "Target: " . readlink($file) . "\n";
}
echo "Realpath: " . realpath($file) . "\n";