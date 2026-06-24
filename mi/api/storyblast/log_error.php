<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
    exit;

$data = file_get_contents('php://input');
if ($data) {
    file_put_contents('/tmp/js_errors.txt', date('Y-m-d H:i:s') . "\n" . $data . "\n---\n", FILE_APPEND);
    echo "Logged";
}