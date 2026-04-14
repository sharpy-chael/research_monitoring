<?php
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");

if (getSettingBool($con, 'maintenance_mode', false)) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <link rel="stylesheet" href="css/maintenance.css">
            <title>Maintenance Mode</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
        </head>
        <body>
            <div class="maintenance-box">
                <i class="ri-tools-line"></i>
                <h1>System Under Maintenance</h1>
                <p>We're currently performing system maintenance.</p>
                <p>Please check back later.</p>
                <a href="home.php">Home</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$advisorUserId = $_SESSION['id'];

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (
        recipient_type = 'all'
        OR recipient_type = 'advisors'
        OR (recipient_type = 'specific' AND recipient_id = :user_id)
    )
    AND status != 'deleted'
    ORDER BY created_at DESC
    LIMIT 10
");
$notificationsStmt->execute(['user_id' => $advisorUserId]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$facultyStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
$facultyStmt->execute(['user_id' => $advisorUserId]);
$faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $faculty['id'] ?? null;

$totalGroups      = 0;
$activeLeaders    = 0;
$totalSubmissions = 0;

if ($facultyId) {
    $groupsStmt = $con->prepare("
        SELECT g.id as group_id
        FROM groups g
        WHERE g.adviser_id = :adviser_id
    ");
    $groupsStmt->execute(['adviser_id' => $facultyId]);
    $assignedGroups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalGroups = count($assignedGroups);

    foreach ($assignedGroups as $g) {
        $groupId = $g['group_id'];

        $leaderStmt = $con->prepare("
            SELECT COUNT(*) as count
            FROM student_groups
            WHERE group_id = :group_id AND is_leader = TRUE
        ");
        $leaderStmt->execute(['group_id' => $groupId]);
        $leaderCount = $leaderStmt->fetch(PDO::FETCH_ASSOC);

        if ($leaderCount['count'] > 0) {
            $activeLeaders++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/advisor.css">
    <link rel="stylesheet" href="css/notifications.css">
    <title>Advisor Dashboard</title>
</head>
<body>
<?php include("templates/aside_advisor.html"); ?>
<main class="main-content">
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <h1><i class="ri-dashboard-line"></i> Dashboard</h1>
                <p class="welcome-text">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                </p>
            </div>
            <div class="header-date">
                <i class="ri-calendar-line"></i>
                <span id="currentDate"></span>
            </div>
        </div>
    </div>

    <?php if ($totalGroups > 0): ?>
        <div class="stats-container">
            <div class="stats-summary clean-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="ri-team-line" style="color: white;"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $totalGroups ?></div>
                        <div class="stat-label">Assigned Groups</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="ri-user-star-line" style="color: white;"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $activeLeaders ?></div>
                        <div class="stat-label">Active Leaders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="ri-file-list-3-line" style="color: white;"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $totalSubmissions ?></div>
                        <div class="stat-label">Total Submissions</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="analytics-section">
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="ri-line-chart-line"></i> Progress Analytics</h2>
                <p class="chart-subtitle">Track group performance over time</p>
            </div>
            <div id="root"></div>
            <script type="module" src="./react-app/dist/assets/advisor-C9E8YwLT.js" defer></script>
        </div>
    </div>

    <div style="height: 50px;" class="space"></div>
</main>

<script>
const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', dateOptions);
const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/timeout.js"></script>
<script src="js/notifications.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>