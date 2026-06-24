<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);

// Target ANY version of the api file and replace with api_v3.php
$pattern = '/kl\s*=\s*"\/api\/storyblast\/[^"]+\?path="/';
$replacement = 'kl = "/api/storyblast/api_v3.php?path="';

if (preg_match($pattern, $content)) {
    echo "Pattern found.\n";
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== null) {
        file_put_contents($file, $newContent);
        echo "Successfully patched JS file to api_v3.php.\n";
    }
    else {
        echo "Regex replace failed.\n";
    }
}
else {
    echo "Pattern NOT found even with regex.\n";
}