<?php
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Ambil notifikasi user aktif
$q_notif = mysqli_query($koneksi, "
  SELECT * FROM notifications
  WHERE id_user = $user_id
  ORDER BY created_at DESC
  LIMIT 5
");

#$jumlah_notif = mysqli_num_rows($q_notif);
$jumlah_belum_dibaca = mysqli_num_rows(mysqli_query(
  $koneksi,
  "SELECT id FROM notifications WHERE id_user = $user_id AND is_read = 0"
));

// ambil data user saat ini
$ambil = $koneksi->query("SELECT * FROM users WHERE id = '$user_id'");
$hasil = $ambil->fetch_assoc();

$path = "dist/img/users/";
$foto_profil = $hasil['foto_profil'];
if (empty($foto_profil) || !file_exists($path . $foto_profil)) {
    $foto_profil = "avatar3.png"; // pastikan file ini memang ada
}

?>
<header class="main-header">
    <!-- Logo -->
    <a href="index.php" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">
        <img src="dist/img/logo-disperinaker.png" alt="Logo" style="height: 30px;">
        </span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">
        <img src="dist/img/logo-disperinaker.png" alt="Logo" style="height: 46px;">
        <b>E-</b>DOC
        </span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>

      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <!-- Notifications: style can be found in dropdown.less -->
          <li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-bell-o"></i>
              <?php if ($jumlah_belum_dibaca > 0): ?>
                <span class="label label-warning"><?= $jumlah_belum_dibaca ?></span>
              <?php endif; ?>
            </a>

            <ul class="dropdown-menu">
              <li class="header">
                <!-- Kamu punya <?php #$jumlah_belum_dibaca ?> notifikasi belum dibaca -->
                 Notifications
              </li>
              <li>
                <ul class="menu">
                  <?php if (mysqli_num_rows($q_notif) == 0): ?>
                    <li><a href="#"><i class="fa fa-info-circle text-muted"></i> Tidak ada notifikasi</a></li>
                  <?php else: ?>
                    <?php while ($n = mysqli_fetch_assoc($q_notif)): ?>
                      <li>
                        <a href="#"
                          class="notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>"
                          data-id="<?= $n['id'] ?>">
                          <i class="fa fa-file-excel-o text-green"></i>
                          <?= htmlspecialchars($n['message']) ?>
                          <small class="text-muted" style="float:right;">
                            <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                          </small>
                        </a>
                      </li>
                    <?php endwhile; ?>
                  <?php endif; ?>
                </ul>
              </li>
              <!-- <li class="footer"><a href="#">Lihat semua</a></li> -->
            </ul>
          </li>
          <!-- User Account: style can be found in dropdown.less -->
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <img src="<?php echo $path . $foto_profil; ?>" class="user-image" alt="User Image">
              <span class="hidden-xs">
                <?php echo $username; ?>
                <i class="fa fa-angle-down" style="margin-left: 5px;"></i>
              </span>
            </a>
            <ul class="dropdown-menu" style="
              background: #fff;
              box-shadow: 0 2px 5px rgba(0,0,0,0.15);
              padding: 5px 10px;
              width: auto;
              min-width: unset;
              text-align: center;">
              <a href="pages/auth/logout.php" class="btn btn-default btn-flat">
                <i class="fa fa-sign-out"></i> Logout
              </a>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
</header>

<style>
  .notif-unread {
    background-color: #eaf4ff; /* biru muda */
    font-weight: bold;
    border-left: 3px solid #3c8dbc;
  }
  .notif-item:hover {
    background-color: #dceeff;
  }

</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '.notif-item', function(e) {
  e.preventDefault();
  const $item = $(this);
  const id = $item.data('id');

  if ($item.hasClass('notif-unread')) {
    $.post('pages/process/mark_read.php', { id: id }, function(res) {
      if (res === 'OK') {
        $item.removeClass('notif-unread');
        $item.css('font-weight', 'normal');

        // kurangi badge
        let $badge = $('.label.label-warning');
        let count = parseInt($badge.text()) || 0;
        if (count > 0) {
          let newCount = count - 1;
          if (newCount > 0) $badge.text(newCount);
          else $badge.remove();
        }
      }
    });

    // update jumlah di header
    let unread = $('.notif-item.unread').length;
    $('#notifDropdown .header').text(`Kamu punya ${unread} notifikasi belum dibaca`);
    if (unread > 0) {
      $('.label.label-warning').text(unread);
    } else {
      $('.label.label-warning').remove();
    }
  }
});
</script>
