<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $id         = trim($_POST['id_user']);
    $nik        = trim($_POST['nik']);
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $no_hp      = trim($_POST['no_hp']);

    $foto_lama  = $_POST['foto_lama'];
    $foto_baru  = $_FILES['foto']['name'];

    $status = 'error';
    $message = '';

    // ==========================
    // 1. Proses Ganti Foto
    // ==========================
    if (!empty($foto_baru)) {

        $tmp_file = $_FILES['foto']['tmp_name'];
        $size     = $_FILES['foto']['size'];

        // Validasi ukuran (max 3MB)
        if ($size > 3000000) {
            $status = 'error';
            $message = 'Ukuran foto terlalu besar. Maksimal 3MB.';
            exit;
        }

        // Lokasi upload
        $folder = "../../dist/img/users/";

        // Nama file unik biar aman
        $nama_file_baru = time() . "_" . $foto_baru;

        // Upload foto
        if (move_uploaded_file($tmp_file, $folder . $nama_file_baru)) {
            
            // Hapus foto lama jika ada
            if (!empty($foto_lama) && file_exists($folder . $foto_lama)) {
                unlink($folder . $foto_lama);
            }

            // Set foto untuk update database
            $foto_final = $nama_file_baru;

        } else {
            $status = 'error';
            $message = 'Gagal mengupload foto baru. Silakan coba lagi!';
            exit;
        }

    } else {
        // Jika tidak upload foto → gunakan foto lama
        $foto_final = $foto_lama;
    }

    // ==========================
    // 2. Update Database
    // ==========================

    $stmt = $koneksi->prepare("
        UPDATE users 
        SET nik = ?, nama = ?, email = ?, no_hp = ?, foto_profil = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $nik, $nama, $email, $no_hp, $foto_final, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $status = 'success';
        $message = 'Profil berhasil diperbarui.';
        
    } else {
        $status = 'info';
        $message = 'Tidak ada perubahan data.';
    }

}

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.close(); // tutup loading
  <?php if (isset($message)): ?>
    Swal.fire({
      icon: '<?= $status ?>',
      title: '<?= $status == "success" ? "Berhasil" : "Gagal" ?>',
      text: '<?= addslashes($message) ?>',
      confirmButtonText: 'OK',
      confirmButtonColor: '<?= $status == "success" ? "#00a65a" : "#d33" ?>'
    }).then((result) => {
      <?php if ($status == 'success'): ?>
        if (result.isConfirmed) {
          window.location = '../../index.php?page=settings_profile';
        }
      <?php else: ?>
        history.back();
      <?php endif; ?>
    });
  <?php endif; ?>
});
</script>