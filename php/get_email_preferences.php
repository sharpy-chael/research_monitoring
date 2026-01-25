<?php
session_start();

// Fix: Use correct paths
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/get_setting.php';

header('Content-Type: application/json');

// Prevent any output before JSON
ob_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['id'];
$userRole = $_SESSION['role'];

try {
    $systemEnabled = getSettingBool($con, 'enable_email_notifications', false);
    
    // Determine table based on role
    $tableMap = [
        'student' => 'student',
        'advisor' => 'advisor',
        'coordinator' => 'coordinator'
    ];
    
    $table = $tableMap[$userRole] ?? 'student';
    
    // Check if columns exist, if not return defaults
    try {
        $checkStmt = $con->query("SHOW COLUMNS FROM {$table} LIKE 'email_notifications_enabled'");
        if ($checkStmt->rowCount() === 0) {
            // Column doesn't exist, return defaults
            throw new Exception('Columns not yet created');
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'system_enabled' => $systemEnabled,
            'enabled' => true,
            'preferences' => getDefaultPreferences($userRole)
        ]);
        exit;
    }
    
    $stmt = $con->prepare("
        SELECT email_notifications_enabled, email_preferences 
        FROM {$table} 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $enabled = $user['email_notifications_enabled'] ?? true;
    $preferences = $user['email_preferences'] ? json_decode($user['email_preferences'], true) : [];
    
    if (empty($preferences)) {
        $preferences = getDefaultPreferences($userRole);
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'system_enabled' => $systemEnabled,
        'enabled' => $enabled,
        'preferences' => $preferences
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getDefaultPreferences($role) {
    $defaults = [
        'student' => [
            'upload_status' => true,
            'title_status' => true,
            'advisor_comments' => true
        ],
        'advisor' => [
            'new_uploads' => true,
            'title_proposals' => true,
            'urec_uploads' => true
        ],
        'coordinator' => [
            'system_alerts' => true,
            'backup_notifications' => true
        ]
    ];
    
    return $defaults[$role] ?? [];
}
?>