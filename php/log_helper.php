<?php
function logActivity($con, $userId, $userType, $actionType, $description = '') {
    try {
        $stmt = $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address, user_agent)
            VALUES (:user_id, :user_type, :action_type, :description, :ip_address, :user_agent)
        ");
        $stmt->execute([
            'user_id'     => $userId,
            'user_type'   => $userType,
            'action_type' => $actionType,
            'description' => $description,
            'ip_address'  => $_SERVER['REMOTE_ADDR']     ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        return true;
    } catch (PDOException $e) {
        logError($con, 'log_activity_error', $e->getMessage(), __FILE__, __LINE__, $userId);
        return false;
    }
}

function logError($con, $errorType, $errorMessage, $errorFile = '', $errorLine = 0, $userId = null) {
    try {
        $stmt = $con->prepare("
            INSERT INTO error_logs (error_type, error_message, error_file, error_line, user_id, ip_address, stack_trace)
            VALUES (:error_type, :error_message, :error_file, :error_line, :user_id, :ip_address, :stack_trace)
        ");
        $stmt->execute([
            'error_type'    => $errorType,
            'error_message' => $errorMessage,
            'error_file'    => $errorFile,
            'error_line'    => $errorLine,
            'user_id'       => $userId,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'stack_trace'   => json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log error to database: " . $e->getMessage());
        return false;
    }
}
?>