<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['user_id'];
    $id = intval($_POST['id']);
    $nama_dokumen = trim($_POST['nama_dokumen']);
    $bidang = trim($_POST['bidang']);
    $file = $_FILES['file_dokumen'];

    $status = 'error';
    $message = '';

    // Ambil data lama dulu (nama file lama)
    $query_old = mysqli_query($koneksi, "SELECT nama_file FROM dokumen WHERE id = '$id'");
    if (mysqli_num_rows($query_old) == 0) {
        $message = 'Data dokumen tidak ditemukan!';
    } else {
        $old = mysqli_fetch_assoc($query_old);
        $old_file = $old['nama_file'];

        // Kalau user upload file baru
        if (isset($file) && $file['error'] == 0) {
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['xls', 'xlsx'];
            if (!in_array($file_ext, $allowed)) {
                $message = 'Format file tidak diizinkan! Hanya Excel (.xls, .xlsx)';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $message = 'Ukuran file maksimal 5MB!';
            } else {
                // Folder tujuan per bidang
                $upload_dir = '../../uploads/' . strtolower($bidang) . '/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                // Buat nama file baru unik
                $new_name = time() . '_' . preg_replace('/\s+/', '_', $file_name);
                $destination = $upload_dir . $new_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Hapus file lama
                    $old_path = $upload_dir . $old_file;
                    if (file_exists($old_path)) unlink($old_path);

                    // Hitung ukuran baru
                    $ukuran_kb = round($file_size / 1024, 2) . ' KB';

                    // Update data di DB
                    $sql = "UPDATE dokumen 
                            SET nama_dokumen='$nama_dokumen', nama_file='$new_name', ukuran_file='$ukuran_kb' 
                            WHERE id='$id'";
                    if (mysqli_query($koneksi, $sql)) {
                        $status = 'success';
                        $message = 'Dokumen berhasil diperbarui (file diganti)!';
                    } else {
                        $message = 'Gagal memperbarui data di database!';
                    }
                } else {
                    $message = 'Gagal upload file baru!';
                }
            }
        } else {
            // Tidak ada file baru → update nama saja
            $sql = "UPDATE dokumen SET nama_dokumen='$nama_dokumen' WHERE id='$id'";
            if (mysqli_query($koneksi, $sql)) {
                $status = 'success';
                $message = 'Nama dokumen berhasil diperbarui!';
            } else {
                $message = 'Gagal memperbarui nama dokumen!';
            }
        }
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
          window.location = '../../index.php?page=<?= strtolower($bidang) ?>';
        }
      <?php else: ?>
        history.back();
      <?php endif; ?>
    });
  <?php endif; ?>
});
</script>
