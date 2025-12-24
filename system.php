<?php
include("connect.php");
session_start();

// Check if user is authorized (admin or coordinator only)
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$user_id = $_SESSION['id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

// Get System Statistics
try {
    // Count total users
    $totalStudents = $con->query("SELECT COUNT(*) FROM student WHERE is_active = TRUE")->fetchColumn();
    $totalAdvisors = $con->query("SELECT COUNT(*) FROM advisor WHERE is_active = TRUE")->fetchColumn();
    $totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
    $totalUploads = $con->query("SELECT COUNT(*) FROM uploads")->fetchColumn();
    
    // Get recent logs count (last 7 days)
    $recentLogs = $con->query("SELECT COUNT(*) FROM system_logs WHERE created_at >= NOW() - INTERVAL '7 days'")->fetchColumn();
    
    // Get backup count
    $totalBackups = $con->query("SELECT COUNT(*) FROM database_backups WHERE status = 'completed'")->fetchColumn();
    
    // Get pending notifications
    $pendingNotifications = $con->query("SELECT COUNT(*) FROM system_notifications WHERE status = 'pending'")->fetchColumn();
    
    // Get error count (last 24 hours)
    $recentErrors = $con->query("SELECT COUNT(*) FROM error_logs WHERE created_at >= NOW() - INTERVAL '24 hours'")->fetchColumn();
    
    // Get database size
    $dbSize = $con->query("SELECT pg_size_pretty(pg_database_size(current_database()))")->fetchColumn();
    
    // Get recent backups
    $backupsStmt = $con->query("
        SELECT id, backup_name, file_size, backup_type, created_at, status 
        FROM database_backups 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentBackups = $backupsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent logs
    $logsStmt = $con->query("
        SELECT id, user_type, action_type, description, created_at 
        FROM system_logs 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentSystemLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system settings
    $settingsStmt = $con->query("SELECT setting_key, setting_value, setting_type, description FROM system_settings ORDER BY setting_key");
    $systemSettings = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent errors
    $errorsStmt = $con->query("
        SELECT id, error_type, error_message, created_at 
        FROM error_logs 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentErrorLogs = $errorsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/system.css">
    <title>System Maintenance</title>
</head>
<body>

<?php 
    include("templates/aside_admin.html");
?>

<main class="main-content">
    <div class="page-header">
        <h1><i class="ri-settings-3-line"></i> System Maintenance</h1>
        <p class="subtitle">Manage database, logs, and system configuration</p>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer"></div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <i class="ri-database-2-line"></i>
            </div>
            <div class="stat-info">
                <h3><?= $dbSize ?></h3>
                <p>Database Size</p>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon">
                <i class="ri-save-line"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalBackups ?></h3>
                <p>Total Backups</p>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon">
                <i class="ri-file-list-3-line"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($recentLogs) ?></h3>
                <p>Logs (7 Days)</p>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="ri-error-warning-line"></i>
            </div>
            <div class="stat-info">
                <h3><?= $recentErrors ?></h3>
                <p>Errors (24h)</p>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon backup">
                <i class="ri-database-2-line"></i>
            </div>
            <h3>Database Backup</h3>
            <p>Create and manage database backups</p>
            <button class="btn-primary" onclick="openBackupModal()">
                <i class="ri-download-line"></i> Create Backup
            </button>
            <button class="btn-secondary" onclick="openViewBackupsModal()">
                <i class="ri-eye-line"></i> View Backups
            </button>
        </div>

        <div class="action-card">
            <div class="action-icon logs">
                <i class="ri-file-list-3-line"></i>
            </div>
            <h3>System Logs</h3>
            <p>View and manage system activity logs</p>
            <button class="btn-primary" onclick="openLogsModal()">
                <i class="ri-file-list-line"></i> View Logs
            </button>
            <button class="btn-secondary" onclick="exportLogs()">
                <i class="ri-download-2-line"></i> Export Logs
            </button>
        </div>

        <div class="action-card">
            <div class="action-icon notification">
                <i class="ri-notification-3-line"></i>
            </div>
            <h3>Notifications</h3>
            <p>Send notifications to users</p>
            <button class="btn-primary" onclick="openNotificationModal()">
                <i class="ri-send-plane-line"></i> Send Notification
            </button>
            <button class="btn-secondary" onclick="openViewNotificationsModal()">
                <i class="ri-inbox-line"></i> View History
            </button>
        </div>

        <div class="action-card">
            <div class="action-icon settings">
                <i class="ri-settings-3-line"></i>
            </div>
            <h3>System Settings</h3>
            <p>Configure system parameters</p>
            <button class="btn-primary" onclick="openSettingsModal()">
                <i class="ri-settings-4-line"></i> Configure
            </button>
            <button class="btn-secondary" onclick="openErrorLogsModal()">
                <i class="ri-bug-line"></i> Error Logs
            </button>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="recent-sections">
        <div class="recent-backups">
            <h2><i class="ri-history-line"></i> Recent Backups</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Backup Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentBackups)): ?>
                            <?php foreach ($recentBackups as $backup): ?>
                                <tr>
                                    <td><?= htmlspecialchars($backup['backup_name']) ?></td>
                                    <td><span class="badge <?= $backup['backup_type'] ?>"><?= ucfirst($backup['backup_type']) ?></span></td>
                                    <td><?= $backup['file_size'] ? number_format($backup['file_size'] / 1024 / 1024, 2) . ' MB' : 'N/A' ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($backup['created_at'])) ?></td>
                                    <td><span class="status-badge status-<?= $backup['status'] ?>"><?= ucfirst($backup['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-data">No backups available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recent-logs">
            <h2><i class="ri-file-list-line"></i> Recent System Logs</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User Type</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentSystemLogs)): ?>
                            <?php foreach ($recentSystemLogs as $log): ?>
                                <tr>
                                    <td><span class="badge <?= strtolower($log['user_type']) ?>"><?= ucfirst($log['user_type']) ?></span></td>
                                    <td><?= htmlspecialchars($log['action_type']) ?></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-data">No logs available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="space"></div>
</main>

<!-- Modals will be included here -->
<div id="modalContainer"></div>

<script src="js/system.js"></script>
</body>
</html>