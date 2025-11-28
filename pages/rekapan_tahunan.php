<?php
$data_dokumen = [];

// --- inisialisasi variabel
$where = [];
$role = $_SESSION['role_name'] ?? '';

// --- filter bidang (hanya untuk user non-admin)
switch ($role) {
  case 'admin':
    // admin melihat semua dokumen
    break;
  case 'sekretariat':
    $where[] = "bidang = 'SEKRETARIAT'";
    break;
  case 'industri':
    $where[] = "bidang = 'INDUSTRI'"; 
    break;
  case 'hubinsyaker':
    $where[] = "bidang = 'HUBINSYAKER'";
    break;
  case 'pptk':
    $where[] = "bidang = 'PPTK'";
    break;
  default:
    // default: tidak menampilkan apa pun
    $where[] = "1=0";
    break;
}

if (isset($_GET['bidang']) && $_GET['bidang'] != '') {
  $bidang = mysqli_real_escape_string($koneksi, $_GET['bidang']);
  $where[] = "bidang = '$bidang'";
}

// --- filter berdasarkan tahun (jika ada input)
if (isset($_GET['tahun']) && $_GET['tahun'] != '') {
  $tahun = intval($_GET['tahun']);
  $where[] = "YEAR(updated_at) = '$tahun'";
}

// --- gabungkan semua kondisi WHERE
$where_sql = '';
if (count($where) > 0) {
  $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// --- eksekusi query
$sql = "SELECT * FROM dokumen $where_sql ORDER BY updated_at DESC";
$q = mysqli_query($koneksi, $sql);

// --- simpan hasil ke array
while ($r = mysqli_fetch_assoc($q)) {
  $data_dokumen[] = $r;
}

// --- tampilkan tabel
include("template/table_documents.php");
?>
