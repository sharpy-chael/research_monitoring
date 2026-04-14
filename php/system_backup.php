<?php
session_start();
include("../connect.php");
include('log_helper.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("
            SELECT id, backup_name, file_path, file_size, backup_type, created_at, status, notes
            FROM database_backups
            ORDER BY created_at DESC
        ");
        echo json_encode(['success' => true, 'backups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $backupName = $_POST['backupName'] ?? 'backup_' . date('Y-m-d_H-i-s');
        $notes      = $_POST['backupNotes'] ?? '';

        $backupDir = '../backups/';
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create backups directory.']);
                exit;
            }
        }

        if (!is_writable($backupDir)) {
            echo json_encode(['success' => false, 'message' => 'Backups directory is not writable.']);
            exit;
        }

        $dbHost = 'localhost';
        $dbPort = '5432';
        $dbName = 'research_monitoring';
        $dbUser = 'postgres';
        $dbPass = 'pangitsiyulip';

        $fileName = $backupName . '.sql';
        $filePath = $backupDir . $fileName;
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $pgDumpPath = null;

        if ($isWindows) {
            $possiblePaths = [
                'C:\Program Files\PostgreSQL\16\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\15\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\14\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\13\bin\pg_dump.exe',
                'C:\Program Files (x86)\PostgreSQL\16\bin\pg_dump.exe',
                'C:\Program Files (x86)\PostgreSQL\15\bin\pg_dump.exe',
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) { $pgDumpPath = $path; break; }
            }
            if (!$pgDumpPath) {
                echo json_encode(['success' => false, 'message' => 'pg_dump.exe not found.']);
                exit;
            }
        } else {
            $pgDumpPath = trim(shell_exec('which pg_dump 2>/dev/null'));
            if (empty($pgDumpPath)) {
                foreach (['/usr/bin/pg_dump','/usr/local/bin/pg_dump','/usr/pgsql-16/bin/pg_dump','/usr/pgsql-15/bin/pg_dump'] as $path) {
                    if (file_exists($path)) { $pgDumpPath = $path; break; }
                }
            }
            if (empty($pgDumpPath)) {
                echo json_encode(['success' => false, 'message' => 'pg_dump not found.']);
                exit;
            }
        }

        putenv("PGPASSWORD={$dbPass}");

        $command = $isWindows
            ? sprintf('"%s" -h %s -p %s -U %s -d %s -f "%s" 2>&1', $pgDumpPath, $dbHost, $dbPort, $dbUser, $dbName, $filePath)
            : sprintf('%s -h %s -p %s -U %s -d %s -f %s 2>&1', escapeshellarg($pgDumpPath), escapeshellarg($dbHost), escapeshellarg($dbPort), escapeshellarg($dbUser), escapeshellarg($dbName), escapeshellarg($filePath));

        exec($command, $output, $returnVar);
        putenv("PGPASSWORD");

        if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
            $fileSize = filesize($filePath);

            $stmt = $con->prepare("
                INSERT INTO database_backups (backup_name, file_path, file_size, backup_type, created_by, status, notes)
                VALUES (:backup_name, :file_path, :file_size, 'manual', :created_by, 'completed', :notes)
            ");
            $stmt->execute([
                'backup_name' => $fileName,
                'file_path'   => $filePath,
                'file_size'   => $fileSize,
                'created_by'  => $user_id,
                'notes'       => $notes
            ]);

            logActivity($con, $user_id, $_SESSION['role'], 'backup', $_SESSION['name'] . ' created backup: ' . $fileName . ' (' . round($fileSize / 1024 / 1024, 2) . ' MB)');

            echo json_encode(['success' => true, 'message' => 'Backup created successfully (' . round($fileSize / 1024 / 1024, 2) . ' MB)']);
        } else {
            $errorMessage = 'Backup failed. ';
            if (!file_exists($filePath)) $errorMessage .= 'File was not created. ';
            elseif (filesize($filePath) === 0) { $errorMessage .= 'File is empty. '; @unlink($filePath); }
            if ($returnVar !== 0) $errorMessage .= 'Return code: ' . $returnVar . '. ';
            if (!empty($output)) $errorMessage .= 'Output: ' . implode(' | ', $output);

            $stmt = $con->prepare("
                INSERT INTO database_backups (backup_name, file_path, backup_type, created_by, status, notes)
                VALUES (:backup_name, :file_path, 'manual', :created_by, 'failed', :notes)
            ");
            $stmt->execute([
                'backup_name' => $fileName,
                'file_path'   => $filePath,
                'created_by'  => $user_id,
                'notes'       => $errorMessage
            ]);

            logError($con, 'backup_failed', $errorMessage, __FILE__, __LINE__, $user_id);
            echo json_encode(['success' => false, 'message' => $errorMessage]);
        }
    } catch (PDOException $e) {
        logError($con, 'backup_error', $e->getMessage(), __FILE__, __LINE__, $user_id);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data     = json_decode(file_get_contents('php://input'), true);
        $backupId = $data['backup_id'] ?? null;

        if (!$backupId) {
            echo json_encode(['success' => false, 'message' => 'Invalid backup ID']);
            exit;
        }

        $stmt = $con->prepare("SELECT file_path, backup_name FROM database_backups WHERE id = :id");
        $stmt->execute(['id' => $backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$backup) {
            echo json_encode(['success' => false, 'message' => 'Backup not found']);
            exit;
        }

        if (file_exists($backup['file_path'])) {
            if (!unlink($backup['file_path'])) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete backup file']);
                exit;
            }
        }

        $con->prepare("DELETE FROM database_backups WHERE id = :id")->execute(['id' => $backupId]);
        logActivity($con, $user_id, $_SESSION['role'], 'delete', $_SESSION['name'] . ' deleted backup: ' . $backup['backup_name']);
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>