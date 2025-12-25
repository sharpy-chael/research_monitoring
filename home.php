<?php
session_start();
include("connect.php");
include('php/log_helper.php');

// Log the logout BEFORE destroying the session
if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
    logActivity(
        $con,
        $_SESSION['id'],
        $_SESSION['role'],
        'logout',
        $_SESSION['name'] . ' logged out'
    );
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to portal page
header('Location: portal.php');
exit;
?>