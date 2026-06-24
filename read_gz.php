<?php
header('Content-Type: text/plain');
$file = '/mnt/webdata/htdocs/mysql_all_2026-02-18_02-40.sql.gz';
if (!file_exists($file)) die("File not found");

$zp = gzopen($file, "r");
if (!$zp) die("Failed to open gz");

echo "Searching for 'cms_user' or 'GRANT'...\n";
$found = 0;
while (!gzeof($zp) && $found < 50) {
    $line = gzgets($zp, 4096);
    if (strpos($line, 'cms_user') !== false || strpos($line, 'GRANT') !== false) {
        echo $line;
        $found++;
    }
}
gzclose($zp);
echo "\nDone.\n";
?>
