<?php
$content = file_get_contents('/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js');

if (strpos($content, 'w-full h-40 bg-slate-800 border-none rounded-xl p-4 text-white placeholder-slate-500') !== false) {
    echo "Found textarea in bundle!\n";
}
else {
    echo "Not found.\n";
}

if (strpos($content, 'Tulis caption menarik di sini...') !== false) {
    echo "Found placeholder text in bundle!\n";
}
else {
    echo "Placeholder not found.\n";
}