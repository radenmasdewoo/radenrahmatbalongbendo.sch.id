#!/usr/bin/env php
<?php
/**
 * Minifikasi JS/CSS hanya untuk htdocs, mi, smp.
 * TIDAK memproses /mnt/webdata/htdocs/rdmmi atau path lain di luar ketiga root ini.
 */

$roots = [
    '/mnt/webdata/htdocs',
    '/mnt/webdata/htdocs/mi',
    '/mnt/webdata/htdocs/smp',
];

$rdmmiPath = '/mnt/webdata/htdocs/rdmmi';

function minifyCss($css) {
    $css = preg_replace('/\/\*[\s\S]*?\*\//u', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    return trim($css);
}

function minifyJs($js) {
    // Hapus komentar blok /* ... */
    $js = preg_replace('/\/\*[\s\S]*?\*\//u', '', $js);
    // Hapus baris kosong berlebih dan collapse spasi (jaga newline setelah ; untuk keamanan)
    $js = preg_replace('/\s*[\r\n]+\s*/', "\n", $js);
    $js = preg_replace('/[ \t]+/', ' ', $js);
    return trim($js);
}

$extensions = ['js' => 'min.js', 'css' => 'min.css'];
$total = 0;

foreach ($roots as $root) {
    if (!is_dir($root)) continue;
    $realRoot = realpath($root);
    if ($realRoot === false) continue;
    // Pastikan tidak ada rdmmi
    if (strpos($realRoot, $rdmmiPath) !== false) continue;

    foreach (['js', 'css'] as $ext) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $it->setMaxDepth(5);
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $path = $file->getPathname();
            if (strpos($path, $rdmmiPath) !== false) continue;
            if ($file->getExtension() !== $ext) continue;
            if (strpos($file->getFilename(), '.min.') !== false) continue;

            $content = @file_get_contents($path);
            if ($content === false) continue;

            $minPath = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.' . $extensions[$ext], $path);
            if ($minPath === $path) continue;

            $out = $ext === 'css' ? minifyCss($content) : minifyJs($content);
            if (strlen($out) > 0 && file_put_contents($minPath, $out) !== false) {
                echo $minPath . "\n";
                $total++;
            }
        }
    }
}

echo "Selesai. Total file minified: $total\n";