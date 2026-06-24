<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ROOT = '/mnt/webdata/htdocs';
$htmlFile = $ROOT . '/berita.html';
$cardsFile = $ROOT . '/api/ig_cache/cards_only.html';

if (!file_exists($htmlFile)) die("berita.html not found");
if (!file_exists($cardsFile)) die("cards_only.html not found");

$html = file_get_contents($htmlFile);
$cards = file_get_contents($cardsFile);

// Target the news-container div
$pattern = '/(<div class="articles-grid" id="news-container">).*?(<\/div>\s*<\/main>)/s';
$replacement = "$1\n" . $cards . "\n$2";

$newHtml = preg_replace($pattern, $replacement, $html);

if ($newHtml !== null && $newHtml !== $html) {
    file_put_contents($htmlFile, $newHtml);
    echo "SUCCESS: berita.html updated with expanded articles.";
} else {
    echo "ERROR: Could not perform injection. Check patterns or content.";
    if ($newHtml === $html) echo " Content already identical.";
}
