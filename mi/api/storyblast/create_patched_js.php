<?php
$oldFile = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$newFile = '/mnt/webdata/htdocs/mi/storyblast/assets/index-V3-FIXED.js';
$content = file_get_contents($oldFile);

$pattern = '/kl\s*=\s*"\/api\/storyblast\/[^"]+\?path="/';
$replacement = 'kl = "/api/storyblast/api_v3.php?path="';

if (preg_match($pattern, $content)) {
    echo "Pattern found.\n";
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== null) {
        if (file_put_contents($newFile, $newContent)) {
            echo "Successfully created NEW patched JS file: $newFile\n";
            echo "New size: " . strlen($newContent) . "\n";
        }
        else {
            echo "FAILED to write NEW patched JS file.\n";
        }
    }
    else {
        echo "Regex replace failed.\n";
    }
}
else {
    echo "Pattern NOT found.\n";
}