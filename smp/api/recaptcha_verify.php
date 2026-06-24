<?php
/**
 * Verifikasi reCAPTCHA v2 token dengan Google siteverify.
 * Secret key disimpan di server saja (jangan di HTML/JS).
 */
header('Content-Type: application/json; charset=utf-8');

$secretKey = '6LeWJ3QsAAAAANGO80jore0BMmmgk-Qt8GKAAf4Q';

// Token dari klien (g-recaptcha-response)
$token = isset($_POST['response']) ? trim($_POST['response']) : '';
if ($token === '') {
    $token = isset($_POST['g-recaptcha-response']) ? trim($_POST['g-recaptcha-response']) : '';
}

if ($token === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Token reCAPTCHA tidak ada.',
        'error-codes' => ['missing-input-response']
    ]);
    exit;
}

$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = [
    'secret'   => $secretKey,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghubungi Google reCAPTCHA.',
        'error-codes' => ['connection-error']
    ]);
    exit;
}

$json = json_decode($result, true);
if ($json === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Respons Google tidak valid.',
        'error-codes' => ['invalid-json']
    ]);
    exit;
}

// Kembalikan respons asli Google (success, error-codes, dll)
echo json_encode($json);
