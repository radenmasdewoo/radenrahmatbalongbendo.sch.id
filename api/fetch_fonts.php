<?php
/**
 * fetch_fonts.php
 * Download webfont assets (Google fonts WOFF2 and FontAwesome CSS + webfonts)
 * into /assets and generate a local CSS file `assets/css/local-fonts.css`.
 * Run via CLI: php fetch_fonts.php
 */

$base = dirname(__DIR__); // c:\xampp\htdocs\radenrahmat
$assets = $base . '/assets';
$faDir = $assets . '/fontawesome';
$fontsDir = $assets . '/fonts';
$cssDir = $assets . '/css';

@mkdir($faDir . '/webfonts', 0755, true);
@mkdir($faDir . '/css', 0755, true);
@mkdir($fontsDir, 0755, true);
@mkdir($cssDir, 0755, true);

$urls = [
    // FontAwesome CSS and referenced webfonts (cdnjs)
    'fa_css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'fa_webfonts' => [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.ttf',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.ttf',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf'
    ],
    // Google Fonts WOFF2 (Outfit + Roboto examples)
    'google_fonts' => [
        'https://fonts.gstatic.com/s/outfit/v15/QGYvz_MVcBeNP4NJtEtqUYLknw.woff2',
        'https://fonts.gstatic.com/s/roboto/v50/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3yUBHMdazQ.woff2'
    ]
];

function download($url, $dest) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; RadenRahmatFontFetcher/1.0)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($data === false || $code >= 400 || empty($data)) {
        return ['ok' => false, 'code' => $code, 'error' => $err];
    }
    $w = file_put_contents($dest, $data);
    return ['ok' => $w !== false, 'code' => $code, 'written' => $w];
}

$results = [];

// Download FontAwesome CSS
$res = download($urls['fa_css'], $faDir . '/css/all.min.css');
$results['fa_css'] = $res;

// Download FA webfonts
foreach ($urls['fa_webfonts'] as $u) {
    $bn = basename(parse_url($u, PHP_URL_PATH));
    $res = download($u, $faDir . '/webfonts/' . $bn);
    $results['fa_webfonts'][$bn] = $res;
}

// Download Google fonts
foreach ($urls['google_fonts'] as $u) {
    $bn = basename(parse_url($u, PHP_URL_PATH));
    $res = download($u, $fontsDir . '/' . $bn);
    $results['google_fonts'][$bn] = $res;
}

// Create local-fonts.css referencing the downloaded font files
$localCss = "/* Local fonts for Outfit & Roboto and FontAwesome overrides */\n";

$localCss .= "@font-face { font-family: 'Outfit'; font-style: normal; font-weight: 400; src: url('/assets/fonts/" . basename($urls['google_fonts'][0]) . "') format('woff2'); font-display: swap; }\n";
$localCss .= "@font-face { font-family: 'Roboto'; font-style: normal; font-weight: 400; src: url('/assets/fonts/" . basename($urls['google_fonts'][1]) . "') format('woff2'); font-display: swap; }\n";

// FontAwesome: ensure font-face definitions exist matching the CSS expectations
$localCss .= "@font-face { font-family: 'Font Awesome 6 Brands'; font-style: normal; font-weight: 400; src: url('/assets/fontawesome/webfonts/fa-brands-400.woff2') format('woff2'); font-display: swap; }\n";
$localCss .= "@font-face { font-family: 'Font Awesome 6 Free'; font-style: normal; font-weight: 400; src: url('/assets/fontawesome/webfonts/fa-regular-400.woff2') format('woff2'); font-display: swap; }\n";
$localCss .= "@font-face { font-family: 'Font Awesome 6 Free'; font-style: normal; font-weight: 900; src: url('/assets/fontawesome/webfonts/fa-solid-900.woff2') format('woff2'); font-display: swap; }\n";

file_put_contents($cssDir . '/local-fonts.css', $localCss);

echo json_encode(['results' => $results, 'note' => 'Fonts downloaded to /assets/fonts and /assets/fontawesome/webfonts. CSS at /assets/css/local-fonts.css']);