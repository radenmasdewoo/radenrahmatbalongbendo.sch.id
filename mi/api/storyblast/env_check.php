<?php
header('Content-Type: application/json');
require_once 'auth.php';
echo json_encode([
    'included_files' => get_included_files(),
    'cwd' => getcwd(),
    'dir' => __DIR__
], JSON_PRETTY_PRINT);