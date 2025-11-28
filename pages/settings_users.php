<?php
include("pages/process/db.php");

if ($_SESSION['role_name'] !== 'admin') {
  echo "<p style='color:red;'>Akses ditolak.</p>";
  exit;
}

$role = $_SESSION['role_name'];

// Tambah user baru
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password_plain = $_POST['password'];
    $role_id = $_POST['role_id'];

    $nik    = trim($_POST['nik']);
    $nama   = trim($_POST['nama']);
    $email  = trim($_POST['email']);
    $no_hp  = trim($_POST['no_hp']);

    if (!empty($username) && !empty($password_plain)) {

        $cek = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            echo "<script>
                window.location.href='index.php?page=settings_users&error=" . urlencode("Username sudah digunakan!") . "';
            </script>";
            exit;
        } else {
            $password = password_hash($password_plain, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("INSERT INTO users (username, password, role_id, nik, nama, email, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissss", $username, $password, $role_id, $nik, $nama, $email, $no_hp);
            $stmt->execute();
            echo "<script>
                window.location.href='index.php?page=settings_users&success=" . urlencode("User berhasil ditambahkan!") . "';
            </script>";
            exit;
        }
    } else {
        echo "<script>
            window.location.href='index.php?page=settings_users&error=" . urlencode("Username dan password wajib diisi!") . "';
        </script>";
        exit;
    }
}

// Update user
if (isset($_POST['update_user'])) {
    // $id = $_POST['edit_id'];
    // $username = trim($_POST['edit_username']);
    // $role_id = $_POST['edit_role_id'];
    // $password = $_POST['edit_password'];

    // if (!empty($password)) {
    //     $hashed = password_hash($password, PASSWORD_DEFAULT);
    //     $stmt = $koneksi->prepare("UPDATE users SET username=?, password=?, role_id=? WHERE id=?");
    //     $stmt->bind_param("ssii", $username, $hashed, $role_id, $id);
    // } else {
    //     $stmt = $koneksi->prepare("UPDATE users SET username=?, role_id=? WHERE id=?");
    //     $stmt->bind_param("sii", $username, $role_id, $id);
    // }
    // $stmt->execute();
    // echo "<script>
    //     window.location.href='index.php?page=settings_users&success=" . urlencode("User berhasil diperbarui!") . "';
    // </script>";
    // exit;

    $id         = $_POST['edit_id'];
    $username   = trim($_POST['edit_username']);
    $role_id    = $_POST['edit_role_id'];
    $nik        = trim($_POST['edit_nik']);
    $nama       = trim($_POST['edit_nama']);
    $email      = trim($_POST['edit_email']);
    $no_hp      = trim($_POST['edit_no_hp']);

    // Query update tanpa password
    $stmt = $koneksi->prepare("
        UPDATE users 
        SET username=?, role_id=?, nik=?, nama=?, email=?, no_hp=? 
        WHERE id=?
    ");

    $stmt->bind_param("sissssi", 
        $username, 
        $role_id,
        $nik, 
        $nama, 
        $email, 
        $no_hp, 
        $id
    );

    $stmt->execute();

    echo "<script>
        window.location.href='index.php?page=settings_users&success=" . urlencode("User berhasil diperbarui!") . "';
    </script>";
    exit;
}

// Hapus user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) { // biar admin ga hapus diri sendiri
        $koneksi->query("DELETE FROM users WHERE id = $id");
        echo "<script>
            window.location.href='index.php?page=settings_users&success=" . urlencode("User berhasil dihapus!") . "';
        </script>";
        exit;
    } else {
        echo "<script>
            window.location.href='index.php?page=settings_users&error=" . urlencode("Tidak bisa menghapus akun sendiri!") . "';
        </script>";
        exit;
    }
}

// Reset password user
if (isset($_GET['reset_password'])) {
    $user_id = intval($_GET['reset_password']);

    // ambil data user berdasarkan id
    $stmt = $koneksi->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $username = $row['username'];

        // hash password baru berdasarkan username
        $new_hashed_password = password_hash($username, PASSWORD_DEFAULT);

        // update password di database
        $update = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hashed_password, $user_id);

        if ($update->execute()) {
            echo "<script>
                window.location.href='index.php?page=settings_users&success=" . urlencode("Password user berhasil direset menjadi username-nya!") . "';
            </script>";
            exit;
        } else {
            echo "<script>
                window.location.href='index.php?page=settings_users&error=" . urlencode("Gagal mereset password.") . "';
            </script>";
            exit;
        }
    } else {
        echo "<script>
            window.location.href='index.php?page=settings_users&error=" . urlencode("User tidak ditemukan!") . "';
        </script>";
        exit;
    }
}

// Ambil semua role
$roles = $koneksi->query("SELECT * FROM roles");

// Ambil semua user + role
$result = $koneksi->query("
  SELECT users.id, users.username, roles.role_name, users.nik, users.nama, users.email, users.no_hp
  FROM users
  LEFT JOIN roles ON users.role_id = roles.id
");
?>


<style>
.table thead {
  background-color: #145885;
  color: white; /* biar teksnya kontras */
}

.table-bordered {
  border: 2px solid #145885 !important; /* border luar */
}

.table-bordered td,
.table-bordered th {
  border: 1px solid #145885 !important; /* border dalam */
}

.table tbody tr:hover {
  background-color: #e9f3fa; /* biru muda lembut */
}
</style>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
  <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
  <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<p>
    <button type="button" class="btn btn-primary btn-md mr-2" data-toggle="modal" data-target="#tambahUserModal">
        <i class="fa fa-plus"></i> Tambah User
    </button>
</p>

<div class="box">
    <div class="box-body table-responsive no-padding">
<table class="table table-bordered users-table">
  <thead>
    <tr>
      <th style="text-align:center;">No</th>
      <th>Username</th>
      <th>Nama</th>
      <th>Hak Akses</th>
      <th style="text-align:center;">Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $no=1; 
    while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td style="text-align:center;"><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= htmlspecialchars($row['nama']) ?></td>
        <td><?= strtoupper($row['role_name']) ?></td>
        <td style="text-align:center;">
          <?php if($role == 'admin' && $row['id'] != $_SESSION['user_id']): ?>
          <a href="#" 
            class="btn btn-warning btn-sm editUserBtn"
            data-id="<?= $row['id'] ?>"
            data-username="<?= htmlspecialchars($row['username']) ?>"
            data-role="<?= $row['role_name'] ?>"

            data-nik="<?= htmlspecialchars($row['nik']) ?>"
            data-nama="<?= htmlspecialchars($row['nama']) ?>"
            data-email="<?= htmlspecialchars($row['email']) ?>"
            data-no_hp="<?= htmlspecialchars($row['no_hp']) ?>"
            >
            Edit
          </a> 
          &nbsp;
          <a href="index.php?page=settings_users&delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus user ini?')">Hapus</a>
          &nbsp;
          <a href="index.php?page=settings_users&reset_password=<?= $row['id'] ?>" class="btn btn-info btn-sm" onclick="return confirm('Yakin reset password user ini?')">Reset Password</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>
</div>

<!-- === MODAL TAMBAH USER === -->
<div class="modal fade" id="tambahUserModal" tabindex="-1" role="dialog" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h4 class="modal-title" id="tambahUserModalLabel">Tambah User Baru</h4>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <div class="form-group">
            <label>NIK:</label>
            <input type="text" name="nik" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Nama:</label>
            <input type="text" name="nama" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Nomor HP:</label>
            <input type="number" name="no_hp" class="form-control" required>
          </div>

          <hr>

          <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" id="username" class="form-control" required>
            <small id="username-status"></small>
          </div>

          <div class="form-group has-feedback">
            <label>Password:</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <span class="glyphicon glyphicon-eye-open form-control-feedback toggle-password" 
              data-target="password" style="cursor:pointer;"></span>
          </div>

          <div class="form-group">
            <label>Role:</label>
            <select name="role_id" class="form-control" required>
              <option value="">-- Pilih Role --</option>
              <?php while ($r = $roles->fetch_assoc()): ?>
                <option value="<?= $r['id'] ?>"><?= strtoupper($r['role_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" name="add_user" class="btn btn-primary">Tambah User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- === MODAL EDIT USER === -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header" style="background:#145885; color:white;">
          <h4 class="modal-title" id="editUserModalLabel">Edit User</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">

          <div class="form-group">
            <label>NIK:</label>
            <input type="text" name="edit_nik" id="edit_nik" value="" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Nama:</label>
            <input type="text" name="edit_nama" id="edit_nama" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Email:</label>
            <input type="email" name="edit_email" id="edit_email" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Nomor HP:</label>
            <input type="number" name="edit_no_hp" id="edit_no_hp" class="form-control" required>
          </div>

          <hr>

          <div class="form-group">
            <label>Username:</label>
            <input type="text" name="edit_username" id="edit_username" class="form-control" required>
          </div>

          <!-- <div class="form-group">
            <label>Password (isi jika ingin ubah):</label>
            <input type="password" name="edit_password" id="edit_password" class="form-control">
          </div> -->

          <div class="form-group">
            <label>Role:</label>
            <select name="edit_role_id" id="edit_role_id" class="form-control" required>
              <option value="">-- Pilih Role --</option>
              <?php
              $roles2 = $koneksi->query("SELECT * FROM roles");
              while ($r = $roles2->fetch_assoc()):
              ?>
                <option value="<?= $r['id'] ?>"><?= strtoupper($r['role_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" name="update_user" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- === AJAX CEK USERNAME === -->
<script>
$('#username').on('keyup change', function() {
    var username = $(this).val();
    if (username.length > 3) {
        $.ajax({
            url: 'pages/process/check_username.php',
            type: 'POST',
            data: { username: username },
            success: function(response) {
                if (response === 'taken') {
                    $('#username-status')
                        .text('❌ Username sudah digunakan')
                        .css('color', 'red');
                } else {
                    $('#username-status')
                        .text('✅ Username tersedia')
                        .css('color', 'green');
                }
            }
        });
    } else {
        $('#username-status').text('');
    }
});
</script>

<script>
$(document).on('click', '.editUserBtn', function() {
    const id = $(this).data('id');
    const username = $(this).data('username');
    const roleName = $(this).data('role');

    // Isi form di modal
    $('#edit_id').val(id);
    $('#edit_username').val(username);

    const nik = $(this).data('nik');
    const nama = $(this).data('nama');
    const email = $(this).data('email');
    const no_hp = $(this).data('no_hp');

    $('#edit_nik').val(nik);
    $('#edit_nama').val(nama);
    $('#edit_email').val(email);
    $('#edit_no_hp').val(no_hp);

    // Set role berdasarkan nama role
    $('#edit_role_id option').each(function() {
        if ($(this).text().toUpperCase() === roleName.toUpperCase()) {
            $(this).prop('selected', true);
        }
    });

    $('#editUserModal').modal('show');
});
</script>

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