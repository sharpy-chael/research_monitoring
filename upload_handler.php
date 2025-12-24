<?php
include("connect.php");
session_start();

if (!isset($_SESSION['submit'])) {
    header("Location: home.php");
    exit;
}

$school_id = $_POST["school_id"];
$task_name = $_POST["task_name"];

if (!isset($_FILES["file_upload"]) || $_FILES["file_upload"]["error"] !== UPLOAD_ERR_OK) {
    die("Upload error.");
}

$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$originalName = basename($_FILES["file_upload"]["name"]);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$storedName = uniqid("FILE_", true) . "." . $ext;
$targetPath = $uploadDir . $storedName;

if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $targetPath)) {

    $stmt = $con->prepare('
        INSERT INTO uploads 
        ("school_id", "task_name", "file_path", "original_filename", "uploaded_at")
        VALUES 
        (:school_id, :task_name, :file_path, :original_filename, NOW())
    ');

    $stmt->execute([
        ':school_id' => $school_id,
        ':task_name' => $task_name,
        ':file_path' => $targetPath,
        ':original_filename' => $originalName
    ]);

    header("Location: requirements.php?success=1");
    exit;

} else {
    echo "Failed to upload file.";
}
