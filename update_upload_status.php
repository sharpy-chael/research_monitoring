<?php
include("connect.php");
include('php/log_helper.php'); // Add this
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$document_id = $_POST['document_id'] ?? null;
$upload_id = $_POST['upload_id'] ?? null; // Add this for regular uploads
$status = $_POST['status'] ?? null;

if ((!$document_id && !$upload_id) || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Handle UREC document status update
    if ($document_id) {
        // Get document info for logging
        $infoStmt = $con->prepare("SELECT document_type, original_filename FROM urec_documents WHERE id = :document_id");
        $infoStmt->execute(['document_id' => $document_id]);
        $docInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the UREC document status
        $stmt = $con->prepare("UPDATE urec_documents SET status = :status WHERE id = :document_id");
        $stmt->execute([
            'status' => $status,
            'document_id' => $document_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $actionType = ($status === 'approved') ? 'approve' : 'reject';
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                $actionType,
                $_SESSION['name'] . " {$status} UREC document: " . ($docInfo['document_type'] ?? 'Unknown') . " (" . ($docInfo['original_filename'] ?? 'Unknown file') . ")"
            );
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Document not found']);
        }
    }
    // Handle regular upload status update
    elseif ($upload_id) {
        // Get upload info for logging
        $infoStmt = $con->prepare("SELECT task_name, original_filename FROM uploads WHERE upload_id = :upload_id");
        $infoStmt->execute(['upload_id' => $upload_id]);
        $uploadInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the upload status
        $stmt = $con->prepare("UPDATE uploads SET status = :status WHERE upload_id = :upload_id");
        $stmt->execute([
            'status' => $status,
            'upload_id' => $upload_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $actionType = ($status === 'approved') ? 'approve' : 'reject';
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                $actionType,
                $_SESSION['name'] . " {$status} upload: " . ($uploadInfo['task_name'] ?? 'Unknown') . " (" . ($uploadInfo['original_filename'] ?? 'Unknown file') . ")"
            );
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload not found']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>