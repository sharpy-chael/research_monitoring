<?php
session_start();

// Fix: Use correct path relative to where this file is located
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

// Prevent any output before JSON
ob_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['id'];
$userRole = $_SESSION['role'];
$enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
$preferences = $_POST['preferences'] ?? '{}';

try {
    // Determine table based on role
    $tableMap = [
        'student' => 'student',
        'advisor' => 'advisor',
        'coordinator' => 'coordinator'
    ];
    
    $table = $tableMap[$userRole] ?? 'student';
    
    // Check if columns exist, if not, add them
    try {
        $checkStmt = $con->query("SHOW COLUMNS FROM {$table} LIKE 'email_notifications_enabled'");
        if ($checkStmt->rowCount() === 0) {
            $con->exec("ALTER TABLE {$table} ADD COLUMN email_notifications_enabled BOOLEAN DEFAULT TRUE");
        }
        
        $checkStmt = $con->query("SHOW COLUMNS FROM {$table} LIKE 'email_preferences'");
        if ($checkStmt->rowCount() === 0) {
            $con->exec("ALTER TABLE {$table} ADD COLUMN email_preferences TEXT");
        }
    } catch (PDOException $e) {
        // Columns might already exist, continue
    }
    
    $stmt = $con->prepare("
        UPDATE {$table} 
        SET email_notifications_enabled = ?, 
            email_preferences = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$enabled, $preferences, $userId]);
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Preferences saved successfully'
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>