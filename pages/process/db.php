<?php
$koneksi = mysqli_connect("localhost", "root", "", "e-doc_db");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$koneksi->query("SET time_zone = '+07:00'");
date_default_timezone_set('Asia/Jakarta');
?>
