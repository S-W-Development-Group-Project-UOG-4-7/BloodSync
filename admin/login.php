<?php
// login.php - Simple test file
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
header("Location: admin.php");
exit();
?>