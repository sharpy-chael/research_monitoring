<?php
include("../connect.php");
include('log_helper.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? null;
$notificationId = $_POST['notification_id'] ?? null;

if (!$action || !$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $infoStmt = $con->prepare("SELECT title FROM system_notifications WHERE id = :id");
    $infoStmt->execute(['id' => $notificationId]);
    $notif = $infoStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'mark_read') {
        $stmt = $con->prepare("UPDATE system_notifications SET status = 'read' WHERE id = :id");
        $stmt->execute(['id' => $notificationId]);
        
        if ($stmt->rowCount() > 0) {
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'notification_read',
                $_SESSION['name'] . ' marked notification as read: ' . ($notif['title'] ?? 'Unknown')
            );
            echo json_encode(['success' => true, 'message' => 'Marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
    } elseif ($action === 'delete') {
        $stmt = $con->prepare("UPDATE system_notifications SET status = 'deleted' WHERE id = :id");
        $stmt->execute(['id' => $notificationId]);
        
        if ($stmt->rowCount() > 0) {
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'notification_deleted',
                $_SESSION['name'] . ' deleted notification: ' . ($notif['title'] ?? 'Unknown')
            );
            echo json_encode(['success' => true, 'message' => 'Notification deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    logError($con, 'notification_error', $e->getMessage(), __FILE__, __LINE__, $_SESSION['id']);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>