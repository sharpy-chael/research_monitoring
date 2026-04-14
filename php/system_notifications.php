<?php
session_start();
include("../connect.php");
include('log_helper.php');
include('get_setting.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("
            SELECT id, notification_type, recipient_type, recipient_id, title, message, priority, status, sent_at, created_at
            FROM system_notifications
            ORDER BY created_at DESC
            LIMIT 100
        ");
        echo json_encode(['success' => true, 'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!getSettingBool($con, 'enable_notifications', true)) {
            echo json_encode(['success' => false, 'message' => 'Notifications are currently disabled in system settings']);
            exit;
        }

        $recipientType = $_POST['recipientType'] ?? '';
        $recipientId   = !empty($_POST['recipientId']) ? (int)$_POST['recipientId'] : null;
        $title         = $_POST['notifTitle']    ?? '';
        $message       = $_POST['notifMessage']  ?? '';
        $priority      = $_POST['notifPriority'] ?? 'normal';

        if (!$recipientType || !$title || !$message) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if (!in_array($recipientType, ['all', 'students', 'advisors', 'coordinators', 'specific'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid recipient type']);
            exit;
        }

        if ($recipientType === 'specific' && $recipientId === null) {
            echo json_encode(['success' => false, 'message' => 'User ID is required for specific user notifications']);
            exit;
        }

        $stmt = $con->prepare("
            INSERT INTO system_notifications
            (notification_type, recipient_type, recipient_id, title, message, priority, status, created_by, sent_at)
            VALUES ('system', :recipient_type, :recipient_id, :title, :message, :priority, 'sent', :created_by, NOW())
        ");
        $stmt->execute([
            'recipient_type' => $recipientType,
            'recipient_id'   => $recipientId,
            'title'          => $title,
            'message'        => $message,
            'priority'       => $priority,
            'created_by'     => $user_id
        ]);

        logActivity($con, $user_id, $_SESSION['role'], 'notification', $_SESSION['name'] . " sent notification to {$recipientType}: {$title}");
        echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
    } catch (PDOException $e) {
        logError($con, 'notification_error', $e->getMessage(), __FILE__, __LINE__, $user_id);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>