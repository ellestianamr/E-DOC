<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="<?= empty($_GET['page']) ? 'active' : '' ?>">
          <a href="index.php">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
          </a>
        </li>
        <li class="<?= (isset($_GET['page']) && $_GET['page'] == 'rekapan_tahunan') ? 'active' : '' ?>">
          <a href="index.php?page=rekapan_tahunan">
            <i class="fa fa-calendar"></i> <span>Rekapan Tahunan</span>
          </a>
        </li>
        <?php if ($_SESSION['role_name'] == 'admin'): ?>
        <li class="<?= (isset($_GET['page']) && $_GET['page'] == 'settings_users') ? 'active' : '' ?>">
          <a href="index.php?page=settings_users">
            <i class="fa fa-users"></i> <span>Pengaturan User</span>
          </a>
        </li>
        <?php endif; ?>
        <li class="<?= (isset($_GET['page']) && $_GET['page'] == 'settings_profile') ? 'active' : '' ?>">
          <a href="index.php?page=settings_profile">
            <i class="fa fa-user"></i> <span>Pengaturan Profil</span>
          </a>
        </li>
      </ul>
    </section>
    <!-- /.sidebar -->
</aside>