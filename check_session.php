<?php
// Include this file at the top of coordinator.php, advisor.php, index.php, etc.

// Make sure to include get_setting.php if not already included
if (!function_exists('getSettingInt')) {
    include_once(__DIR__ . '/php/get_setting.php');
}

// Get session timeout from settings (in minutes)
$sessionTimeoutMinutes = getSettingInt($con, 'session_timeout', 30);
$sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;

// Check if session has expired
if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    
    if ($inactiveTime > $sessionTimeoutSeconds) {
        // Session expired
        
        // Log the timeout before destroying session
        if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
            include_once(__DIR__ . '/php/log_helper.php');
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'session_timeout',
                $_SESSION['name'] . ' session expired due to inactivity'
            );
        }
        
        session_unset();
        session_destroy();
        header('Location: portal.php?timeout=1');
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>