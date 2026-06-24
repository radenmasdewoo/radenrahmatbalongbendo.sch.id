<?php
$file = 'SELAYANG PANDANG.docx';
$zip = new ZipArchive;
if ($zip->open($file) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    // Simple way to strip XML tags to get text
    echo strip_tags($xml);
}
else {
    echo "Failed to open $file";
}
?>