<?php
require_once '../config/database.php';
require_once '../autoload.php';

Auth::logout();
header('Location: index.php');
exit();
?>