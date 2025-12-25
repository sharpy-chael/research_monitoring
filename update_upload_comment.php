<?php
include("connect.php");
include('php/log_helper.php'); // Add this
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$uploadId = $_POST['upload_id'] ?? null;
$documentId = $_POST['document_id'] ?? null; // Add this for UREC documents
$comment = $_POST['comment'] ?? '';

if (!$uploadId && !$documentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Handle regular upload comment
    if ($uploadId) {
        // Get upload info for logging
        $infoStmt = $con->prepare("SELECT task_name FROM uploads WHERE upload_id = :upload_id");
        $infoStmt->execute(['upload_id' => $uploadId]);
        $uploadInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the upload comment
        $stmt = $con->prepare("UPDATE uploads SET comment = :comment WHERE upload_id = :upload_id");
        $stmt->execute([
            'comment' => $comment,
            'upload_id' => $uploadId
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'comment',
                $_SESSION['name'] . " added comment to upload: " . ($uploadInfo['task_name'] ?? 'Unknown')
            );
            
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload not found']);
        }
    }
    // Handle UREC document comment
    elseif ($documentId) {
        // Get document info for logging
        $infoStmt = $con->prepare("SELECT document_type FROM urec_documents WHERE id = :document_id");
        $infoStmt->execute(['document_id' => $documentId]);
        $docInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the UREC document comment
        $stmt = $con->prepare("UPDATE urec_documents SET comment = :comment WHERE id = :document_id");
        $stmt->execute([
            'comment' => $comment,
            'document_id' => $documentId
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'comment',
                $_SESSION['name'] . " added comment to UREC document: " . ($docInfo['document_type'] ?? 'Unknown')
            );
            
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Document not found']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>