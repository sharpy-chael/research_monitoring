<?php
include("connect.php");
include('php/log_helper.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'] ?? null;

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

try {
    // Get notification info before updating
    $infoStmt = $con->prepare("SELECT title FROM system_notifications WHERE id = :id");
    $infoStmt->execute(['id' => $notificationId]);
    $notif = $infoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update notification status
    $stmt = $con->prepare("UPDATE system_notifications SET status = 'read' WHERE id = :id");
    $stmt->execute(['id' => $notificationId]);
    
    if ($stmt->rowCount() > 0) {
        // Log the activity
        logActivity(
            $con,
            $_SESSION['id'],
            $_SESSION['role'],
            'notification_read',
            $_SESSION['name'] . ' marked notification as read: ' . ($notif['title'] ?? 'Unknown')
        );
        
        echo json_encode(['success' => true, 'message' => 'Marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    }
} catch (PDOException $e) {
    logError($con, 'notification_error', $e->getMessage(), __FILE__, __LINE__, $_SESSION['id']);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
