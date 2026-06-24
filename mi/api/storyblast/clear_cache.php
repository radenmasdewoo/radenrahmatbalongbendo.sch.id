<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset successfully.\n";
}
else {
    echo "OPcache reset function not found.\n";
}

$files = [
    __DIR__ . '/index.php',
    __DIR__ . '/config.php',
    __DIR__ . '/auth.php'
];

foreach ($files as $file) {
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($file, true);
        echo "Invalidated $file\n";
    }
}