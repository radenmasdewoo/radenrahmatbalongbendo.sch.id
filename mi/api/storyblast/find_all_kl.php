<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
$content = file_get_contents($file);

if (preg_match_all('/kl\s*=\s*"[^"]+"/', $content, $matches, PREG_OFFSET_CAPTURE)) {
    echo "Total occurrences: " . count($matches[0]) . "\n";
    foreach ($matches[0] as $match) {
        echo "Found: " . $match[0] . " at offset " . $match[1] . "\n";
    }
}
else {
    echo "NOT FOUND\n";
}