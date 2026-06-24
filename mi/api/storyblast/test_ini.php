<?php
header('Content-Type: application/json');
echo json_encode([
    'included_files' => get_included_files(),
    'cwd' => getcwd(),
    'script' => $_SERVER['SCRIPT_FILENAME']
], JSON_PRETTY_PRINT);