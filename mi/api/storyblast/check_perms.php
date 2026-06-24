<?php
$file = '/mnt/webdata/htdocs/mi/storyblast/assets/index-CgDaV2Gv.js';
echo "File: $file\n";
echo "Exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
echo "Writable: " . (is_writable($file) ? 'YES' : 'NO') . "\n";
echo "Readable: " . (is_readable($file) ? 'YES' : 'NO') . "\n";
echo "Owner: " . fileowner($file) . "\n";
echo "Group: " . filegroup($file) . "\n";
echo "Perms: " . substr(sprintf('%o', fileperms($file)), -4) . "\n";

$dir = dirname($file);
echo "Dir: $dir\n";
echo "Dir Writable: " . (is_writable($dir) ? 'YES' : 'NO') . "\n";