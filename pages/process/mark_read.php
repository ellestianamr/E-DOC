<?php
include("db.php");
session_start();

if (isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $id_user = $_SESSION['user_id'];

  mysqli_query($koneksi, "
    UPDATE notifications
    SET is_read = 1,
    read_at = NOW()
    WHERE id = $id AND id_user = $id_user
  ");

  echo 'OK';
}
?>
