<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_role = $_SESSION['role_id'];
    $nama_dokumen = trim($_POST['nama_dokumen']);
    $bidang = trim($_POST['bidang']);
    $file = $_FILES['file_dokumen'];

    $status = 'error';
    $message = '';

    // Cek kalau ada file
    if (isset($file) && $file['error'] == 0) {
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi ekstensi
        $allowed = ['xls', 'xlsx'];
        if (!in_array($file_ext, $allowed)) {
            $message = 'Format file tidak diizinkan! Hanya Excel (.xls, .xlsx)';
        } elseif ($file_size > 5 * 1024 * 1024) {
            $message = 'Ukuran file maksimal 5MB!';
        } else {
            // --- ambil semua user admin ---
            $q_admin = mysqli_query($koneksi, "SELECT id FROM users WHERE role_id = 1");
            $admin_list = [];
            while ($r = mysqli_fetch_assoc($q_admin)) {
              $admin_list[] = $r['id'];
            }

            // --- ambil semua user dalam bidang uploader ---
            $q_bidang = mysqli_query($koneksi, "SELECT id FROM users WHERE role_id = $id_role");
            $bidang_list = [];
            while ($r = mysqli_fetch_assoc($q_bidang)) {
              $bidang_list[] = $r['id'];
            }

            // --- gabungkan admin dan bidang, lalu hilangkan duplikat kalau ada ---
            $target_users = array_unique(array_merge($admin_list, $bidang_list));

            // Folder tujuan per bidang
            $upload_dir = '../../uploads/' . strtolower($bidang) . '/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            // Buat nama file unik
            $new_name = time() . '_' . preg_replace('/\s+/', '_', $file_name);
            $destination = $upload_dir . $new_name;

            // Pindahkan file
            if (move_uploaded_file($file_tmp, $destination)) {
                // Simpan ke database
                $ukuran_kb = round($file_size / 1024, 2) . ' KB';
                $sql = "INSERT INTO dokumen (id_user, nama_dokumen, bidang, nama_file, ukuran_file) 
                        VALUES ('$id_user', '$nama_dokumen', '$bidang', '$new_name', '$ukuran_kb')";
                if (mysqli_query($koneksi, $sql)) {
                  
                    foreach ($target_users as $target_id) {
                      mysqli_query($koneksi, "
                        INSERT INTO notifications (id_user, message)
                        VALUES ($target_id, '$file_name')
                      ");
                    }

                    $status = 'success';
                    $message = 'Upload berhasil!';
                } else {
                    $message = 'Gagal menyimpan ke database!';
                }
            } else {
                $message = 'Gagal upload file!';
            }
        }
    } else {
        $message = 'File belum dipilih!';
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
