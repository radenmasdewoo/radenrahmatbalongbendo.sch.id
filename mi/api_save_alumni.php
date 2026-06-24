<?php
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'upload_photo') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['photo']['tmp_name'];
        $name = basename($_FILES['photo']['name']);
        $name = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $name);
        $target = 'uploads/alumni/user_' . time() . '_' . $name;
        
        if (move_uploaded_file($tmpName, $target)) {
            echo json_encode(['status' => 'success', 'path' => $target]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Upload error.']);
    }
    exit;
}

if ($action === 'save_data') {
    $teacherData = $_POST['teacherData'] ?? '';
    $alumniData = $_POST['alumniData'] ?? '';
    
    if ($teacherData && $alumniData) {
        $content = "// FILE INI DIGUNAKAN UNTUK MENGUBAH DATA SISWA DAN PESAN GURU DENGAN MUDAH\n";
        $content .= "// Diedit otomatis melalui browser pada " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "const teacherData = " . $teacherData . ";\n\n";
        $content .= "const alumniData = " . $alumniData . ";\n";
        
        if (file_put_contents('alumni_data.js', $content) !== false) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file alumni_data.js. Pastikan permission file/folder mendukung penulisan (write).']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
?>
