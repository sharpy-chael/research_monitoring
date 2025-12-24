<?php
include("connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$document_id = $_POST['document_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$document_id || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Update the UREC document status
    $stmt = $con->prepare("UPDATE urec_documents SET status = :status WHERE id = :document_id");
    $stmt->execute([
        'status' => $status,
        'document_id' => $document_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>