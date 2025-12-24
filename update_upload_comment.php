<?php
include("connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$uploadId = $_POST['upload_id'] ?? null;
$comment = $_POST['comment'] ?? '';

if (!$uploadId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Update the upload comment
    $stmt = $con->prepare("UPDATE uploads SET comment = :comment WHERE upload_id = :upload_id");
    $stmt->execute([
        'comment' => $comment,
        'upload_id' => $uploadId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>