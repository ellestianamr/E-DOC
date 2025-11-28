<!DOCTYPE html>
<html>
<?php include("../../template/formhead.php"); ?>
<body class="hold-transition login-page" style="background-color: rgba(78,116,255,1);">

<div class="login-box" style="width: 400px; margin: 40px auto;"> <!-- kotak tetap di tengah halaman -->
  <div class="login-logo" style="text-align: center;">
    <!-- Ganti path/logo sesuai file kamu -->
    <img src="../../dist/img/logo-disperinaker.png" alt="Logo" style="width: 100px; margin-bottom: 10px;">
    <div style="font-size: 36px;">
      <b>E-</b>DOC
    </div>
  </div>

  <div class="login-box-body" style="border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
    <p class="login-box-msg" style="font-size: 20px; font-weight: 500;">Electronic Document</p>

    <?php
    if(isset($_GET['error']) && $_GET['error'] == "Password_salah"){
        echo "<p class='login-box-msg text-danger' style='font-size:16px;'><b>Invalid Password, please try again</b></p>";
    }

    if(isset($_GET['error']) && $_GET['error'] == "Username_tidak_terdaftar") {
        echo "<p class='login-box-msg text-danger' style='font-size:16px;'><b>Sorry, your username has not registered yet</b></p>";
    }
    ?>

    <form action="../process/login.php" method="post">
      <div class="form-group has-feedback">
        <input type="text" class="form-control" name="username" placeholder="Username">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input id="pwd" type="password" class="form-control" name="password" placeholder="Password">
        <span id="togglePwd" class="glyphicon glyphicon-eye-open form-control-feedback" style="cursor:pointer; z-index:5;"></span>
      </div>

      <!-- Row dengan tombol login di kiri -->
      <div class="row">
        <div class="col-xs-4"> 
          <!-- tombol tidak full-width dan berada di kiri -->
          <button type="submit" class="btn btn-primary btn-flat" style="width: 100%; 
               background-color: #145885;  /* warna utama */
               color: #fff;                /* warna teks */
               border: none;               /* hilangkan border bawaan */
               border-radius: 8px;         /* sudut melengkung */
               font-weight: 600;">Login</button>
        </div>
        <div class="col-xs-8">
          <!-- kolom kanan kosong (bisa diisi checkbox/forgot password nanti) -->
        </div>
      </div>

      <!-- Teks di bawah tombol, tetap di dalam kotak dan center -->
      <div style="margin-top: 18px; text-align: center;">
        <b><p style="font-size: 16px; color: #000; margin: 0;">BY : Magang PTI Unesa 2025</p></b>
      </div>
      </br>

    </form>
  </div>
</div>

<!-- jQuery 3 -->
<script src="../../bower_components/jquery/dist/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="../../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- toogle password visibility -->
<style>
.form-control-feedback {
    pointer-events: auto !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const toggle = document.getElementById('togglePwd');
  const pwd = document.getElementById('pwd');

  toggle.addEventListener('click', function() {
    if (pwd.type === "password") {
      pwd.type = "text";
      this.classList.remove('glyphicon-eye-open');
      this.classList.add('glyphicon-eye-close');
    } else {
      pwd.type = "password";
      this.classList.remove('glyphicon-eye-close');
      this.classList.add('glyphicon-eye-open');
    }
  });
});
</script>
</body>
</html>
