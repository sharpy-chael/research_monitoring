<?php
if (!function_exists('getSettingInt')) {
    include_once(__DIR__ . '/php/get_setting.php');
}

$sessionTimeoutMinutes = getSettingInt($con, 'session_timeout', 30);
$sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;

if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    
    if ($inactiveTime > $sessionTimeoutSeconds) {

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

$_SESSION['last_activity'] = time();
?>