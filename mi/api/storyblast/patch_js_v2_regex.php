<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);

$pattern = '/kl\s*=\s*"\/api\/storyblast\/index\.php\?path="/';
$replacement = 'kl = "/api/storyblast/api_v2.php?path="';

if (preg_match($pattern, $content)) {
    echo "Pattern found.\n";
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== null) {
        file_put_contents($file, $newContent);
        echo "Successfully patched JS file with regex.\n";
    }
    else {
        echo "Regex replace failed.\n";
    }
}
else {
    echo "Pattern NOT found even with regex. Searching for any kl=...\n";
    if (preg_match('/kl\s*=\s*"[^"]+"/', $content, $m)) {
        echo "Found different kl: " . $m[0] . "\n";
    }
    else {
        echo "Nothing found.\n";
    }
}