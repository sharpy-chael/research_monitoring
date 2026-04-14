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
        SELECT year_start, year_end FROM academic_years
        WHERE is_active = true AND CURRENT_DATE BETWEEN year_start AND year_end
        LIMIT 1
    ");
    $ayStmt->execute();
    $activeAY = $ayStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activeAY) {
        $latestAY = $con->query("
            SELECT year_start, year_end FROM academic_years ORDER BY year_end DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $yearLabel = $latestAY
            ? "Academic Year " . date('Y', strtotime($latestAY['year_start'])) . "–" . date('Y', strtotime($latestAY['year_end']))
            : "the current academic year";

        include('php/log_helper.php');
        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'session_ended', $_SESSION['name'] . ' session ended — academic year inactive or expired');

        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error_message'] = "The {$yearLabel} is over. Please contact the administrator.";
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {}

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

$studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
$studentStmt->execute(['school_id' => $school_id]);
$studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);
$studentId  = $studentRow['id'] ?? null;

$allGroups = [];
if ($studentId) {
    $sgStmt = $con->prepare("
        SELECT sg.group_id, sg.is_leader, g.name AS group_name,
               g.research_title, g.title_status, g.research_id
        FROM student_groups sg
        JOIN groups g ON sg.group_id = g.id
        WHERE sg.student_id = :student_id
        ORDER BY sg.id ASC
    ");
    $sgStmt->execute(['student_id' => $studentId]);
    $allGroups = $sgStmt->fetchAll(PDO::FETCH_ASSOC);
}

$firstGroup = $allGroups[0] ?? null;
$_SESSION['research_title'] = $firstGroup['research_title'] ?? '';

$REQUIRED_TOTAL = 6;

$groupProgressMap = [];

foreach ($allGroups as $grp) {
    $gid   = $grp['group_id'];
    $rid   = $grp['research_id'];
    $count = 0;

    $msStmt = $con->prepare("
        SELECT g.title_status,
               gm.proposal_status, gm.final_defense_status, gm.hardbound_submitted_status
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $msStmt->execute(['group_id' => $gid]);
    $ms = $msStmt->fetch(PDO::FETCH_ASSOC);

    if ($ms) {
        if (($ms['title_status']               ?? '') === 'approved')  $count++;
        if (($ms['proposal_status']            ?? '') === 'completed') $count++;
        if (($ms['final_defense_status']       ?? '') === 'completed') $count++;
        if (($ms['hardbound_submitted_status'] ?? '') === 'completed') $count++;
    }

    if ($rid) {
        $urecStmt = $con->prepare("
            SELECT document_type, status FROM urec_documents
            WHERE research_id = :research_id ORDER BY uploaded_at DESC
        ");
        $urecStmt->execute(['research_id' => $rid]);
        $urecMap = [];
        foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
        }
        if (isset($urecMap['UREC Form'])      && $urecMap['UREC Form']['status']      === 'approved') $count++;
        if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved') $count++;
    }

    $groupProgressMap[$gid] = [
        'count'   => $count,
        'percent' => round(($count / $REQUIRED_TOTAL) * 100),
    ];
}

$firstGroupId       = $firstGroup['group_id'] ?? null;
$approvedCount      = $firstGroupId ? ($groupProgressMap[$firstGroupId]['count']   ?? 0) : 0;
$progressPercentage = $firstGroupId ? ($groupProgressMap[$firstGroupId]['percent'] ?? 0) : 0;

$groupMembersData = [];
foreach ($allGroups as $grp) {
    $gid = $grp['group_id'];

    $leaderStmt = $con->prepare("
        SELECT TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(s.middlename,'') || ' ' || COALESCE(s.lastname,'')) AS name
        FROM students s
        JOIN student_groups sg ON s.id = sg.student_id
        WHERE sg.group_id = :gid AND sg.is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['gid' => $gid]);
    $leader = $leaderStmt->fetchColumn() ?: 'Not assigned';

    $membersStmt = $con->prepare("
        SELECT TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(s.middlename,'') || ' ' || COALESCE(s.lastname,'')) AS name
        FROM students s
        JOIN student_groups sg ON s.id = sg.student_id
        WHERE sg.group_id = :gid AND sg.is_leader = FALSE
        ORDER BY s.firstname
    ");
    $membersStmt->execute(['gid' => $gid]);
    $members = $membersStmt->fetchAll(PDO::FETCH_COLUMN);

    $groupMembersData[$gid] = [
        'leader'    => $leader,
        'members'   => $members,
        'is_leader' => $grp['is_leader'],
    ];
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

    <div class="title-group-row">

        <div class="tg-card">
            <div class="tg-card-header">
                <div class="tg-card-icon"><i class="ri-book-open-line"></i></div>
                <span class="tg-card-label">Research Titles</span>
            </div>
            <div class="tg-card-body">
                <?php if (empty($allGroups)): ?>
                    <div class="tg-empty">No title yet</div>
                <?php else: ?>
                    <?php foreach ($allGroups as $idx => $grp):
                        $ts = $grp['title_status'] ?? 'missing';
                        $badgeClass = match($ts) {
                            'approved'         => 'status-approved',
                            'pending_approval' => 'status-pending',
                            'rejected'         => 'status-rejected',
                            default            => 'status-missing'
                        };
                        $badgeText = match($ts) {
                            'approved'         => 'Approved',
                            'pending_approval' => 'Pending',
                            'rejected'         => 'Rejected',
                            default            => 'Missing'
                        };
                    ?>
                        <div class="tg-item tg-title-clickable <?= $idx === 0 ? 'tg-title-active' : '' ?>"
                             data-group-id="<?= $grp['group_id'] ?>"
                             onclick="switchGroupProgress(<?= $grp['group_id'] ?>, this)">
                            <span class="tg-item-title"><?= htmlspecialchars($grp['research_title'] ?: 'No title yet') ?></span>
                            <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tg-card">
            <div class="tg-card-header">
                <div class="tg-card-icon"><i class="ri-team-line"></i></div>
                <span class="tg-card-label">Groups</span>
            </div>
            <div class="tg-card-body">
                <?php if (empty($allGroups)): ?>
                    <div class="tg-empty">No group yet</div>
                <?php else: ?>
                    <?php foreach ($allGroups as $grp): ?>
                        <div class="tg-item tg-group-item">
                            <span class="tg-item-title"><?= htmlspecialchars($grp['group_name']) ?></span>
                            <div class="tg-group-right">
                                <?php if ($grp['is_leader']): ?>
                                    <span class="tg-leader-badge"><i class="ri-star-fill"></i> Leader</span>
                                <?php else: ?>
                                    <span class="tg-member-badge">Member</span>
                                <?php endif; ?>
                                <button class="tg-dots-btn"
                                    onclick="openGroupModal(<?= $grp['group_id'] ?>)"
                                    title="View members">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php foreach ($allGroups as $grp):
        $gid  = $grp['group_id'];
        $data = $groupMembersData[$gid];
    ?>
    <div class="modal" id="groupModal_<?= $gid ?>">
        <div class="modal-overlay" onclick="closeGroupModal(<?= $gid ?>)"></div>
        <div class="modal-content group-modal-content">
            <button class="modal-close" onclick="closeGroupModal(<?= $gid ?>)">&times;</button>
            <h3><i class="ri-team-line"></i> <?= htmlspecialchars($grp['group_name']) ?></h3>

            <div class="group-modal-section">
                <div class="group-modal-label"><i class="ri-star-fill"></i> Leader</div>
                <div class="group-modal-member leader-member">
                    <i class="ri-user-star-line"></i>
                    <span><?= htmlspecialchars($data['leader']) ?></span>
                    <?php if ($data['is_leader']): ?>
                        <span class="group-you-badge">You</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($data['members'])): ?>
            <div class="group-modal-section">
                <div class="group-modal-label"><i class="ri-group-line"></i> Members</div>
                <?php foreach ($data['members'] as $member): ?>
                    <div class="group-modal-member">
                        <i class="ri-user-3-line"></i>
                        <span><?= htmlspecialchars($member) ?></span>
                        <?php if (!$data['is_leader'] && trim($member) === trim($_SESSION['name'])): ?>
                            <span class="group-you-badge">You</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="group-modal-section">
                <div class="group-modal-label"><i class="ri-group-line"></i> Members</div>
                <p style="color:#999;font-size:13px;padding:8px 0;">No other members yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="progress-card-enhanced">
        <div class="progress-header">
            <div class="progress-title">
                <i class="ri-bar-chart-box-line"></i>
                <h2>Research Progress</h2>
            </div>
            <div class="progress-percentage" id="progressPercent"><?= $progressPercentage ?>%</div>
        </div>
        <div class="progress-description" id="progressDesc">
            <?= $approvedCount ?> of <?= $REQUIRED_TOTAL ?> Required Milestones Completed
        </div>
        <div class="progress-bar-enhanced">
            <div class="progress-bar-fill-enhanced" id="progressBarFill" style="width: <?= $progressPercentage ?>%;"></div>
        </div>
    </div>

    <div class="analytics-section-student">
        <div class="chart-container-student">
            <div class="chart-header-student">
                <h2><i class="ri-line-chart-line"></i> Progress Timeline</h2>
                <p class="chart-subtitle-student">Track your research journey</p>
            </div>
            <div id="root"></div>
            <script type="module" src="./react-app/dist/assets/student-DskrHSe7.js" defer></script>
        </div>
    </div>

    <div class="space"></div>
</main>

<script>
const groupProgressMap = <?= json_encode($groupProgressMap) ?>;
const requiredTotal    = <?= $REQUIRED_TOTAL ?>;

function switchGroupProgress(groupId, el) {
    document.querySelectorAll('.tg-title-clickable').forEach(t => t.classList.remove('tg-title-active'));
    el.classList.add('tg-title-active');

    const data  = groupProgressMap[groupId] || { count: 0, percent: 0 };
    const pct   = data.percent;
    const count = data.count;

    document.getElementById('progressPercent').textContent = pct + '%';
    document.getElementById('progressDesc').textContent    = count + ' of ' + requiredTotal + ' Required Milestones Completed';

    const fill = document.getElementById('progressBarFill');
    fill.style.transition = 'width 0.5s ease';
    fill.style.width      = pct + '%';
}

function openGroupModal(gid) {
    document.getElementById('groupModal_' + gid).classList.add('open');
}
function closeGroupModal(gid) {
    document.getElementById('groupModal_' + gid).classList.remove('open');
}

const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
document.getElementById('currentDateStudent').textContent =
    new Date().toLocaleDateString('en-US', dateOptions);

const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/notifications.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>