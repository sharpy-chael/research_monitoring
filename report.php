<?php
session_start();
include('connect.php');
include('php/get_setting.php');
include("check_session.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'approve_title' || $action === 'reject_title') {
        $groupId  = $_POST['group_id'] ?? null;
        $comment  = trim($_POST['comment'] ?? '');
        if (!$groupId) { echo json_encode(['success' => false, 'message' => 'Group ID required']); exit; }
        $newStatus     = $action === 'approve_title' ? 'approved' : 'rejected';
        $coordinatorId = $_SESSION['id'];
        try {
            if ($newStatus === 'approved') {
                $stmt = $con->prepare("UPDATE groups SET title_status = :status, title_approval_comment = :comment, title_approved_at = NOW(), title_approved_by = :coord_id WHERE id = :group_id");
                $stmt->execute(['status' => $newStatus, 'comment' => $comment, 'coord_id' => $coordinatorId, 'group_id' => $groupId]);
            } else {
                $stmt = $con->prepare("UPDATE groups SET title_status = :status, title_approval_comment = :comment WHERE id = :group_id");
                $stmt->execute(['status' => $newStatus, 'comment' => $comment, 'group_id' => $groupId]);
            }
            echo json_encode(['success' => true, 'message' => $action === 'approve_title' ? 'Title approved successfully' : 'Title rejected', 'new_status' => $newStatus]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action === 'approve_milestone' || $action === 'reject_milestone') {
        $groupId       = $_POST['group_id'] ?? null;
        $milestoneType = $_POST['milestone_type'] ?? null;
        $comment       = trim($_POST['comment'] ?? '');
        if (!$groupId || !$milestoneType) { echo json_encode(['success' => false, 'message' => 'Invalid parameters']); exit; }
        $validMilestones = ['proposal', 'final_defense', 'hardbound_submitted', 'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'];
        $validUrec       = ['urec_form', 'urec_clearance'];

        if (in_array($milestoneType, $validUrec)) {
            $newStatus = $action === 'approve_milestone' ? 'approved' : 'rejected';
            $docType   = $milestoneType === 'urec_form' ? 'UREC Form' : 'UREC Clearance';
            try {
                $resStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :group_id");
                $resStmt->execute(['group_id' => $groupId]);
                $resRow = $resStmt->fetch(PDO::FETCH_ASSOC);
                if (!$resRow || !$resRow['research_id']) { echo json_encode(['success' => false, 'message' => 'Research not found']); exit; }
                if ($newStatus === 'approved') {
                    $stmt = $con->prepare("UPDATE urec_documents SET status = :status, comment = :comment WHERE research_id = :research_id AND document_type = :doc_type");
                } else {
                    $stmt = $con->prepare("UPDATE urec_documents SET status = :status, comment = :comment, approved_at = NULL WHERE research_id = :research_id AND document_type = :doc_type");
                }
                $stmt->execute(['status' => $newStatus, 'comment' => $comment, 'research_id' => $resRow['research_id'], 'doc_type' => $docType]);
                echo json_encode(['success' => true, 'message' => $action === 'approve_milestone' ? 'Milestone approved successfully' : 'Milestone rejected', 'new_status' => $newStatus]);
            } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
            exit;
        }

        if (!in_array($milestoneType, $validMilestones)) { echo json_encode(['success' => false, 'message' => 'Invalid milestone type']); exit; }
        $newStatus        = $action === 'approve_milestone' ? 'completed' : 'rejected';
        $statusColumn     = $milestoneType . '_status';
        $approvedAtColumn = $milestoneType . '_approved_at';
        try {
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $groupId]);
            if ($checkStmt->rowCount() > 0) {
                if ($newStatus === 'completed') {
                    $stmt = $con->prepare("UPDATE group_milestones SET {$statusColumn} = :status WHERE group_id = :group_id");
                } else {
                    $stmt = $con->prepare("UPDATE group_milestones SET {$statusColumn} = :status, {$approvedAtColumn} = NULL WHERE group_id = :group_id");
                }
                $stmt->execute(['status' => $newStatus, 'group_id' => $groupId]);
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$statusColumn}) VALUES (:group_id, :status)");
                $stmt->execute(['group_id' => $groupId, 'status' => $newStatus]);
            }
            echo json_encode(['success' => true, 'message' => $action === 'approve_milestone' ? 'Milestone approved successfully' : 'Milestone rejected', 'new_status' => $newStatus]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action === 'poll_counts') {
        try {
            $tc = $con->query("SELECT COUNT(*) FROM groups WHERE title_status = 'pending_approval'")->fetchColumn();
            $mc = $con->query("
                SELECT COUNT(DISTINCT g.id) FROM groups g
                LEFT JOIN group_milestones gm ON g.id = gm.group_id
                WHERE g.title_status = 'approved'
                AND (
                    EXISTS (
                        SELECT 1 FROM research_updates ru
                        WHERE ru.research_id = g.research_id
                        AND ru.milestone_type IS NOT NULL
                        AND COALESCE(
                            CASE ru.milestone_type
                                WHEN 'proposal'            THEN gm.proposal_status
                                WHEN 'final_defense'       THEN gm.final_defense_status
                                WHEN 'hardbound_submitted' THEN gm.hardbound_submitted_status
                                WHEN 'applied_copyright'   THEN gm.applied_copyright_status
                                WHEN 'research_presented'  THEN gm.research_presented_status
                                WHEN 'research_published'  THEN gm.research_published_status
                                WHEN 'copyright_approved'  THEN gm.copyright_approved_status
                            END, 'pending'
                        ) = 'endorsed'
                    )
                    OR EXISTS (
                        SELECT 1 FROM urec_documents ud
                        WHERE ud.research_id = g.research_id
                        AND ud.status = 'endorsed'
                    )
                )
            ")->fetchColumn();
            echo json_encode(['titles' => (int)$tc, 'milestones' => (int)$mc]);
        } catch (Exception $e) { echo json_encode(['titles' => 0, 'milestones' => 0]); }
        exit;
    }
}

if (!isset($_SESSION['submit'])) { header('Location: home.php'); exit; }

$statsQuery = $con->query("
    SELECT COUNT(DISTINCT g.id) as total_groups, COUNT(DISTINCT sg.student_id) as total_students, COUNT(DISTINCT f.id) as total_advisors
    FROM groups g
    LEFT JOIN student_groups sg ON g.id = sg.group_id
    LEFT JOIN faculties f ON g.adviser_id = f.id
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$statusQuery = $con->query("SELECT SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected FROM uploads");
$statusData  = $statusQuery->fetch(PDO::FETCH_ASSOC);

$sdgQuery = $con->query("SELECT sd.id as sdg_id, sd.name as sdg_name, COUNT(DISTINCT g.id) as group_count FROM sdgs sd JOIN thrusts_assignments ta ON sd.id = ta.sdg_id JOIN groups g ON g.research_id = ta.research_id GROUP BY sd.id, sd.name HAVING COUNT(DISTINCT g.id) > 0 ORDER BY group_count DESC, sd.name");
$sdgData  = $sdgQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedSdgCount = $con->query("SELECT COUNT(DISTINCT g.id) FROM groups g WHERE g.research_id IS NULL OR NOT EXISTS (SELECT 1 FROM thrusts_assignments ta WHERE ta.research_id = g.research_id AND ta.sdg_id IS NOT NULL)")->fetchColumn();
if ($unassignedSdgCount > 0) $sdgData[] = ['sdg_id' => 0, 'sdg_name' => 'Unassigned', 'group_count' => $unassignedSdgCount];

$thrustQuery = $con->query("SELECT t.id as thrust_id, t.name as thrust_name, COUNT(DISTINCT g.id) as group_count FROM thrusts t JOIN thrusts_assignments ta ON t.id = ta.thrust_id JOIN groups g ON g.research_id = ta.research_id GROUP BY t.id, t.name HAVING COUNT(DISTINCT g.id) > 0 ORDER BY group_count DESC, t.name");
$thrustData  = $thrustQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedThrustCount = $con->query("SELECT COUNT(DISTINCT g.id) FROM groups g WHERE g.research_id IS NULL OR NOT EXISTS (SELECT 1 FROM thrusts_assignments ta WHERE ta.research_id = g.research_id AND ta.thrust_id IS NOT NULL)")->fetchColumn();
if ($unassignedThrustCount > 0) $thrustData[] = ['thrust_id' => 0, 'thrust_name' => 'Unassigned', 'group_count' => $unassignedThrustCount];

$pendingTitles = $con->query("
    SELECT g.id, g.name, g.research_title, g.title_proposal_file, g.title_proposal_filename,
           COALESCE(u.username, 'Not assigned') as advisor_name,
           (SELECT TRIM(COALESCE(s2.firstname,'') || ' ' || COALESCE(s2.middlename,'') || ' ' || COALESCE(s2.lastname,''))
            FROM students s2 JOIN student_groups sg2 ON s2.id = sg2.student_id
            WHERE sg2.group_id = g.id AND sg2.is_leader = TRUE LIMIT 1) as leader_name
    FROM groups g LEFT JOIN faculties f ON g.adviser_id = f.id LEFT JOIN users u ON f.user_id = u.id
    WHERE g.title_status = 'pending_approval' ORDER BY g.name
")->fetchAll(PDO::FETCH_ASSOC);

$pendingMilestones = $con->query("
    SELECT g.id, g.name, g.research_title,
           COALESCE(u.username, 'Not assigned') as advisor_name,
           (SELECT TRIM(COALESCE(s2.firstname,'') || ' ' || COALESCE(s2.middlename,'') || ' ' || COALESCE(s2.lastname,''))
            FROM students s2 JOIN student_groups sg2 ON s2.id = sg2.student_id
            WHERE sg2.group_id = g.id AND sg2.is_leader = TRUE LIMIT 1) as leader_name,
           gm.proposal_status, gm.final_defense_status, gm.hardbound_submitted_status,
           gm.applied_copyright_status, gm.research_presented_status,
           gm.research_published_status, gm.copyright_approved_status,
           gm.proposal_approved_at, gm.final_defense_approved_at, gm.hardbound_submitted_approved_at,
           gm.applied_copyright_approved_at, gm.research_presented_approved_at,
           gm.research_published_approved_at, gm.copyright_approved_approved_at
    FROM groups g
    LEFT JOIN group_milestones gm ON g.id = gm.group_id
    LEFT JOIN faculties f ON g.adviser_id = f.id LEFT JOIN users u ON f.user_id = u.id
    WHERE g.title_status = 'approved'
    AND (
        EXISTS (
            SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type IS NOT NULL
            AND COALESCE(CASE ru.milestone_type
                WHEN 'proposal'            THEN gm.proposal_status
                WHEN 'final_defense'       THEN gm.final_defense_status
                WHEN 'hardbound_submitted' THEN gm.hardbound_submitted_status
                WHEN 'applied_copyright'   THEN gm.applied_copyright_status
                WHEN 'research_presented'  THEN gm.research_presented_status
                WHEN 'research_published'  THEN gm.research_published_status
                WHEN 'copyright_approved'  THEN gm.copyright_approved_status
            END, 'pending') = 'endorsed'
        )
        OR EXISTS (SELECT 1 FROM urec_documents ud WHERE ud.research_id = g.research_id AND ud.status = 'endorsed')
    )
    ORDER BY g.name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($pendingMilestones as &$pm) {
    $ruStmt = $con->prepare("SELECT milestone_type, file_path, original_filename, uploaded_at FROM research_updates WHERE research_id = (SELECT research_id FROM groups WHERE id = :group_id) AND milestone_type IS NOT NULL ORDER BY uploaded_at DESC");
    $ruStmt->execute(['group_id' => $pm['id']]);
    foreach ($ruStmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $pm[$f['milestone_type'] . '_file_path']         = $f['file_path'];
        $pm[$f['milestone_type'] . '_original_filename'] = $f['original_filename'];
        $pm[$f['milestone_type'] . '_uploaded_at']       = $f['uploaded_at'];
    }

    $urecStmt = $con->prepare("SELECT document_type, file_path, original_filename, status, uploaded_at, approved_at FROM urec_documents WHERE research_id = (SELECT research_id FROM groups WHERE id = :group_id) AND status = 'endorsed'");
    $urecStmt->execute(['group_id' => $pm['id']]);
    foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $ud) {
        $key = ($ud['document_type'] === 'UREC Form') ? 'urec_form' : 'urec_clearance';
        $pm[$key . '_file_path']         = $ud['file_path'];
        $pm[$key . '_original_filename'] = $ud['original_filename'];
        $pm[$key . '_uploaded_at']       = $ud['uploaded_at'];
        $pm[$key . '_approved_at']       = $ud['approved_at'] ?? null;
        $pm[$key . '_status']            = $ud['status'];
    }
}
unset($pm);

// ── Approval History ──────────────────────────────────────────────────────
$recentApprovedTitles = $con->query("
    SELECT g.id, g.name AS group_name, g.research_title, g.title_approved_at,
           COALESCE(f.name, 'Not assigned') AS adviser_name,
           (SELECT TRIM(COALESCE(s2.firstname,'') || ' ' || COALESCE(s2.middlename,'') || ' ' || COALESCE(s2.lastname,''))
            FROM students s2 JOIN student_groups sg2 ON s2.id = sg2.student_id
            WHERE sg2.group_id = g.id AND sg2.is_leader = TRUE LIMIT 1) AS leader_name
    FROM groups g
    LEFT JOIN faculties f ON g.adviser_id = f.id
    WHERE g.title_status = 'approved' AND g.title_approved_at IS NOT NULL
    ORDER BY g.title_approved_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$milestoneLabels = [
    'proposal'            => ['label' => 'Proposal Approved',  'icon' => 'ri-file-text-line'],
    'final_defense'       => ['label' => 'Research Completed', 'icon' => 'ri-presentation-line'],
    'hardbound_submitted' => ['label' => 'Hardbound Submitted','icon' => 'ri-book-2-line'],
    'applied_copyright'   => ['label' => 'Copyright Applied',  'icon' => 'ri-copyright-line'],
    'research_presented'  => ['label' => 'Research Presented', 'icon' => 'ri-slideshow-3-line'],
    'research_published'  => ['label' => 'Research Published', 'icon' => 'ri-newspaper-line'],
    'copyright_approved'  => ['label' => 'Copyright Approved', 'icon' => 'ri-shield-check-line'],
];

$recentApprovedMilestones = $con->query("
    SELECT g.id AS group_id, g.name AS group_name, g.research_title,
           COALESCE(f.name, 'Not assigned') AS adviser_name,
           gm.proposal_status,            gm.proposal_approved_at,
           gm.final_defense_status,       gm.final_defense_approved_at,
           gm.hardbound_submitted_status, gm.hardbound_submitted_approved_at,
           gm.applied_copyright_status,   gm.applied_copyright_approved_at,
           gm.research_presented_status,  gm.research_presented_approved_at,
           gm.research_published_status,  gm.research_published_approved_at,
           gm.copyright_approved_status,  gm.copyright_approved_approved_at
    FROM groups g
    JOIN group_milestones gm ON g.id = gm.group_id
    LEFT JOIN faculties f ON g.adviser_id = f.id
    WHERE (
        gm.proposal_status            = 'completed' OR
        gm.final_defense_status       = 'completed' OR
        gm.hardbound_submitted_status = 'completed' OR
        gm.applied_copyright_status   = 'completed' OR
        gm.research_presented_status  = 'completed' OR
        gm.research_published_status  = 'completed' OR
        gm.copyright_approved_status  = 'completed'
    )
    ORDER BY GREATEST(
        COALESCE(gm.proposal_approved_at,           '1970-01-01'),
        COALESCE(gm.final_defense_approved_at,      '1970-01-01'),
        COALESCE(gm.hardbound_submitted_approved_at,'1970-01-01'),
        COALESCE(gm.applied_copyright_approved_at,  '1970-01-01'),
        COALESCE(gm.research_presented_approved_at, '1970-01-01'),
        COALESCE(gm.research_published_approved_at, '1970-01-01'),
        COALESCE(gm.copyright_approved_approved_at, '1970-01-01')
    ) DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$recentApprovedUrec = $con->query("
    SELECT ud.document_type, ud.approved_at, ud.status,
           g.id AS group_id, g.name AS group_name, g.research_title,
           COALESCE(f.name, 'Not assigned') AS adviser_name
    FROM urec_documents ud
    JOIN groups g ON g.research_id = ud.research_id
    LEFT JOIN faculties f ON g.adviser_id = f.id
    WHERE ud.status = 'approved' AND ud.approved_at IS NOT NULL
    ORDER BY ud.approved_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Merge milestones + UREC into one flat list, sort by approved_at desc, take top 20
$historyItems = [];
foreach ($recentApprovedTitles as $t) {
    $historyItems[] = [
        'type'         => 'title',
        'group_name'   => $t['group_name'],
        'research_title'=> $t['research_title'],
        'adviser'      => $t['adviser_name'],
        'label'        => 'Title Approved',
        'icon'         => 'ri-bookmark-check-line',
        'approved_at'  => $t['title_approved_at'],
    ];
}
foreach ($recentApprovedMilestones as $m) {
    foreach ($milestoneLabels as $key => $meta) {
        $statusCol = $key . '_status';
        $dateCol   = $key . '_approved_at';
        if (($m[$statusCol] ?? '') === 'completed' && !empty($m[$dateCol])) {
            $historyItems[] = [
                'type'          => 'milestone',
                'group_name'    => $m['group_name'],
                'research_title'=> $m['research_title'],
                'adviser'       => $m['adviser_name'],
                'label'         => $meta['label'],
                'icon'          => $meta['icon'],
                'approved_at'   => $m[$dateCol],
            ];
        }
    }
}
foreach ($recentApprovedUrec as $u) {
    $label = $u['document_type'] === 'UREC Form' ? 'UREC Applied' : 'UREC Approved';
    $icon  = $u['document_type'] === 'UREC Form' ? 'ri-file-shield-line' : 'ri-file-shield-2-line';
    $historyItems[] = [
        'type'          => 'milestone',
        'group_name'    => $u['group_name'],
        'research_title'=> $u['research_title'],
        'adviser'       => $u['adviser_name'],
        'label'         => $label,
        'icon'          => $icon,
        'approved_at'   => $u['approved_at'],
    ];
}
usort($historyItems, fn($a, $b) => strtotime($b['approved_at']) - strtotime($a['approved_at']));
$historyItems = array_slice($historyItems, 0, 20);

$notificationsStmt = $con->prepare("SELECT id, title, message, priority, created_at, status FROM system_notifications WHERE (recipient_type = 'all' OR recipient_type = 'coordinators' OR (recipient_type = 'specific' AND recipient_id = :user_id)) AND status != 'deleted' ORDER BY created_at DESC LIMIT 10");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
$totalGroups   = $stats['total_groups'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/setup.css">
    <title>Monitoring Reports</title>
</head>
<body>
    <?php include("templates/aside_coordinator.html"); ?>
    <div id="exportToast" class="toast"></div>

    <main class="main-content">
        <div class="page-header">
            <div>
                <div class="page-title">Monitoring Reports</div>
                <div class="page-subtitle">Overview of research group activity, submissions, and approvals</div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-card-top"><span class="stat-label">Total Groups</span><div class="stat-icon-wrap"><i class="ri-team-line"></i></div></div>
                <div class="stat-number"><?= $stats['total_groups'] ?></div>
                <div class="stat-trend">Research groups registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top"><span class="stat-label">Total Students</span><div class="stat-icon-wrap"><i class="ri-user-line"></i></div></div>
                <div class="stat-number"><?= $stats['total_students'] ?></div>
                <div class="stat-trend">Enrolled across all groups</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top"><span class="stat-label">Total Advisors</span><div class="stat-icon-wrap"><i class="ri-user-star-line"></i></div></div>
                <div class="stat-number"><?= $stats['total_advisors'] ?></div>
                <div class="stat-trend">Faculty advisors assigned</div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head"><div class="section-head-left"><div class="section-icon"><i class="ri-pie-chart-line"></i></div><span class="section-title">Submission Status Overview</span></div></div>
            <div class="section-body">
                <div class="status-grid">
                    <div class="status-item approved"><i class="ri-checkbox-circle-line"></i><span class="status-count"><?= $statusData['approved'] ?></span><span class="status-label">Approved</span></div>
                    <div class="status-item pending"><i class="ri-time-line"></i><span class="status-count"><?= $statusData['pending'] ?></span><span class="status-label">Pending</span></div>
                    <div class="status-item rejected"><i class="ri-close-circle-line"></i><span class="status-count"><?= $statusData['rejected'] ?></span><span class="status-label">Rejected</span></div>
                </div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head"><div class="section-head-left"><div class="section-icon"><i class="ri-global-line"></i></div><span class="section-title">UN SDG Distribution</span></div></div>
            <div class="section-body">
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>SDG Name</th><th>Groups</th><th style="min-width:180px;">Distribution</th></tr></thead>
                        <tbody>
                            <?php foreach ($sdgData as $sdg): $pct = $totalGroups > 0 ? round(($sdg['group_count'] / $totalGroups) * 100, 1) : 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($sdg['sdg_name']) ?></td>
                                <td><strong><?= $sdg['group_count'] ?></strong></td>
                                <td><div class="pct-bar-wrap"><div class="pct-bar"><div class="pct-bar-fill" style="width:<?= $pct ?>%"></div></div><span class="pct-text"><?= $pct ?>%</span></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head"><div class="section-head-left"><div class="section-icon"><i class="ri-compass-3-line"></i></div><span class="section-title">Research Thrust Distribution</span></div></div>
            <div class="section-body">
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Research Thrust</th><th>Groups</th><th style="min-width:180px;">Distribution</th></tr></thead>
                        <tbody>
                            <?php foreach ($thrustData as $thrust): $pct = $totalGroups > 0 ? round(($thrust['group_count'] / $totalGroups) * 100, 1) : 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($thrust['thrust_name']) ?></td>
                                <td><strong><?= $thrust['group_count'] ?></strong></td>
                                <td><div class="pct-bar-wrap"><div class="pct-bar"><div class="pct-bar-fill" style="width:<?= $pct ?>%"></div></div><span class="pct-text"><?= $pct ?>%</span></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left"><div class="section-icon"><i class="ri-bookmark-line"></i></div><span class="section-title">Pending Title Approvals</span></div>
                <span class="pending-badge" id="titleBadge" <?= empty($pendingTitles) ? 'style="display:none"' : '' ?>><?= count($pendingTitles) ?> pending</span>
            </div>
            <div class="section-body">
                <?php if (!empty($pendingTitles)): ?>
                    <div class="pending-grid">
                        <?php foreach ($pendingTitles as $pt): ?>
                            <div class="pcard" data-group-id="<?= $pt['id'] ?>">
                                <div class="pcard-header">
                                    <div class="pcard-name"><i class="ri-team-line"></i> <?= htmlspecialchars($pt['name']) ?></div>
                                    <div class="card-menu-wrapper">
                                        <div class="card-menu-icon" onclick="toggleTitleMenu(event,this)"><i class="ri-more-2-fill"></i></div>
                                        <div class="card-menu-dropdown">
                                            <?php if (!empty($pt['title_proposal_file'])): ?>
                                                <a href="<?= htmlspecialchars($pt['title_proposal_file']) ?>" target="_blank" class="menu-item"><i class="ri-file-text-line"></i> View Proposal</a>
                                            <?php endif; ?>
                                            <button class="menu-item approve" onclick="approveTitleAction(<?= $pt['id'] ?>)"><i class="ri-check-line"></i> Approve Title</button>
                                            <button class="menu-item reject" onclick="rejectTitleAction(<?= $pt['id'] ?>)"><i class="ri-close-line"></i> Reject Title</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pcard-title"><?= htmlspecialchars($pt['research_title']) ?></div>
                                <div class="pcard-meta">
                                    <div class="pcard-meta-item"><i class="ri-user-star-line"></i> <?= htmlspecialchars($pt['advisor_name']) ?></div>
                                    <div class="pcard-meta-item"><i class="ri-user-line"></i> <?= htmlspecialchars($pt['leader_name'] ?? 'No leader') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pending"><i class="ri-checkbox-circle-line"></i><p>All titles are up to date</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left"><div class="section-icon"><i class="ri-folder-check-line"></i></div><span class="section-title">Pending Milestone Approvals</span></div>
                <span class="pending-badge" id="milestoneBadge" <?= empty($pendingMilestones) ? 'style="display:none"' : '' ?>><?= count($pendingMilestones) ?> pending</span>
            </div>
            <div class="section-body">
                <?php
                $milestoneTypes = [
                    ['key' => 'proposal',            'label' => 'Proposal Approved',  'icon' => 'ri-file-text-line'],
                    ['key' => 'urec_form',           'label' => 'UREC Applied',       'icon' => 'ri-file-shield-line'],
                    ['key' => 'urec_clearance',      'label' => 'UREC Approved',      'icon' => 'ri-file-shield-2-line'],
                    ['key' => 'final_defense',       'label' => 'Research Completed', 'icon' => 'ri-presentation-line'],
                    ['key' => 'hardbound_submitted', 'label' => 'Hardbound Submitted','icon' => 'ri-book-2-line'],
                    ['key' => 'applied_copyright',   'label' => 'Copyright Applied',  'icon' => 'ri-copyright-line'],
                    ['key' => 'research_presented',  'label' => 'Research Presented', 'icon' => 'ri-slideshow-3-line'],
                    ['key' => 'research_published',  'label' => 'Research Published', 'icon' => 'ri-newspaper-line'],
                    ['key' => 'copyright_approved',  'label' => 'Copyright Approved', 'icon' => 'ri-shield-check-line'],
                ];
                if (!empty($pendingMilestones)): ?>
                    <div class="pending-grid">
                        <?php foreach ($pendingMilestones as $pm):
                            $hasPending = false;
                            foreach ($milestoneTypes as $m) {
                                $fk = $m['key'] . '_file_path'; $sk = $m['key'] . '_status';
                                if (!empty($pm[$fk]) && ($pm[$sk] ?? '') === 'endorsed') { $hasPending = true; break; }
                            }
                            if (!$hasPending) continue;
                        ?>
                            <div class="pcard">
                                <div class="pcard-header"><div class="pcard-name"><i class="ri-team-line"></i> <?= htmlspecialchars($pm['name']) ?></div></div>
                                <div class="pcard-title"><?= htmlspecialchars($pm['research_title']) ?></div>
                                <div class="pcard-meta">
                                    <div class="pcard-meta-item"><i class="ri-user-star-line"></i> <?= htmlspecialchars($pm['advisor_name']) ?></div>
                                    <div class="pcard-meta-item"><i class="ri-user-line"></i> <?= htmlspecialchars($pm['leader_name'] ?? 'No leader') ?></div>
                                </div>
                                <?php foreach ($milestoneTypes as $m):
                                    $fk = $m['key'] . '_file_path';
                                    $nk = $m['key'] . '_original_filename';
                                    $dk = $m['key'] . '_uploaded_at';
                                    $sk = $m['key'] . '_status';
                                    $cs = $pm[$sk] ?? '';
                                    if (!empty($pm[$fk]) && $cs === 'endorsed'):
                                ?>
                                    <div class="ms-card" data-milestone="<?= $m['key'] ?>" data-group="<?= $pm['id'] ?>">
                                        <div class="ms-card-top">
                                            <div class="ms-name"><i class="<?= $m['icon'] ?>"></i> <?= $m['label'] ?></div>
                                            <span class="ms-badge">Endorsed — Awaiting Approval</span>
                                        </div>
                                        <div class="ms-meta"><i class="ri-file-line"></i> <?= htmlspecialchars($pm[$nk] ?? '') ?></div>
                                        <div class="ms-meta"><i class="ri-calendar-line"></i> Uploaded: <?= !empty($pm[$dk]) ? date("M d, Y • h:i A", strtotime($pm[$dk])) : '' ?></div>
                                        <?php
                                        $approvedAtKey = $m['key'] . '_approved_at';
                                        if (!empty($pm[$approvedAtKey])): ?>
                                            <div class="ms-meta" style="color:#155724;"><i class="ri-checkbox-circle-line"></i> Date Approved: <?= date("M d, Y", strtotime($pm[$approvedAtKey])) ?></div>
                                        <?php endif; ?>
                                        <div class="ms-actions">
                                            <a href="<?= htmlspecialchars($pm[$fk]) ?>" target="_blank" class="ms-btn view"><i class="ri-eye-line"></i> View</a>
                                            <button onclick="approveMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" class="ms-btn approve"><i class="ri-check-line"></i> Approve</button>
                                            <button onclick="rejectMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" class="ms-btn reject"><i class="ri-close-line"></i> Reject</button>
                                        </div>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pending"><i class="ri-checkbox-circle-line"></i><p>All milestones are up to date</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-history-line"></i></div>
                    <span class="section-title">Recent Approvals</span>
                </div>
                <?php if (!empty($historyItems)): ?>
                    <span class="history-count-badge"><?= count($historyItems) ?> records</span>
                <?php endif; ?>
            </div>
            <div class="section-body">
                <?php if (!empty($historyItems)): ?>
                    <div class="history-timeline">
                        <?php foreach ($historyItems as $item):
                            $isTitle = $item['type'] === 'title';
                            $timeAgo = '';
                            $ts = strtotime($item['approved_at']);
                            $diff = time() - $ts;
                            if ($diff < 60)                 $timeAgo = 'Just now';
                            elseif ($diff < 3600)           $timeAgo = floor($diff/60) . 'm ago';
                            elseif ($diff < 86400)          $timeAgo = floor($diff/3600) . 'h ago';
                            elseif ($diff < 86400*7)        $timeAgo = floor($diff/86400) . 'd ago';
                            else                            $timeAgo = date('M j, Y', $ts);
                        ?>
                            <div class="history-item <?= $isTitle ? 'history-item--title' : 'history-item--milestone' ?>">
                                <div class="history-icon-wrap">
                                    <i class="<?= $item['icon'] ?>"></i>
                                </div>
                                <div class="history-content">
                                    <div class="history-top">
                                        <span class="history-label <?= $isTitle ? 'hl-title' : 'hl-milestone' ?>">
                                            <?= htmlspecialchars($item['label']) ?>
                                        </span>
                                        <span class="history-time" title="<?= date('F j, Y g:i A', $ts) ?>">
                                            <i class="ri-time-line"></i> <?= $timeAgo ?>
                                        </span>
                                    </div>
                                    <div class="history-group"><?= htmlspecialchars($item['group_name']) ?></div>
                                    <?php if (!empty($item['research_title'])): ?>
                                        <div class="history-title-text"><?= htmlspecialchars($item['research_title']) ?></div>
                                    <?php endif; ?>
                                    <div class="history-adviser"><i class="ri-user-star-line"></i> <?= htmlspecialchars($item['adviser']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pending"><i class="ri-checkbox-circle-line"></i><p>No approvals recorded yet</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="export-section">
            <div class="section-head"><div class="section-head-left"><div class="section-icon"><i class="ri-download-line"></i></div><span class="section-title">Export Reports</span></div></div>
            <div class="export-buttons">
                <button class="btn-export" onclick="exportReport('status')"><i class="ri-file-text-line"></i> Status Report</button>
                <button class="btn-export" onclick="exportReport('sdg')"><i class="ri-global-line"></i> SDG Report</button>
                <button class="btn-export" onclick="exportReport('thrust')"><i class="ri-compass-3-line"></i> Thrust Report</button>
                <button class="btn-export" onclick="exportReport('full')"><i class="ri-file-download-line"></i> Full Report</button>
            </div>
        </div>

        <div class="space"></div>
    </main>

    <div class="comment-modal" id="commentModal">
        <div class="comment-modal-content">
            <h3 id="commentModalTitle">Add Comment</h3>
            <div class="modal-subtitle">This comment will be visible to the group.</div>
            <textarea id="commentText" placeholder="Enter your comment or reason (optional)..."></textarea>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeCommentModal()">Cancel</button>
                <button class="btn-submit" onclick="submitTitleAction()">Submit</button>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="milestoneCommentModal">
        <div class="comment-modal-content">
            <h3 id="milestoneCommentModalTitle">Add Comment</h3>
            <div class="modal-subtitle">This comment will be visible to the group.</div>
            <textarea id="milestoneCommentText" placeholder="Enter your comment or reason (optional)..."></textarea>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeMilestoneCommentModal()">Cancel</button>
                <button class="btn-submit" onclick="submitMilestoneAction()">Submit</button>
            </div>
        </div>
    </div>

    <script>
        let currentActionGroupId = null, currentActionType = null, currentMilestoneType = null;
        let lastTitleCount = <?= count($pendingTitles) ?>;
        let lastMilestoneCount = <?= count($pendingMilestones) ?>;

        function showToast(m, t = 'success') {
            const toast = document.getElementById('exportToast');
            toast.className = `toast ${t}`;
            toast.innerHTML = `<i class="${t === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i><span>${m}</span>`;
            toast.classList.add('show');
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toast.classList.remove('show'), 3500);
        }

        function toggleTitleMenu(e, el) {
            e.stopPropagation();
            document.querySelectorAll('.card-menu-dropdown').forEach(d => { if (d !== el.nextElementSibling) d.classList.remove('show'); });
            el.nextElementSibling.classList.toggle('show');
        }

        function approveTitleAction(gid) {
            currentActionGroupId = gid; currentActionType = 'approve';
            document.getElementById('commentModalTitle').textContent = 'Approve Title';
            document.getElementById('commentText').value = '';
            document.getElementById('commentText').placeholder = 'Add approval comment (optional)...';
            document.getElementById('commentModal').style.display = 'flex';
        }

        function rejectTitleAction(gid) {
            currentActionGroupId = gid; currentActionType = 'reject';
            document.getElementById('commentModalTitle').textContent = 'Reject Title';
            document.getElementById('commentText').value = '';
            document.getElementById('commentText').placeholder = 'Reason for rejection (optional)...';
            document.getElementById('commentModal').style.display = 'flex';
        }

        function closeCommentModal() {
            document.getElementById('commentModal').style.display = 'none';
            currentActionGroupId = null; currentActionType = null;
        }

        async function submitTitleAction() {
            if (!currentActionGroupId || !currentActionType) return;
            const comment = document.getElementById('commentText').value.trim();
            const action  = currentActionType === 'approve' ? 'approve_title' : 'reject_title';
            try {
                const fd = new FormData();
                fd.append('action', action); fd.append('group_id', currentActionGroupId); fd.append('comment', comment);
                const data = await (await fetch('report.php', { method: 'POST', body: fd })).json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeCommentModal();
                    const card = document.querySelector(`.pcard[data-group-id="${currentActionGroupId}"]`);
                    if (card) card.style.cssText = 'opacity:0;transform:scale(0.93);transition:all 0.3s ease;';
                    setTimeout(() => location.reload(), 700);
                } else showToast(data.message || 'Action failed', 'error');
            } catch { showToast('Network error. Please try again.', 'error'); }
        }

        function approveMilestoneAction(gid, mtype) {
            currentActionGroupId = gid; currentMilestoneType = mtype; currentActionType = 'approve';
            document.getElementById('milestoneCommentModalTitle').textContent = 'Approve Milestone';
            document.getElementById('milestoneCommentText').value = '';
            document.getElementById('milestoneCommentText').placeholder = 'Add approval comment (optional)...';
            document.getElementById('milestoneCommentModal').style.display = 'flex';
        }

        function rejectMilestoneAction(gid, mtype) {
            currentActionGroupId = gid; currentMilestoneType = mtype; currentActionType = 'reject';
            document.getElementById('milestoneCommentModalTitle').textContent = 'Reject Milestone';
            document.getElementById('milestoneCommentText').value = '';
            document.getElementById('milestoneCommentText').placeholder = 'Reason for rejection (optional)...';
            document.getElementById('milestoneCommentModal').style.display = 'flex';
        }

        function closeMilestoneCommentModal() {
            document.getElementById('milestoneCommentModal').style.display = 'none';
            currentActionGroupId = null; currentMilestoneType = null; currentActionType = null;
        }

        async function submitMilestoneAction() {
            if (!currentActionGroupId || !currentMilestoneType || !currentActionType) return;
            const comment = document.getElementById('milestoneCommentText').value.trim();
            const action  = currentActionType === 'approve' ? 'approve_milestone' : 'reject_milestone';
            try {
                const fd = new FormData();
                fd.append('action', action); fd.append('group_id', currentActionGroupId);
                fd.append('milestone_type', currentMilestoneType); fd.append('comment', comment);
                const data = await (await fetch('report.php', { method: 'POST', body: fd })).json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeMilestoneCommentModal();
                    const ms = document.querySelector(`.ms-card[data-milestone="${currentMilestoneType}"][data-group="${currentActionGroupId}"]`);
                    if (ms) ms.style.cssText = 'opacity:0;transform:scale(0.93);transition:all 0.3s ease;';
                    setTimeout(() => location.reload(), 700);
                } else showToast(data.message || 'Action failed', 'error');
            } catch { showToast('Network error. Please try again.', 'error'); }
        }

        async function exportReport(type) {
            try {
                const res = await fetch('php/export_report.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `type=${type}` });
                if (!res.ok) throw new Error('Server error ' + res.status);
                const url = window.URL.createObjectURL(await res.blob());
                const a = Object.assign(document.createElement('a'), { href: url, download: `${type}_report_${new Date().toISOString().split('T')[0]}.csv` });
                document.body.appendChild(a); a.click(); a.remove();
                window.URL.revokeObjectURL(url);
                showToast(`${({status:'Status',sdg:'SDG',thrust:'Thrust',full:'Full'}[type]??'Report')} report downloaded!`, 'success');
            } catch (e) { showToast('Export failed: ' + e.message, 'error'); }
        }

        document.addEventListener('click', e => {
            if (!e.target.closest('.card-menu-wrapper'))
                document.querySelectorAll('.card-menu-dropdown').forEach(d => d.classList.remove('show'));
        });

        setInterval(async () => {
            try {
                const fd = new FormData();
                fd.append('action', 'poll_counts');
                const data = await (await fetch('report.php', { method: 'POST', body: fd })).json();
                if (data.titles > lastTitleCount || data.milestones > lastMilestoneCount) {
                    lastTitleCount = data.titles; lastMilestoneCount = data.milestones;
                    location.reload();
                }
            } catch {}
        }, 15000);

        const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
    </script>
    <script src="js/session_monitor.js"></script>
</body>
</html>