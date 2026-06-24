<?php
$oldFile = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$tempFile = '/mnt/webdata/htdocs/mi/api/storyblast/temp_fixed.js';
$content = file_get_contents($oldFile);

$pattern = '/kl\s*=\s*"\/api\/storyblast\/[^"]+\?path="/';
$replacement = 'kl = "/api/storyblast/api_v3.php?path="';

if (preg_match($pattern, $content)) {
    echo "Pattern found.\n";
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== null) {
        if (file_put_contents($tempFile, $newContent)) {
            echo "Successfully created TEMP patched JS file: $tempFile\n";
            echo "Size: " . strlen($newContent) . "\n";
        }
        else {
            echo "FAILED to write TEMP file.\n";
        }
    }
}
else {
    echo "Pattern NOT found.\n";
}