<?php
$source = '/mnt/webdata/htdocs/abah-zuhdi.webp.bak';
$dest = '/mnt/webdata/htdocs/abah-zuhdi.webp';

if (copy($source, $dest)) {
    echo "Successfully restored $dest from $source\n";
} else {
    echo "Failed to copy $source to $dest\n";
}

$source_jpg = '/mnt/webdata/htdocs/abah-zuhdi.jpeg.bak';
$dest_jpg = '/mnt/webdata/htdocs/abah-zuhdi.jpeg';

if (file_exists($source_jpg)) {
    if (copy($source_jpg, $dest_jpg)) {
        echo "Successfully restored $dest_jpg from $source_jpg\n";
    } else {
        echo "Failed to copy $source_jpg to $dest_jpg\n";
    }
}
?>
