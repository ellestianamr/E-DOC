<?php
$data_dokumen = [];

$where = "WHERE bidang = 'HUBINSYAKER'";

if (isset($_GET['tanggal']) && $_GET['tanggal'] != '') {
  // input dari form: dd/mm/yyyy → ubah ke yyyy-mm-dd
  $tgl_input = trim($_GET['tanggal']);
  $parts = explode('/', $tgl_input);
  if (count($parts) === 3) {
    $tanggal_mysql = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    $where .= "AND DATE(updated_at) = '$tanggal_mysql'";
  }
}

$q = mysqli_query($koneksi, "SELECT * FROM dokumen $where ORDER BY updated_at DESC");
while ($r = mysqli_fetch_assoc($q)) {
  $data_dokumen[] = $r;
}
include("template/table_documents.php");

?>