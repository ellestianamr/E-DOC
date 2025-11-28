<?php
session_start();
include("pages/process/db.php");

if(!isset($_SESSION['user_id'])){
  header('Location: pages/auth/login.php');
  exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : "";

?>

<!DOCTYPE html>
<html>

<?php include("template/head.php"); ?>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include("template/header.php"); ?>

  <!-- Left side column. contains the logo and sidebar -->
  <?php include("template/sidebar.php"); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <?php include("template/content_header.php"); ?>
    </section>

    <!-- Main content -->
    <section class="content">

      <?php if ($page == "") {
          include("home.php");
        } else {
          include("pages/" . $page . ".php");
        } 
      ?>

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <?php include("template/footer.php"); ?>

</div>
<!-- ./wrapper -->

<?php include("script/script.php"); ?>
</body>
</html>
