<?php
include("../connect.php");
include('log_helper.php');
include('document_analyzer.php');
session_start();

if (!isset($_SESSION['submit'])) {
    header("Location: ../home.php");
    exit;
}

$school_id = $_POST["school_id"];
$task_name = $_POST["task_name"];

if (!isset($_FILES["file_upload"]) || $_FILES["file_upload"]["error"] !== UPLOAD_ERR_OK) {
    $_SESSION['upload_error'] = "Upload error. Please try again.";
    header("Location: ../requirements.php");
    exit;
}

// Validate file type
$allowedExtensions = ['pdf', 'docx', 'doc'];
$originalName = basename($_FILES["file_upload"]["name"]);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    $_SESSION['upload_error'] = "Invalid file type. Please upload PDF, DOCX, or DOC files only.";
    header("Location: ../requirements.php");
    exit;
}

// Validate file size (max 10MB)
$maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
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
        // Insert upload record
        $stmt = $con->prepare('
            INSERT INTO uploads 
            ("school_id", "task_name", "file_path", "original_filename", "uploaded_at", "status")
            VALUES 
            (:school_id, :task_name, :file_path, :original_filename, NOW(), :status)
            RETURNING upload_id
        ');

        $stmt->execute([
            ':school_id' => $school_id,
            ':task_name' => $task_name,
            ':file_path' => $targetPath,
            ':original_filename' => $originalName,
            ':status' => 'pending'
        ]);
        
        $uploadId = $stmt->fetchColumn();

        // Log the upload activity
        logActivity(
            $con,
            $_SESSION['id'],
            $_SESSION['role'],
            'upload',
            $_SESSION['name'] . " uploaded " . $task_name . " (" . $originalName . ")"
        );
        
        // 🤖 AI ANALYSIS - Analyze the document
        $aiAnalysisSuccess = false;
        $aiError = null;
        
        try {
            $analyzer = new DocumentAnalyzer($con, $targetPath, $task_name);
            $aiAnalysisSuccess = $analyzer->saveAnalysis($uploadId, null);
            
            if ($aiAnalysisSuccess) {
                // Log successful AI analysis
                logActivity(
                    $con,
                    $_SESSION['id'],
                    $_SESSION['role'],
                    'ai_analysis',
                    "AI analyzed " . $task_name . " (Upload ID: " . $uploadId . ")"
                );
            }
        } catch (Exception $e) {
            // Log error but don't stop the upload process
            $aiError = $e->getMessage();
            error_log("AI Analysis failed for upload_id {$uploadId}: " . $aiError);
            
            // Optionally store error in database for debugging
            try {
                $errorStmt = $con->prepare("
                    INSERT INTO document_analysis 
                    (upload_id, analysis_type, overall_score, recommendations)
                    VALUES 
                    (:upload_id, :analysis_type, 0, :error_message)
                ");
                $errorStmt->execute([
                    ':upload_id' => $uploadId,
                    ':analysis_type' => 'chapter',
                    ':error_message' => 'AI Analysis Error: ' . $aiError
                ]);
            } catch (Exception $dbError) {
                error_log("Failed to log AI error to database: " . $dbError->getMessage());
            }
        }

        // Set success message with AI status
        if ($aiAnalysisSuccess) {
            $_SESSION['upload_success'] = "File uploaded successfully! AI analysis completed.";
        } else {
            $_SESSION['upload_success'] = "File uploaded successfully! AI analysis is processing...";
            if ($aiError) {
                $_SESSION['upload_warning'] = "Note: AI analysis encountered an issue. Your advisor can still review the file manually.";
            }
        }

        header("Location: ../requirements.php?success=1&upload_id=" . $uploadId);
        exit;

    } catch (PDOException $e) {
        // Database error - delete uploaded file
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        
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