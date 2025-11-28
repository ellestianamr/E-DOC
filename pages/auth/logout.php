<?php
session_start();
	
$_SESSION['user_id'] = null;
$_SESSION['username'] = null;
$_SESSION['role_id'] = null;
$_SESSION['role_name'] = null;

header('Location: login.php');
?>