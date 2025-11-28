<?php
include("pages/process/db.php");

// pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Anda harus login terlebih dahulu.</div>';
    header('Location: auth/login.php');
    exit;
}

$role = $_SESSION['role_name'];
$id_user = $_SESSION['user_id'] ;

// ambil data user saat ini
$ambil = $koneksi->query("SELECT * FROM users WHERE id = '$id_user'");
$hasil = $ambil->fetch_assoc();

$path = "dist/img/users/";
$foto_profil = $hasil['foto_profil'];
if (empty($foto_profil) || !file_exists($path . $foto_profil)) {
    $foto_profil = "avatar3.png"; // pastikan file ini memang ada
}

// jika form disubmit (edit password)
if (isset($_POST['update_password'])) {
    $password_lama = trim($_POST['password_lama']);
    $password_baru  = trim($_POST['password_baru']);
    $password_baru2 = trim($_POST['password_baru2']);

    // Ambil password lama dari DB
    $stmt = $koneksi->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($password_db);
    $stmt->fetch();

    // 1. Cek password lama benar
    if (!password_verify($password_lama, $password_db)) {
        echo '<div class="alert alert-danger alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check"></i> Password lama salah.
              </div>';
    } else if (empty($password_baru) && empty($password_baru2)) {
      // 2. Cek password baru tidak kosong
        echo '<div class="alert alert-danger alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check"></i> Password baru tidak boleh kosong.
              </div>';
    } else if ($password_baru !== $password_baru2) {
      // 3. Cek password baru dan konfirmasi sama
        echo '<div class="alert alert-danger alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check"></i> Konfirmasi password tidak cocok.
              </div>';
    } else {

        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id_user);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo '<div class="alert alert-success alert-dismissible fade in" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-check"></i> Password berhasil diubah.
                  </div>';
        } else {
            echo '<div class="alert alert-info alert-dismissible fade in" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-info-circle"></i> Tidak ada perubahan.
                  </div>';
        }
    }

}

?>

<div class="row">
<!-- EDIT PROFILE -->
<div class="col-sm-5">
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-user"></i> Profil</h3>
    </div>

    <form method="POST" enctype="multipart/form-data" action="pages/process/update_profile.php">
      <div class="box-body">

        <!-- FOTO PROFIL -->
        <div class="text-center mb-3">
          <img src="<?php echo $path . $foto_profil; ?>" 
              alt="Foto Profil" 
              class="img-thumbnail" 
              style="max-width: 200px; height:auto;">
        </div>

        <!-- UPLOAD FOTO -->
        <div class="form-group">
          <label>Ganti Foto Profil</label>
          <input type="file" accept="image/*" name="foto" class="form-control">
          <input type="hidden" name="foto_lama" value="<?php echo $path . $foto_profil; ?>">
        </div>

        <hr>

        <div class="form-group">
          <label>NIK ( KTP ) </label>
          <input type="text" class="form-control" name="nik"
            value="<?php echo $hasil['nik']; ?>" required="required"
            <?= ($role !== 'admin') ? 'readonly' : ''; ?> />
        </div>

        <div class="form-group">
          <label>Nama </label>
          <input type="text" class="form-control" name="nama"
            value="<?php echo $hasil['nama']; ?>" required="required" />
        </div>

        <div class="form-group">
          <label>Email </label>
          <input type="email" class="form-control" name="email"
            value="<?php echo $hasil['email']; ?>" required="required" />
        </div>

        <div class="form-group">
          <label>Nomor HP </label>
          <input type="number" class="form-control" name="no_hp"
            value="<?php echo $hasil['no_hp']; ?>" required="required" />
        </div>

        <input type="hidden" name="id_user" value="<?php echo $id_user; ?>">

        <button class="btn btn-primary pull-right" name="update_profile" value="Tambah">
          <i class="fa fa-edit"></i> Ubah Profil
        </button>
      </div>
    </form>
  </div>
</div>
<!-- GANTI PASSWORD -->
<div class="col-sm-5">
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-lock"></i> Ganti Password</h3>
    </div>

    <form method="POST">
      <div class="box-body">
        <!-- <div class="form-group">
          <label>Username</label>
          <input type="text" class="form-control" 
                 value="<//?php echo htmlspecialchars($hasil['username']); ?>" 
                 <//?= ($role !== 'admin') ? 'readonly' : ''; ?>>
        </div> -->

        <div class="form-group has-feedback">
          <label>Password Lama</label>
          <input type="password" class="form-control" name="password_lama" id="password_lama" 
            placeholder="Masukkan password lama" required>
          <span class="glyphicon glyphicon-eye-open form-control-feedback toggle-password" 
            data-target="password_lama" style="cursor:pointer;"></span>
        </div>

        <div class="form-group has-feedback">
          <label>Password Baru</label>
          <input type="password" class="form-control" name="password_baru" id="password_baru"
            placeholder="Masukkan password baru" required>
          <span class="glyphicon glyphicon-eye-open form-control-feedback toggle-password"
            data-target="password_baru" style="cursor:pointer;"></span>
        </div>

        <div class="form-group has-feedback">
          <label>Konfirmasi Password Baru</label>
          <input type="password" class="form-control" name="password_baru2" id="password_baru2"
            placeholder="Ulangi password baru" required>
          <span class="glyphicon glyphicon-eye-open form-control-feedback toggle-password"
            data-target="password_baru2" style="cursor:pointer;"></span>
        </div>

      </div>

      <div class="box-footer">
        <button type="submit" name="update_password" class="btn btn-primary pull-right">
          <i class="fa fa-save"></i> Ganti Password
        </button>
      </div>
    </form>
  </div>
</div>
</div>

<script>
  window.setTimeout(function() {
    $(".alert").fadeTo(500, 0).slideUp(500, function(){
      $(this).remove(); 
    });
  }, 3000);
</script>

<style>
.form-control-feedback {
    pointer-events: auto !important;
}
</style>

<script>
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const id = this.getAttribute('data-target');
        const input = document.getElementById(id);

        if (input.type === "password") {
            input.type = "text";
            this.classList.remove("glyphicon-eye-open");
            this.classList.add("glyphicon-eye-close");
        } else {
            input.type = "password";
            this.classList.remove("glyphicon-eye-close");
            this.classList.add("glyphicon-eye-open");
        }
    });
});
</script>

