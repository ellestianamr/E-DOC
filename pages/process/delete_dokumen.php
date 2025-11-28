<?php
include("db.php");

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
    exit;
}

$id = intval($_GET['id']);

// Ambil data dulu biar bisa hapus file fisiknya
$result = mysqli_query($koneksi, "SELECT nama_file, bidang FROM dokumen WHERE id = '$id'");
if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
    exit;
}

$data = mysqli_fetch_assoc($result);
$file_path = "../../uploads/" . strtolower($data['bidang']) . "/" . $data['nama_file'];

// Hapus file fisik
if (file_exists($file_path)) unlink($file_path);

// Hapus dari database
if (mysqli_query($koneksi, "DELETE FROM dokumen WHERE id = '$id'")) {
    echo json_encode(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus dokumen']);
}
?>
