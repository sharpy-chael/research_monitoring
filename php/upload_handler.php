<?php
include("../connect.php");
include('log_helper.php');
session_start();

if (!isset($_SESSION['submit'])) {
    header("Location: ../home.php");
    exit;
}

$group_id  = $_POST["group_id"];
$task_name = $_POST["task_name"];

if (!isset($_FILES["file_upload"]) || $_FILES["file_upload"]["error"] !== UPLOAD_ERR_OK) {
    $_SESSION['upload_error'] = "Upload error. Please try again.";
    header("Location: ../requirements.php");
    exit;
}

$allowedExtensions = ['pdf', 'docx', 'doc'];
$originalName = basename($_FILES["file_upload"]["name"]);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    $_SESSION['upload_error'] = "Invalid file type. Please upload PDF, DOCX, or DOC files only.";
    header("Location: ../requirements.php");
    exit;
}

$maxFileSize = 10 * 1024 * 1024;
if ($_FILES["file_upload"]["size"] > $maxFileSize) {
    $_SESSION['upload_error'] = "File too large. Maximum size is 10MB.";
    header("Location: ../requirements.php");
    exit;
}

$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$storedName = uniqid("FILE_", true) . "." . $ext;
$targetPath = $uploadDir . $storedName;

if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $targetPath)) {
    try {
        $stmt = $con->prepare("
            INSERT INTO uploads (group_id, task_name, file_path, original_filename, uploaded_at, status)
            VALUES (:group_id, :task_name, :file_path, :original_filename, NOW(), 'pending')
            RETURNING upload_id
        ");

        $stmt->execute([
            ':group_id'         => $group_id,
            ':task_name'        => $task_name,
            ':file_path'        => $targetPath,
            ':original_filename' => $originalName,
        ]);

        $uploadId = $stmt->fetchColumn();

        logActivity(
            $con,
            $_SESSION['id'],
            $_SESSION['role'],
            'upload',
            $_SESSION['name'] . " uploaded " . $task_name . " (" . $originalName . ")"
        );

        $_SESSION['upload_success'] = "File uploaded successfully!";
        header("Location: ../requirements.php?success=1&upload_id=" . $uploadId);
        exit;

    } catch (PDOException $e) {
        if (file_exists($targetPath)) unlink($targetPath);
        error_log("Database error in upload_handler.php: " . $e->getMessage());
        $_SESSION['upload_error'] = "Database error. Please try again.";
        header("Location: ../requirements.php");
        exit;
    }
} else {
    $_SESSION['upload_error'] = "Failed to upload file. Please check file permissions.";
    header("Location: ../requirements.php");
    exit;
}
?>