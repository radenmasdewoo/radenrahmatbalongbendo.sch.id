<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);

$pattern = '/kl\s*=\s*"\/api\/storyblast\/[^"]+\?path="/';
$replacement = 'kl = "/api/storyblast/api_v3.php?path="';

if (preg_match($pattern, $content)) {
    $newContent = preg_replace($pattern, $replacement, $content);
    echo $newContent;
}
else {
    // If pattern not found, output original (or error)
    echo $content;
}