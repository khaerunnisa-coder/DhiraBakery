<?php
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['user_nama']);
unset($_SESSION['user_email']);
unset($_SESSION['user_wa']);
unset($_SESSION['user_role']);
header("Location: index.php");
exit();
?>