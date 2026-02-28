<?php
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$school_id = $_SESSION['school_id'];

$sessionTimeoutMinutes = getSettingInt($con, 'session_timeout', 30);
$sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;

if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    if ($inactiveTime > $sessionTimeoutSeconds) {
        include('php/log_helper.php');
        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'session_timeout', $_SESSION['name'] . ' session expired');
        session_unset();
        session_destroy();
        header('Location: portal.php?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

try {
    $ayStmt = $con->prepare("
        SELECT year_start, year_end
        FROM academic_years
        WHERE is_active = true
          AND CURRENT_DATE BETWEEN year_start AND year_end
        LIMIT 1
    ");
    $ayStmt->execute();
    $activeAY = $ayStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activeAY) {
        $latestAY = $con->query("
            SELECT year_start, year_end
            FROM academic_years
            ORDER BY year_end DESC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $yearLabel = $latestAY
            ? "Academic Year " . date('Y', strtotime($latestAY['year_start']))
              . "–" . date('Y', strtotime($latestAY['year_end']))
            : "the current academic year";

        include('php/log_helper.php');
        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'session_ended',
            $_SESSION['name'] . ' session ended — academic year inactive or expired');

        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error_message'] = "The {$yearLabel} is over. Please contact the administrator.";
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    // If academic_years table doesn't exist, continue normally
}

// ── Maintenance mode ──────────────────────────────────────────────────────────
if (getSettingBool($con, 'maintenance_mode', false)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Maintenance Mode</title>
        <link rel="stylesheet" href="css/maintenance.css">
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

// ── Notifications ─────────────────────────────────────────────────────────────
$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (
        recipient_type = 'all'
        OR recipient_type = 'students'
        OR (recipient_type = 'specific' AND recipient_id = :user_id)
    )
    AND status != 'deleted'
    ORDER BY created_at DESC
    LIMIT 10
");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

// ── Student / group data ──────────────────────────────────────────────────────
$stmt = $con->prepare("
    SELECT s.group_id, s.is_leader, g.research_title, g.title_status
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    WHERE s.school_id = :school_id
");
$stmt->execute(['school_id' => $school_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$group_id    = $user['group_id'];
$is_leader   = $user['is_leader'];
$groupTitle  = $user['research_title'] ?? '';
$titleStatus = $user['title_status']   ?? 'missing';

$_SESSION['research_title'] = $groupTitle;

// ── Progress calculation (10 requirements total) ──────────────────────────────
// Requirements:
// 1.  Title approved
// 2.  Proposal
// 3.  UREC Form
// 4.  UREC Clearance
// 5.  Final Defense
// 6.  Applied for Copyright
// 7.  Research Presented
// 8.  Research Published
// 9.  Copyright Approved
// 10. Full Manuscript upload (at least one approved)

$approvedCount = 0;

// -- Full Manuscript (uploads) — counts as 1 if ANY upload is approved ---------
$progressStmt = $con->prepare("
    SELECT task_name, status
    FROM uploads
    WHERE school_id IN (SELECT school_id FROM student WHERE group_id = :group_id)
    ORDER BY uploaded_at DESC
");
$progressStmt->execute(['group_id' => $group_id]);
$allUploads = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

$uploadMap = [];
foreach ($allUploads as $upload) {
    if (!isset($uploadMap[$upload['task_name']])) {
        $uploadMap[$upload['task_name']] = $upload;
    }
}

foreach ($uploadMap as $upload) {
    if ($upload['status'] === 'approved') {
        $approvedCount++;
        break; // manuscript is 1 requirement regardless of how many tasks
    }
}

// -- Milestones (7 items) + UREC (2 items) = 9 --------------------------------
$milestoneStmt = $con->prepare("
    SELECT
        g.title_status,
        gm.proposal_status,
        gm.final_defense_status,
        gm.applied_copyright_status,
        gm.research_presented_status,
        gm.research_published_status,
        gm.copyright_approved_status
    FROM groups g
    LEFT JOIN group_milestones gm ON g.id = gm.group_id
    WHERE g.id = :group_id
");
$milestoneStmt->execute(['group_id' => $group_id]);
$milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

if ($milestones) {
    if (($milestones['title_status']              ?? '') === 'approved')  $approvedCount++;
    if (($milestones['proposal_status']           ?? '') === 'completed') $approvedCount++;
    if (($milestones['final_defense_status']      ?? '') === 'completed') $approvedCount++;
    if (($milestones['applied_copyright_status']  ?? '') === 'completed') $approvedCount++;
    if (($milestones['research_presented_status'] ?? '') === 'completed') $approvedCount++;
    if (($milestones['research_published_status'] ?? '') === 'completed') $approvedCount++;
    if (($milestones['copyright_approved_status'] ?? '') === 'completed') $approvedCount++;
}

// -- UREC documents (2 items) --------------------------------------------------
$urecStmt = $con->prepare("
    SELECT document_type, status FROM urec_documents
    WHERE group_id = :group_id ORDER BY uploaded_at DESC
");
$urecStmt->execute(['group_id' => $group_id]);
$urecDocs = $urecStmt->fetchAll(PDO::FETCH_ASSOC);

$urecMap = [];
foreach ($urecDocs as $doc) {
    if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
}

if (isset($urecMap['UREC Form'])      && $urecMap['UREC Form']['status']      === 'approved') $approvedCount++;
if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved') $approvedCount++;

// Grand total = 10
$progressPercentage = round(($approvedCount / 10) * 100);
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
    <link rel="stylesheet" href="css/notifications.css">
    <title>Student Dashboard</title>
</head>
<body>

<?php include("templates/aside_student.html"); ?>

<main class="main-content">
    <div class="dashboard-header-student">
        <div class="header-content-student">
            <div class="header-text-student">
                <h1><i class="ri-dashboard-line"></i> Dashboard</h1>
                <p class="welcome-text-student">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
                </p>
            </div>
            <div class="header-date-student">
                <i class="ri-calendar-line"></i>
                <span id="currentDateStudent"></span>
            </div>
        </div>
    </div>

    <div class="research-title-card">
        <div class="title-icon"><i class="ri-book-open-line"></i></div>
        <div class="title-content">
            <h3>Research Title</h3>
            <p><?= htmlspecialchars($groupTitle ?: 'No title yet') ?></p>
            <?php if ($titleStatus !== 'missing'): ?>
                <span class="status-badge status-<?= $titleStatus ?>"><?= ucfirst($titleStatus) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="progress-card-enhanced">
        <div class="progress-header">
            <div class="progress-title">
                <i class="ri-bar-chart-box-line"></i>
                <h2>Research Progress</h2>
            </div>
            <div class="progress-percentage"><?= $progressPercentage ?>%</div>
        </div>
        <div class="progress-description"><?= $approvedCount ?> of 10 Requirements Completed</div>
        <div class="progress-bar-enhanced">
            <div class="progress-bar-fill-enhanced" style="width: <?= $progressPercentage ?>%;"></div>
        </div>
    </div>

    <div class="analytics-section-student">
        <div class="chart-container-student">
            <div class="chart-header-student">
                <h2><i class="ri-line-chart-line"></i> Progress Timeline</h2>
                <p class="chart-subtitle-student">Track your research journey</p>
            </div>
            <div id="root"></div>
            <script type="module" src="./react-app/dist/assets/student-DSRl6HVe.js" defer></script>
        </div>
    </div>

    <div class="space"></div>
</main>

<script>
const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
document.getElementById('currentDateStudent').textContent =
    new Date().toLocaleDateString('en-US', dateOptions);

const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/notifications.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>