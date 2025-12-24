<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || !in_array($_SESSION['role'], ['admin', 'coordinator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

// List backups
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("
            SELECT id, backup_name, file_path, file_size, backup_type, created_at, status, notes
            FROM database_backups
            ORDER BY created_at DESC
        ");
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'backups' => $backups]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Create backup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $backupName = $_POST['backupName'] ?? 'backup_' . date('Y-m-d_H-i-s');
        $notes = $_POST['backupNotes'] ?? '';
        
        // Create backups directory if it doesn't exist
        $backupDir = '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Get database credentials from connect.php
        // Assuming you have these defined in connect.php
        $dbHost = 'localhost'; // Update with your actual host
        $dbName = 'research_monitoring'; // Update with your actual database name
        $dbUser = 'postgres'; // Update with your actual username
        $dbPass = 'pangitsiyulip'; // Update with your actual password
        
        $fileName = $backupName . '.sql';
        $filePath = $backupDir . $fileName;
        
        // PostgreSQL backup command
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -U %s -d %s > %s 2>&1',
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($filePath)) {
            $fileSize = filesize($filePath);
            
            // Save backup record to database
            $stmt = $con->prepare("
                INSERT INTO database_backups (backup_name, file_path, file_size, backup_type, created_by, status, notes)
                VALUES (:backup_name, :file_path, :file_size, 'manual', :created_by, 'completed', :notes)
            ");
            $stmt->execute([
                'backup_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'created_by' => $user_id,
                'notes' => $notes
            ]);
            
            // Log the action
            $logStmt = $con->prepare("
                INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
                VALUES (:user_id, :user_type, 'backup', :description, :ip_address)
            ");
            $logStmt->execute([
                'user_id' => $user_id,
                'user_type' => $_SESSION['role'],
                'description' => 'Created database backup: ' . $fileName,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully'
            ]);
        } else {
            // Save failed backup record
            $stmt = $con->prepare("
                INSERT INTO database_backups (backup_name, file_path, backup_type, created_by, status, notes)
                VALUES (:backup_name, :file_path, 'manual', :created_by, 'failed', :notes)
            ");
            $stmt->execute([
                'backup_name' => $fileName,
                'file_path' => $filePath,
                'created_by' => $user_id,
                'notes' => 'Backup failed: ' . implode("\n", $output)
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => 'Backup failed. Please check server configuration.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Delete backup
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $backupId = $data['backup_id'] ?? null;
        
        if (!$backupId) {
            echo json_encode(['success' => false, 'message' => 'Invalid backup ID']);
            exit;
        }
        
        // Get backup file path
        $stmt = $con->prepare("SELECT file_path, backup_name FROM database_backups WHERE id = :id");
        $stmt->execute(['id' => $backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backup) {
            echo json_encode(['success' => false, 'message' => 'Backup not found']);
            exit;
        }
        
        // Delete file if exists
        if (file_exists($backup['file_path'])) {
            unlink($backup['file_path']);
        }
        
        // Delete database record
        $deleteStmt = $con->prepare("DELETE FROM database_backups WHERE id = :id");
        $deleteStmt->execute(['id' => $backupId]);
        
        // Log the action
        $logStmt = $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
            VALUES (:user_id, :user_type, 'delete', :description, :ip_address)
        ");
        $logStmt->execute([
            'user_id' => $user_id,
            'user_type' => $_SESSION['role'],
            'description' => 'Deleted backup: ' . $backup['backup_name'],
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>