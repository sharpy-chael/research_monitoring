<?php
/**
 * System Activity Logger
 * Include this file wherever you want to log activities
 * Usage: logActivity($con, $userId, $userType, $actionType, $description);
 */

/**
 * Log system activity
 * 
 * @param PDO $con Database connection
 * @param int $userId User ID performing the action
 * @param string $userType Type of user (student, advisor, admin, coordinator)
 * @param string $actionType Type of action (login, logout, upload, approve, reject, delete, etc.)
 * @param string $description Detailed description of the action
 * @return bool Success status
 */
function logActivity($con, $userId, $userType, $actionType, $description = '') {
    try {
        $stmt = $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address, user_agent)
            VALUES (:user_id, :user_type, :action_type, :description, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'user_type' => $userType,
            'action_type' => $actionType,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log error to error_logs table
        logError($con, 'log_activity_error', $e->getMessage(), __FILE__, __LINE__, $userId);
        return false;
    }
}

/**
 * Log system error
 * 
 * @param PDO $con Database connection
 * @param string $errorType Type of error
 * @param string $errorMessage Error message
 * @param string $errorFile File where error occurred
 * @param int $errorLine Line number where error occurred
 * @param int $userId User ID (optional)
 * @return bool Success status
 */
function logError($con, $errorType, $errorMessage, $errorFile = '', $errorLine = 0, $userId = null) {
    try {
        $stmt = $con->prepare("
            INSERT INTO error_logs (error_type, error_message, error_file, error_line, user_id, ip_address, stack_trace)
            VALUES (:error_type, :error_message, :error_file, :error_line, :user_id, :ip_address, :stack_trace)
        ");
        
        $stmt->execute([
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'error_file' => $errorFile,
            'error_line' => $errorLine,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ]);
        
        return true;
    } catch (PDOException $e) {
        // If we can't log the error, at least write it to PHP error log
        error_log("Failed to log error to database: " . $e->getMessage());
        return false;
    }
}

/**
 * Example usage in your existing files:
 * 
 * // In login.php after successful login:
 * logActivity($con, $userId, $userType, 'login', 'User logged in successfully');
 * 
 * // In logout.php:
 * logActivity($con, $userId, $userType, 'logout', 'User logged out');
 * 
 * // In update_upload_status.php after approval:
 * logActivity($con, $_SESSION['id'], $_SESSION['role'], 'approve', "Approved upload: {$uploadId}");
 * 
 * // In update_urec_status.php after rejection:
 * logActivity($con, $_SESSION['id'], $_SESSION['role'], 'reject', "Rejected UREC document: {$documentId}");
 * 
 * // When catching errors:
 * catch (PDOException $e) {
 *     logError($con, 'database_error', $e->getMessage(), __FILE__, __LINE__, $userId);
 * }
 */
?>