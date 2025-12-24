<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || !in_array($_SESSION['role'], ['admin', 'coordinator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

// List notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("
            SELECT id, notification_type, recipient_type, recipient_id, title, message, priority, status, sent_at, created_at
            FROM system_notifications
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'notifications' => $notifications]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Send notification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $recipientType = $_POST['recipientType'] ?? '';
        $recipientId = $_POST['recipientId'] ?? null;
        $title = $_POST['notifTitle'] ?? '';
        $message = $_POST['notifMessage'] ?? '';
        $priority = $_POST['notifPriority'] ?? 'normal';
        
        if (!$recipientType || !$title || !$message) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Insert notification
        $stmt = $con->prepare("
            INSERT INTO system_notifications 
            (notification_type, recipient_type, recipient_id, title, message, priority, status, created_by, sent_at)
            VALUES ('system', :recipient_type, :recipient_id, :title, :message, :priority, 'sent', :created_by, NOW())
        ");
        
        $stmt->execute([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'created_by' => $user_id
        ]);
        
        // Log the action
        $logStmt = $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
            VALUES (:user_id, :user_type, 'notification', :description, :ip_address)
        ");
        $logStmt->execute([
            'user_id' => $user_id,
            'user_type' => $_SESSION['role'],
            'description' => "Sent notification to {$recipientType}: {$title}",
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        // Here you could add logic to actually send emails or push notifications
        // For now, we're just storing in the database
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>