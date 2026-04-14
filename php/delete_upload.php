<?php
session_start();
include '../connect.php';

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$upload_id = $_POST['upload_id'] ?? null;

if (!$upload_id) {
    echo json_encode(['status' => 'error', 'message' => 'No upload ID provided']);
    exit;
}

$studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
$studentStmt->execute(['school_id' => $_SESSION['school_id']]);
$studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$studentRow) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    exit;
}

$sgStmt = $con->prepare("SELECT group_id FROM student_groups WHERE student_id = :student_id ORDER BY id ASC LIMIT 1");
$sgStmt->execute(['student_id' => $studentRow['id']]);
$sgRow = $sgStmt->fetch(PDO::FETCH_ASSOC);

if (!$sgRow || !$sgRow['group_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Student group not found']);
    exit;
}

$group_id = $sgRow['group_id'];

$stmt = $con->prepare("SELECT file_path FROM uploads WHERE upload_id = :upload_id AND group_id = :group_id");
$stmt->execute(['upload_id' => $upload_id, 'group_id' => $group_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

$stmt = $con->prepare("DELETE FROM uploads WHERE upload_id = :upload_id AND group_id = :group_id");
$stmt->execute(['upload_id' => $upload_id, 'group_id' => $group_id]);

if (file_exists($file['file_path'])) unlink($file['file_path']);

echo json_encode(['status' => 'success']);
?>