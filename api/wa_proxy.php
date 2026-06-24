<?php
/**
 * WhatsApp Image Proxy
 * Script ini menjadi proxy untuk gambar WhatsApp Channel agar bisa ditampilkan di website
 */

$imageUrl = $_GET['url'] ?? '';

if (empty($imageUrl)) {
    http_response_code(400);
    die('No image URL provided');
}

// Validasi URL hanya dari WhatsApp domains
$allowedHosts = [
    'whatsapp.net',
    'whatsapp.com',
    'pps.whatsapp.net',
    'mmg.whatsapp.net'
];

$isAllowed = false;
$parsedUrl = parse_url($imageUrl);
$host = $parsedUrl['host'] ?? '';

foreach ($allowedHosts as $allowedHost) {
    if ($host === $allowedHost || substr($host, -(strlen($allowedHost) + 1)) === '.' . $allowedHost) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(403);
    die('Invalid image source');
}

// Fetch gambar dengan custom headers
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: image/webp,image/apng,image/*,*/*;q=0.8",
            "Referer: https://www.whatsapp.com/"
        ],
        'timeout' => 15
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$imageData = @file_get_contents($imageUrl, false, $context);

if ($imageData === false) {
    http_response_code(404);
    die('Image not found or could not be fetched');
}

// Detect content type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->buffer($imageData);

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=86400'); // Cache 1 hari
header('Access-Control-Allow-Origin: *');

// Output image
echo $imageData;