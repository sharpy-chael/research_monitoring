<?php
include("../connect.php");
session_start();

ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$upload_id = $_POST['upload_id']   ?? null;
$comment   = trim($_POST['comment'] ?? '');

if (!$upload_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $con->prepare("UPDATE uploads SET comment = :comment WHERE upload_id = :id");
    $result = $stmt->execute(['comment' => $comment, 'id' => $upload_id]);

    ob_end_clean();

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>