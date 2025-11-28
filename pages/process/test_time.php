<?php
include("db.php");

$result = mysqli_query($koneksi, "SELECT NOW() AS mysql_time");
$row = mysqli_fetch_assoc($result);

echo "<b>Waktu MySQL:</b> " . $row['mysql_time'] . "<br>";
echo "<b>Waktu PHP:</b> " . date('Y-m-d H:i:s');
?>
