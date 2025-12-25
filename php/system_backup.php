<?php
include("../connect.php");
include('log_helper.php'); // Add logging
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
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
            if (!mkdir($backupDir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create backups directory. Check permissions.']);
                exit;
            }
        }
        
        // Check if directory is writable
        if (!is_writable($backupDir)) {
            echo json_encode(['success' => false, 'message' => 'Backups directory is not writable. Check folder permissions.']);
            exit;
        }
        
        // Use credentials from connect.php
        $dbHost = 'localhost';
        $dbPort = '5432';
        $dbName = 'research_monitoring';
        $dbUser = 'postgres';
        $dbPass = 'pangitsiyulip';
        
        $fileName = $backupName . '.sql';
        $filePath = $backupDir . $fileName;
        
        // Detect operating system
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Find pg_dump
        $pgDumpPath = null;
        
        if ($isWindows) {
            // Windows paths
            $possiblePaths = [
                'C:\Program Files\PostgreSQL\16\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\15\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\14\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\13\bin\pg_dump.exe',
                'C:\Program Files (x86)\PostgreSQL\16\bin\pg_dump.exe',
                'C:\Program Files (x86)\PostgreSQL\15\bin\pg_dump.exe',
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $pgDumpPath = $path;
                    break;
                }
            }
            
            if (!$pgDumpPath) {
                echo json_encode([
                    'success' => false,
                    'message' => 'pg_dump.exe not found. Please ensure PostgreSQL is installed. Checked paths: ' . implode(', ', $possiblePaths)
                ]);
                exit;
            }
        } else {
            // Linux/Unix paths
            $pgDumpPath = trim(shell_exec('which pg_dump 2>/dev/null'));
            
            if (empty($pgDumpPath)) {
                $possiblePaths = [
                    '/usr/bin/pg_dump',
                    '/usr/local/bin/pg_dump',
                    '/usr/pgsql-16/bin/pg_dump',
                    '/usr/pgsql-15/bin/pg_dump',
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $pgDumpPath = $path;
                        break;
                    }
                }
            }
            
            if (empty($pgDumpPath)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'pg_dump command not found. Please install PostgreSQL client tools: sudo apt-get install postgresql-client'
                ]);
                exit;
            }
        }
        
        // Set PGPASSWORD environment variable (works on both Windows and Linux)
        putenv("PGPASSWORD={$dbPass}");
        
        // Build command based on OS
        if ($isWindows) {
            $command = sprintf(
                '"%s" -h %s -p %s -U %s -d %s -f "%s" 2>&1',
                $pgDumpPath,
                $dbHost,
                $dbPort,
                $dbUser,
                $dbName,
                $filePath
            );
        } else {
            $command = sprintf(
                '%s -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellarg($pgDumpPath),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbName),
                escapeshellarg($filePath)
            );
        }
        
        // Execute command
        exec($command, $output, $returnVar);
        
        // Clear password from environment
        putenv("PGPASSWORD");
        
        // Debug info (remove in production)
        $debugInfo = [
            'command' => preg_replace('/PGPASSWORD=\S+/', 'PGPASSWORD=***', $command),
            'return_code' => $returnVar,
            'output' => $output,
            'file_exists' => file_exists($filePath),
            'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            'pg_dump_path' => $pgDumpPath,
        ];
        
        // Check if backup was successful
        if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
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
            logActivity(
                $con,
                $user_id,
                $_SESSION['role'],
                'backup',
                $_SESSION['name'] . ' created database backup: ' . $fileName . ' (' . round($fileSize / 1024 / 1024, 2) . ' MB)'
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully (' . round($fileSize / 1024 / 1024, 2) . ' MB)'
            ]);
        } else {
            // Backup failed - detailed error
            $errorMessage = 'Backup failed. ';
            
            if (!file_exists($filePath)) {
                $errorMessage .= 'File was not created. ';
            } elseif (filesize($filePath) === 0) {
                $errorMessage .= 'File is empty. ';
                @unlink($filePath); // Delete empty file
            }
            
            if ($returnVar !== 0) {
                $errorMessage .= 'Return code: ' . $returnVar . '. ';
            }
            
            if (!empty($output)) {
                $errorMessage .= 'Output: ' . implode(' | ', $output);
            }
            
            // Save failed backup record
            $stmt = $con->prepare("
                INSERT INTO database_backups (backup_name, file_path, backup_type, created_by, status, notes)
                VALUES (:backup_name, :file_path, 'manual', :created_by, 'failed', :notes)
            ");
            $stmt->execute([
                'backup_name' => $fileName,
                'file_path' => $filePath,
                'created_by' => $user_id,
                'notes' => $errorMessage
            ]);
            
            // Log the error
            logError(
                $con,
                'backup_failed',
                $errorMessage . ' Debug: ' . json_encode($debugInfo),
                __FILE__,
                __LINE__,
                $user_id
            );
            
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'debug' => $debugInfo // Remove this in production
            ]);
        }
    } catch (PDOException $e) {
        logError($con, 'backup_error', $e->getMessage(), __FILE__, __LINE__, $user_id);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
            if (!unlink($backup['file_path'])) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete backup file']);
                exit;
            }
        }
        
        // Delete database record
        $deleteStmt = $con->prepare("DELETE FROM database_backups WHERE id = :id");
        $deleteStmt->execute(['id' => $backupId]);
        
        // Log the action
        logActivity(
            $con,
            $user_id,
            $_SESSION['role'],
            'delete',
            $_SESSION['name'] . ' deleted backup: ' . $backup['backup_name']
        );
        
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>