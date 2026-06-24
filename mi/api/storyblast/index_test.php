<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'test', 'uri' => $_SERVER['REQUEST_URI']]);
?>