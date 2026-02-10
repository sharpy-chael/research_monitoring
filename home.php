<?php
session_start();
include("connect.php");
include('php/log_helper.php');
if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
    logActivity(
        $con,
        $_SESSION['id'],
        $_SESSION['role'],
        'logout',
        $_SESSION['name'] . ' logged out'
    );
}
session_unset();
session_destroy();
header('Location: portal.php');
exit;
?>