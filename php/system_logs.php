<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || !in_array($_SESSION['role'], ['admin', 'coordinator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

// List logs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    try {
        if ($_GET['action'] === 'list') {
            $stmt = $con->query("
                SELECT id, user_id, user_type, action_type, description, ip_address, user_agent, created_at
                FROM system_logs
                ORDER BY created_at DESC
                LIMIT 500
            ");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'logs' => $logs]);
        } elseif ($_GET['action'] === 'errors') {
            $stmt = $con->query("
                SELECT id, error_type, error_message, error_file, error_line, user_id, ip_address, created_at
                FROM error_logs
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'errors' => $errors]);
        } elseif ($_GET['action'] === 'export') {
            // Export logs as CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, ['ID', 'User Type', 'Action Type', 'Description', 'IP Address', 'Date']);
            
            $stmt = $con->query("
                SELECT id, user_type, action_type, description, ip_address, created_at
                FROM system_logs
                ORDER BY created_at DESC
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['user_type'],
                    $row['action_type'],
                    $row['description'],
                    $row['ip_address'],
                    $row['created_at']
                ]);
            }
            
            fclose($output);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Delete logs
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        if (isset($_GET['action']) && $_GET['action'] === 'clear_errors') {
            // Clear error logs
            $con->exec("DELETE FROM error_logs");
            
            // Log the action
            $logStmt = $con->prepare("
                INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
                VALUES (:user_id, :user_type, 'clear_logs', 'Cleared all error logs', :ip_address)
            ");
            $logStmt->execute([
                'user_id' => $user_id,
                'user_type' => $_SESSION['role'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Error logs cleared successfully']);
        } else {
            // Clear system logs
            $con->exec("DELETE FROM system_logs");
            
            // Create a log entry about clearing logs (meta!)
            $logStmt = $con->prepare("
                INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
                VALUES (:user_id, :user_type, 'clear_logs', 'Cleared all system logs', :ip_address)
            ");
            $logStmt->execute([
                'user_id' => $user_id,
                'user_type' => $_SESSION['role'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'System logs cleared successfully']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>