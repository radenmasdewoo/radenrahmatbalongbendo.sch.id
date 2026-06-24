<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);

if (preg_match_all('/kl\s*=\s*"[^"]+"/', $content, $matches, PREG_OFFSET_CAPTURE)) {
    foreach ($matches[0] as $match) {
        echo "Found: " . $match[0] . " at offset " . $match[1] . "\n";
        // Show 20 characters before and after
        $start = max(0, $match[1] - 20);
        $len = strlen($match[0]) + 40;
        echo "Context: " . substr($content, $start, $len) . "\n\n";
    }
}
else {
    echo "kl version NOT found with double quotes. Trying single quotes...\n";
    if (preg_match_all("/kl\s*=\s*'[^']+'/", $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            echo "Found: " . $match[0] . " at offset " . $match[1] . "\n";
        }
    }
    else {
        echo "NOT FOUND AT ALL\n";
        echo "First 500 chars: " . substr($content, 0, 500) . "\n";
    }
}