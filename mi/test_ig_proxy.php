<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/api/image_proxy.php';

$shortcode = 'DV5EZmrAI2m';
echo "Testing resolution for shortcode: $shortcode\n";

echo "Kind: image\n";
$imgUrl = rr_resolve_media_from_shortcode($shortcode, 'image', false);
echo "Result Image URL: " . ($imgUrl ?: "FAILED") . "\n\n";

echo "Kind: video\n";
$vidUrl = rr_resolve_media_from_shortcode($shortcode, 'video', false);
echo "Result Video URL: " . ($vidUrl ?: "FAILED") . "\n";
?>
