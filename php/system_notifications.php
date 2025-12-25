<?php
include("../connect.php");
include('log_helper.php'); // Add logging
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
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
        
        // Convert empty string to NULL for integer field
        if (empty($recipientId) || $recipientId === '') {
            $recipientId = null;
        } else {
            $recipientId = (int)$recipientId; // Ensure it's an integer
        }
        
        // Validate recipient type
        if (!in_array($recipientType, ['all', 'students', 'advisors', 'coordinators', 'specific'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid recipient type']);
            exit;
        }
        
        // If specific user, recipient_id is required
        if ($recipientType === 'specific' && $recipientId === null) {
            echo json_encode(['success' => false, 'message' => 'User ID is required for specific user notifications']);
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
            'recipient_id' => $recipientId, // Now properly NULL or integer
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'created_by' => $user_id
        ]);
        
        // Log the action
        logActivity(
            $con,
            $user_id,
            $_SESSION['role'],
            'notification',
            $_SESSION['name'] . " sent notification to {$recipientType}: {$title}"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    } catch (PDOException $e) {
        // Log the error
        logError(
            $con,
            'notification_error',
            $e->getMessage(),
            __FILE__,
            __LINE__,
            $user_id
        );
        
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>