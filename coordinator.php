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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/manage.css">
<link rel="stylesheet" href="css/notifications.css">

<title>Coordinator Dashboard</title>

<style>
/* Hide old elements more specifically */
.main-content > h1:not(.dashboard-header-coord h1) {
    display: none !important;
}

.main-content > p:first-of-type {
    display: none !important;
}

.card.progress-card {
    display: none !important;
}

/* Force white color for dashboard header text */
.dashboard-header-coord h1,
.dashboard-header-coord h1 *,
.dashboard-header-coord p,
.dashboard-header-coord strong,
.dashboard-header-coord .welcome-text-coord,
.dashboard-header-coord .welcome-text-coord strong {
    color: white !important;
}
.main-content .head{
    color: white !important;
    font-size: 34px;
    font-weight: 900;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<?php include("templates/aside_coordinator.html"); ?>

<main class="main-content">
    <!-- Enhanced Dashboard Header -->
    <div class="dashboard-header-coord">
        <div class="header-content-coord">
            <div class="header-text-coord">
                <h2 class="head"><i class="ri-dashboard-3-line"></i> Dashboard</h2>
                <p class="welcome-text-coord">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['name']); ?></strong>
                </p>
            </div>
            <div class="header-date-coord">
                <i class="ri-calendar-line"></i>
                <span id="currentDateCoord"></span>
            </div>
        </div>
    </div>

    <!-- Enhanced Progress Card -->
    <div class="progress-card-coord">
        <div class="progress-header-coord">
            <div class="progress-title-coord">
                <i class="ri-progress-3-line"></i>
                <h2>Overall Research Progress</h2>
            </div>
            <div class="progress-percentage-coord" id="progress-text-enhanced">0%</div>
        </div>
        <div class="progress-bar-wrapper-coord">
            <div id="progress-bar-fill-enhanced" class="progress-bar-fill-coord" style="width:0%"></div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid-coord">
        <div class="chart-card-coord">
            <div class="chart-header-coord">
                <h2><i class="ri-line-chart-line"></i> Analytics Overview</h2>
                <p class="chart-subtitle-coord">Comprehensive research progress tracking</p>
            </div>
            <div id="root"></div>
            <script type="module" src="./react-app/dist/assets/coordinator-CnanJDvs.js" defer></script>
        </div>
    </div>

    <!-- Hidden original elements for script compatibility -->
    <div style="display: none;">
        <h1>Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['name']); ?>.</p>
        <div class="dashboard-wrapper">
            <div class="card progress-card">
                <div class="card-head">
                    <strong>OVERALL RESEARCH PROGRESS</strong>
                    <span class="progress-percent" id="progress-text">0%</span>
                </div>
                <div class="progress-bar">
                    <div id="progress-bar-fill" class="progress-bar-fill" style="width:0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div style="height: 50px;" class="space"></div>
</main>

<script>

// Display current date
const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
const currentDate = new Date().toLocaleDateString('en-US', dateOptions);
document.getElementById('currentDateCoord').textContent = currentDate;

// Fetch and update progress from coordinator data
async function updateProgress() {
    try {
        const response = await fetch('/research_monitoring/data/get_coordinator_data.php');
        const data = await response.json();
        
        if (data.progress !== undefined) {
            const progressBar = document.getElementById('progress-bar-fill-enhanced');
            const progressText = document.getElementById('progress-text-enhanced');
            const oldProgressBar = document.getElementById('progress-bar-fill');
            const oldProgressText = document.getElementById('progress-text');
            
            const progressValue = data.progress + '%';
            
            if (progressBar) progressBar.style.width = progressValue;
            if (progressText) progressText.textContent = progressValue;
            if (oldProgressBar) oldProgressBar.style.width = progressValue;
            if (oldProgressText) oldProgressText.textContent = progressValue;
        }
    } catch (error) {
        console.error('Error fetching progress:', error);
    }
}

// Update progress on load and every 30 seconds
updateProgress();
setInterval(updateProgress, 30000);

</script>
<script src="js/notifications.js"></script>
</body>
</html>