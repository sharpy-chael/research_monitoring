<?php
include("connect.php");
include('php/log_helper.php'); // Add this
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if POST data exists
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$group_id = $_POST['group_id'] ?? null;
$status = $_POST['status'] ?? null;

// Validate inputs
if (!$group_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status value
if (!in_array($status, ['approved', 'rejected', 'pending'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    // Get group info for logging
    $infoStmt = $con->prepare("SELECT name, research_title FROM groups WHERE id = :group_id");
    $infoStmt->execute(['group_id' => $group_id]);
    $groupInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update the title status for the group
    $stmt = $con->prepare("
        UPDATE groups 
        SET title_status = :status 
        WHERE id = :group_id
    ");
    
    $stmt->execute([
        'status' => $status,
        'group_id' => $group_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Log the activity
        $actionType = ($status === 'approved') ? 'approve' : 'reject';
        logActivity(
            $con,
            $_SESSION['id'],
            $_SESSION['role'],
            $actionType,
            $_SESSION['name'] . " {$status} research title for group: " . ($groupInfo['name'] ?? 'Unknown') . " - \"" . ($groupInfo['research_title'] ?? 'No title') . "\""
        );
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or group not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>