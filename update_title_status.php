<?php
include("connect.php");
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
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or group not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>