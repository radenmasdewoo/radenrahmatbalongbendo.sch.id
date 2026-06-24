<?php
header('Content-Type: application/json');

// Function to check file modification time and return relative time
function getFileStatus($filePath) {
    if (file_exists($filePath)) {
        $mtime = filemtime($filePath);
        $diff = time() - $mtime;
        
        $status = 'ok';
        if ($diff > 3600 * 24) {
            $status = 'error'; // Lebih dari 24 jam belum update
        } else if ($diff > 3600 * 2) {
            $status = 'warning'; // Lebih dari 2 jam belum update
        }
        
        return [
            'exists' => true,
            'time' => date('Y-m-d H:i:s', $mtime),
            'diff_seconds' => $diff,
            'status' => $status
        ];
    }
    return ['exists' => false, 'status' => 'error'];
}

// Cek file berita masing-masing akun untuk mengetahui kapan terakhir diupdate
$status = [
    'portal_smp' => getFileStatus('../api/news.json'),
    'portal_mi' => getFileStatus('../api/news_mi.json'),
    'mi' => getFileStatus('../mi/api/news_mi.json'),
    'smp' => getFileStatus('../smp/api/news.json')
];

echo json_encode(['success' => true, 'data' => $status]);
