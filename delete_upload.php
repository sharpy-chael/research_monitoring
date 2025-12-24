<?php
session_start();
include 'connect.php'; // your PDO connection

// Check login
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$school_id = $_SESSION['school_id'];
$upload_id = $_POST['upload_id'] ?? null;

if (!$upload_id) {
    echo json_encode(['status' => 'error', 'message' => 'No upload ID provided']);
    exit;
}

// Fetch file info from uploads table
$stmt = $con->prepare("SELECT file_path FROM uploads WHERE upload_id = :upload_id AND school_id = :school_id");
$stmt->execute([
    'upload_id' => $upload_id,
    'school_id' => $school_id
]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

$file_path = $file['file_path'];

// Delete from database
$stmt = $con->prepare("DELETE FROM uploads WHERE upload_id = :upload_id AND school_id = :school_id");
$stmt->execute([
    'upload_id' => $upload_id,
    'school_id' => $school_id
]);

// Delete the file itself
if (file_exists($file_path)) {
    unlink($file_path);
}

echo json_encode(['status' => 'success']);
?>

