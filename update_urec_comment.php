<?php
include("connect.php");
session_start();

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$document_id = $_POST['document_id'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$document_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $con->prepare("UPDATE urec_documents SET comment = :comment WHERE id = :document_id");
    $result = $stmt->execute([
        'comment' => $comment,
        'document_id' => $document_id
    ]);
    
    ob_end_clean();
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>