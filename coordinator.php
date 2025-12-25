<?php
session_start();
include ('connect.php');
include('php/get_setting.php');

// Check maintenance mode (only coordinators/admins can access)
if (getSettingBool($con, 'maintenance_mode', false)) {
    // Allow coordinators to access
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Maintenance Mode</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }
                .maintenance-box {
                    text-align: center;
                    background: rgba(255,255,255,0.1);
                    padding: 50px;
                    border-radius: 10px;
                    backdrop-filter: blur(10px);
                }
                .maintenance-box i {
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                .maintenance-box h1 {
                    font-size: 2.5rem;
                    margin: 20px 0;
                }
                .maintenance-box p {
                    font-size: 1.2rem;
                    opacity: 0.9;
                }
            </style>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
        </head>
        <body>
            <div class="maintenance-box">
                <i class="ri-tools-line"></i>
                <h1>System Under Maintenance</h1>
                <p>We're currently performing system maintenance.</p>
                <p>Please check back later.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
include('check_session.php');

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

// Fetch notifications for the current user
$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (
        recipient_type = 'all' 
        OR recipient_type = 'coordinators'
        OR (recipient_type = 'specific' AND recipient_id = :user_id)
    )
    AND status != 'deleted'
    ORDER BY created_at DESC
    LIMIT 10
");
$notificationsStmt->execute([
    'user_id' => $_SESSION['id']
]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
<link href="https://cdn.boxicons.com/fonts/basic/boxicons.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/manage.css">
<link rel="stylesheet" href="css/notifications.css">

<title>Coordinator Dashboard</title>
</head>

<body>

<?php include("templates/aside_coordinator.html"); ?>

<!-- REQUIRED by home.css -->
<main class="main-content">

    <h1>Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']); ?>.</p>

    <!-- Notifications Section -->
    <?php if (!empty($notifications)): ?>
    <div class="notifications-section">
        <h2>
            <i class="ri-notification-3-line"></i>
            Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </h2>
        
        <div class="notifications-list">
            <?php foreach ($notifications as $notif): 
                $priorityClass = 'priority-' . strtolower($notif['priority']);
                $statusClass = $notif['status'] === 'sent' ? 'unread' : 'read';
            ?>
                <div class="notification-card <?= $priorityClass ?> <?= $statusClass ?>">
                    <div class="notification-header">
                        <span class="notification-title">
                            <?php if ($notif['status'] === 'sent'): ?>
                                <span class="new-badge">NEW</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($notif['title']) ?>
                        </span>
                        <span class="notification-date">
                            <?= date('M d, Y • h:i A', strtotime($notif['created_at'])) ?>
                        </span>
                    </div>
                    <div class="notification-body">
                        <?= nl2br(htmlspecialchars($notif['message'])) ?>
                    </div>
                    <?php if ($notif['status'] === 'sent'): ?>
                        <button class="mark-read-btn" onclick="markAsRead(<?= $notif['id'] ?>)">
                            <i class="ri-check-line"></i> Mark as Read
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="no-notifications">
        <i class="ri-notification-off-line"></i>
        <p>No notifications yet</p>
    </div>
    <?php endif; ?>

    <!-- CONTENT WRAPPER (prevents overflow bugs) -->
    <div class="dashboard-wrapper">

        <!-- PROGRESS CARD -->
        <div class="card progress-card">
            <div class="card-head">
                <strong>OVERALL RESEARCH PROGRESS</strong>
                <span class="progress-percent" id="progress-text">0%</span>
            </div>

            <div class="progress-bar">
                <div
                    id="progress-bar-fill"
                    class="progress-bar-fill"
                    style="width:0%">
                </div>
            </div>
        </div>

        <!-- CHART -->
        <div class="chart-wrapper">
            <div id="root"></div>
            <!-- Update this to coordinator build file once you build it -->
            <script
                type="module"
                src="./react-app/dist/assets/coordinator-D-BFdTQT.js"
                defer>
            </script>
        </div>

    </div>
    <div style="height: 50px; " class="space"></div>
</main>

<script src="js/notifications.js"></script>
</body>
</html>