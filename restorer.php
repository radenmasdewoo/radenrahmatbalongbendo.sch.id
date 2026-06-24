<?php
$source = '/mnt/webdata/htdocs/abah-zuhdi.webp.bak';
$dest = '/mnt/webdata/htdocs/abah-zuhdi.webp';

if (file_exists($source)) {
    if (copy($source, $dest)) {
        echo "SUCCESS: Restored $dest from $source\n";
    } else {
        echo "ERROR: Failed to copy $source to $dest\n";
    }
} else {
    echo "ERROR: Source $source not found\n";
}
?>
