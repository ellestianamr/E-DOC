<?php
session_start();
include("db.php");

// Ambil data dari form login
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = mysqli_real_escape_string($koneksi, $_POST['password']);

// Cek apakah user terdaftar
$sql = "SELECT users.*, roles.role_name 
        FROM users 
        LEFT JOIN roles ON users.role_id = roles.id 
        WHERE username = '$username'";
$result = mysqli_query($koneksi, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    // Verifikasi password
    if (password_verify($password, $row['password'])) {
        // Simpan session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role_id'] = $row['role_id'];
        $_SESSION['role_name'] = $row['role_name'];

        header("Location: ../../index.php");
        exit();
    } else {
        header("Location: ../auth/login.php?error=Password_salah");
        exit();
    }
} else {
    header("Location: ../auth/login.php?error=Username_tidak_terdaftar");
    exit();
}
?>
