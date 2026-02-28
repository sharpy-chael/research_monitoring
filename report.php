<?php
session_start();
include('connect.php');
include('php/get_setting.php');
include("check_session.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'approve_title' || $action === 'reject_title') {
        $groupId = $_POST['group_id'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!$groupId) {
            echo json_encode(['success' => false, 'message' => 'Group ID required']);
            exit;
        }

        $newStatus = $action === 'approve_title' ? 'approved' : 'rejected';
        $coordinatorId = $_SESSION['id'];

        try {
            if ($newStatus === 'approved') {
                $stmt = $con->prepare("UPDATE groups SET title_status = :status, title_approval_comment = :comment, title_approved_at = NOW(), title_approved_by = :coord_id WHERE id = :group_id");
                $stmt->execute(['status' => $newStatus, 'comment' => $comment, 'coord_id' => $coordinatorId, 'group_id' => $groupId]);
            } else {
                $stmt = $con->prepare("UPDATE groups SET title_status = :status, title_approval_comment = :comment WHERE id = :group_id");
                $stmt->execute(['status' => $newStatus, 'comment' => $comment, 'group_id' => $groupId]);
            }

            $message = $action === 'approve_title' ? 'Title approved successfully' : 'Title rejected';
            echo json_encode(['success' => true, 'message' => $message, 'new_status' => $newStatus]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'approve_milestone' || $action === 'reject_milestone') {
        $groupId = $_POST['group_id'] ?? null;
        $milestoneType = $_POST['milestone_type'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!$groupId || !$milestoneType) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $validMilestones = ['proposal', 'final_defense', 'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'];
        if (!in_array($milestoneType, $validMilestones)) {
            echo json_encode(['success' => false, 'message' => 'Invalid milestone type']);
            exit;
        }

        $newStatus = $action === 'approve_milestone' ? 'completed' : 'rejected';
        $statusColumn = $milestoneType . '_status';

        try {
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $groupId]);

            if ($checkStmt->rowCount() > 0) {
                $stmt = $con->prepare("UPDATE group_milestones SET {$statusColumn} = :status WHERE group_id = :group_id");
                $stmt->execute(['status' => $newStatus, 'group_id' => $groupId]);
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$statusColumn}) VALUES (:group_id, :status)");
                $stmt->execute(['group_id' => $groupId, 'status' => $newStatus]);
            }

            $message = $action === 'approve_milestone' ? 'Milestone approved successfully' : 'Milestone rejected';
            echo json_encode(['success' => true, 'message' => $message, 'new_status' => $newStatus]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$statsQuery = $con->query("
    SELECT 
        COUNT(DISTINCT g.id) as total_groups,
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT a.id) as total_advisors
    FROM groups g
    LEFT JOIN student s ON g.id = s.group_id
    LEFT JOIN advisor a ON g.adviser_id = a.id
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$statusQuery = $con->query("
    SELECT 
        SUM(CASE WHEN u.status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN u.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM uploads u
");
$statusData = $statusQuery->fetch(PDO::FETCH_ASSOC);

$sdgQuery = $con->query("
    SELECT 
        us.id as sdg_id,
        us.name as sdg_name,
        COUNT(DISTINCT gs.group_id) as group_count
    FROM un_sdgs us
    LEFT JOIN group_sdgs gs ON us.id = gs.sdg_id
    GROUP BY us.id, us.name
    HAVING COUNT(DISTINCT gs.group_id) > 0
    ORDER BY group_count DESC, us.name
");
$sdgData = $sdgQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedSdgQuery = $con->query("
    SELECT COUNT(DISTINCT g.id) as group_count
    FROM groups g
    LEFT JOIN group_sdgs gs ON g.id = gs.group_id
    WHERE gs.id IS NULL
");
$unassignedSdgCount = $unassignedSdgQuery->fetchColumn();

if ($unassignedSdgCount > 0) {
    $sdgData[] = [
        'sdg_id' => 0,
        'sdg_name' => 'Unassigned',
        'group_count' => $unassignedSdgCount
    ];
}

$thrustQuery = $con->query("
    SELECT 
        rt.id as thrust_id,
        rt.name as thrust_name,
        COUNT(DISTINCT gt.group_id) as group_count
    FROM research_thrusts rt
    LEFT JOIN group_thrusts gt ON rt.id = gt.thrust_id
    GROUP BY rt.id, rt.name
    HAVING COUNT(DISTINCT gt.group_id) > 0
    ORDER BY group_count DESC, rt.name
");
$thrustData = $thrustQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedThrustQuery = $con->query("
    SELECT COUNT(DISTINCT g.id) as group_count
    FROM groups g
    LEFT JOIN group_thrusts gt ON g.id = gt.group_id
    WHERE gt.id IS NULL
");
$unassignedThrustCount = $unassignedThrustQuery->fetchColumn();

if ($unassignedThrustCount > 0) {
    $thrustData[] = [
        'thrust_id' => 0,
        'thrust_name' => 'Unassigned',
        'group_count' => $unassignedThrustCount
    ];
}

$pendingTitlesQuery = $con->query("
    SELECT g.id, g.name, g.research_title, g.title_proposal_file, g.title_proposal_filename,
           g.adviser_id,
           COALESCE(a.name, 'Not assigned') as advisor_name,
           (SELECT s.name FROM student s WHERE s.group_id = g.id AND s.is_leader = TRUE LIMIT 1) as leader_name
    FROM groups g
    LEFT JOIN advisor a ON g.adviser_id = a.id
    WHERE g.title_status = 'pending_approval'
    ORDER BY g.name
");
$pendingTitles = $pendingTitlesQuery->fetchAll(PDO::FETCH_ASSOC);

$pendingMilestonesQuery = $con->query("
    SELECT g.id, g.name, g.research_title,
           g.proposal_file_path, g.proposal_original_filename, g.proposal_uploaded_at,
           g.final_defense_file_path, g.final_defense_original_filename, g.final_defense_uploaded_at,
           g.applied_copyright_file_path, g.applied_copyright_original_filename, g.applied_copyright_uploaded_at,
           g.research_presented_file_path, g.research_presented_original_filename, g.research_presented_uploaded_at,
           g.research_published_file_path, g.research_published_original_filename, g.research_published_uploaded_at,
           g.copyright_approved_file_path, g.copyright_approved_original_filename, g.copyright_approved_uploaded_at,
           gm.proposal_status, gm.final_defense_status, gm.applied_copyright_status,
           gm.research_presented_status, gm.research_published_status, gm.copyright_approved_status,
           COALESCE(a.name, 'Not assigned') as advisor_name,
           (SELECT s.name FROM student s WHERE s.group_id = g.id AND s.is_leader = TRUE LIMIT 1) as leader_name
    FROM groups g
    LEFT JOIN group_milestones gm ON g.id = gm.group_id
    LEFT JOIN advisor a ON g.adviser_id = a.id
    WHERE g.title_status = 'approved'
    AND (
        (g.proposal_file_path IS NOT NULL AND COALESCE(gm.proposal_status, 'pending') NOT IN ('completed', 'rejected'))
        OR (g.final_defense_file_path IS NOT NULL AND COALESCE(gm.final_defense_status, 'pending') NOT IN ('completed', 'rejected'))
        OR (g.applied_copyright_file_path IS NOT NULL AND COALESCE(gm.applied_copyright_status, 'pending') NOT IN ('completed', 'rejected'))
        OR (g.research_presented_file_path IS NOT NULL AND COALESCE(gm.research_presented_status, 'pending') NOT IN ('completed', 'rejected'))
        OR (g.research_published_file_path IS NOT NULL AND COALESCE(gm.research_published_status, 'pending') NOT IN ('completed', 'rejected'))
        OR (g.copyright_approved_file_path IS NOT NULL AND COALESCE(gm.copyright_approved_status, 'pending') NOT IN ('completed', 'rejected'))
    )
    ORDER BY g.name
");
$pendingMilestones = $pendingMilestonesQuery->fetchAll(PDO::FETCH_ASSOC);

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
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
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
    <style>
        .toast {
            position: fixed;
            top: 30px;
            right: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .18);
            z-index: 99999;
            opacity: 0;
            transform: translateY(-16px);
            transition: opacity .3s, transform .3s;
            pointer-events: none;
            max-width: 320px
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0)
        }

        .toast.success {
            background: linear-gradient(135deg, #38a169 0%, #276749 100%)
        }

        .toast.error {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%)
        }

        .toast i {
            font-size: 18px;
            flex-shrink: 0
        }

        .pending-badge {
            background: #ff9800;
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px
        }

        .pending-titles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            max-height: 600px;
            overflow-y: auto;
            padding: 2px
        }

        .pending-title-compact {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            transition: all .2s
        }

        .pending-title-compact:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
            border-color: #ccc
        }

        .compact-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 8px;
            margin-bottom: 8px
        }

        .compact-group-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1
        }

        .compact-group-name i {
            color: #007bff;
            font-size: 16px
        }

        .card-menu-wrapper {
            position: relative
        }

        .card-menu-icon {
            cursor: pointer;
            color: #666;
            font-size: 18px;
            padding: 4px;
            border-radius: 4px;
            transition: all .2s
        }

        .card-menu-icon:hover {
            background: #f0f0f0;
            color: #333
        }

        .card-menu-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            min-width: 160px;
            z-index: 1000;
            margin-top: 4px
        }

        .card-menu-dropdown.show {
            display: block
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #333;
            cursor: pointer;
            transition: background .2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            text-decoration: none
        }

        .menu-item:first-child {
            border-radius: 6px 6px 0 0
        }

        .menu-item:last-child {
            border-radius: 0 0 6px 6px
        }

        .menu-item:hover {
            background: #f8f9fa
        }

        .menu-item.approve {
            color: #28a745
        }

        .menu-item.approve:hover {
            background: #d4edda
        }

        .menu-item.reject {
            color: #dc3545
        }

        .menu-item.reject:hover {
            background: #f8d7da
        }

        .menu-item i {
            font-size: 14px
        }

        .compact-title {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        .compact-meta {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: #999;
            flex-wrap: wrap
        }

        .compact-meta span {
            display: flex;
            align-items: center;
            gap: 4px
        }

        .compact-meta i {
            font-size: 12px
        }

        .no-pending {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px
        }

        .no-pending i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: .5
        }

        .comment-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 10000;
            align-items: center;
            justify-content: center
        }

        .comment-modal-content {
            background: #fff;
            padding: 24px;
            border-radius: 10px;
            max-width: 450px;
            width: 90%
        }

        .comment-modal-content h3 {
            margin: 0 0 15px;
            font-size: 18px
        }

        .comment-modal-content textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical
        }

        .comment-modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end
        }

        .comment-modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px
        }

        .btn-cancel {
            background: #6c757d;
            color: #fff
        }

        .btn-cancel:hover {
            background: #5a6268
        }

        .btn-submit {
            background: #007bff;
            color: #fff
        }

        .btn-submit:hover {
            background: #0056b3
        }

        .milestone-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px
        }

        .milestone-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px
        }

        .milestone-title {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 6px
        }

        .milestone-title i {
            color: #17a2b8
        }

        .milestone-status {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600
        }

        .milestone-status.pending {
            background: #fff3cd;
            color: #856404
        }

        .milestone-status.completed {
            background: #d4edda;
            color: #155724
        }

        .milestone-status.rejected {
            background: #f8d7da;
            color: #721c24
        }

        .milestone-file-info {
            font-size: 12px;
            color: #666;
            margin-top: 4px
        }

        .milestone-file-info i {
            margin-right: 4px;
            color: #17a2b8
        }

        @media (max-width:768px) {
            .toast {
                top: 16px;
                left: 16px;
                right: 16px;
                max-width: none
            }

            .pending-titles-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <?php include("templates/aside_coordinator.html"); ?>

    <div id="exportToast" class="toast"></div>

    <main class="main-content">
        <h1 id="head"><i class="ri-file-chart-line"></i> Monitoring Reports</h1>

        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-team-line" style="color:#fff"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_groups'] ?></div>
                    <div class="stat-label">Total Groups</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line" style="color:#fff"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_students'] ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line" style="color:#fff"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_advisors'] ?></div>
                    <div class="stat-label">Total Advisors</div>
                </div>
            </div>
        </div>

        <div class="report-section">
            <h2><i class="ri-pie-chart-line"></i> Submission Status Overview</h2>
            <div class="status-grid">
                <div class="status-item approved">
                    <i class="ri-checkbox-circle-line"></i>
                    <span class="status-count"><?= $statusData['approved'] ?></span>
                    <span class="status-label">Approved</span>
                </div>
                <div class="status-item pending">
                    <i class="ri-time-line"></i>
                    <span class="status-count"><?= $statusData['pending'] ?></span>
                    <span class="status-label">Pending</span>
                </div>
                <div class="status-item rejected">
                    <i class="ri-close-circle-line"></i>
                    <span class="status-count"><?= $statusData['rejected'] ?></span>
                    <span class="status-label">Rejected</span>
                </div>
            </div>
        </div>

        <div class="report-section">
            <h2><i class="ri-global-line"></i> UN SDG Distribution</h2>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SDG Name</th>
                            <th>Groups Aligned</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGroups = $stats['total_groups'];
                        foreach ($sdgData as $sdg):
                            $percentage = $totalGroups > 0 ? round(($sdg['group_count'] / $totalGroups) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($sdg['sdg_name']) ?></td>
                                <td><strong><?= $sdg['group_count'] ?></strong></td>
                                <td><?= $percentage ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h2><i class="ri-compass-3-line"></i> Research Thrust Distribution</h2>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Research Thrust</th>
                            <th>Groups Aligned</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($thrustData as $thrust):
                            $percentage = $totalGroups > 0 ? round(($thrust['group_count'] / $totalGroups) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($thrust['thrust_name']) ?></td>
                                <td><strong><?= $thrust['group_count'] ?></strong></td>
                                <td><?= $percentage ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h2>
                <i class="ri-book-mark-line"></i>
                Pending Title Approvals
                <?php if (!empty($pendingTitles)): ?>
                    <span class="pending-badge"><?= count($pendingTitles) ?></span>
                <?php endif; ?>
            </h2>

            <?php if (!empty($pendingTitles)): ?>
                <div class="pending-titles-grid">
                    <?php foreach ($pendingTitles as $pt): ?>
                        <div class="pending-title-compact" data-group-id="<?= $pt['id'] ?>">
                            <div class="compact-header">
                                <div class="compact-group-name">
                                    <i class="ri-team-line"></i>
                                    <span><?= htmlspecialchars($pt['name']) ?></span>
                                </div>
                                <div class="card-menu-wrapper">
                                    <i class="ri-more-2-fill card-menu-icon" onclick="toggleTitleMenu(event,this)"></i>
                                    <div class="card-menu-dropdown">
                                        <?php if (!empty($pt['title_proposal_file'])): ?>
                                            <a href="<?= htmlspecialchars($pt['title_proposal_file']) ?>" target="_blank" class="menu-item">
                                                <i class="ri-file-text-line"></i> View Proposal
                                            </a>
                                        <?php endif; ?>
                                        <button class="menu-item approve" onclick="approveTitleAction(<?= $pt['id'] ?>)">
                                            <i class="ri-check-line"></i> Approve
                                        </button>
                                        <button class="menu-item reject" onclick="rejectTitleAction(<?= $pt['id'] ?>)">
                                            <i class="ri-close-line"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="compact-title"><?= htmlspecialchars($pt['research_title']) ?></div>
                            <div class="compact-meta">
                                <span><i class="ri-user-star-line"></i><?= htmlspecialchars($pt['advisor_name']) ?></span>
                                <span><i class="ri-user-line"></i><?= htmlspecialchars($pt['leader_name'] ?? 'No leader') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-pending">
                    <i class="ri-checkbox-circle-line"></i>
                    <p>No titles pending approval</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="report-section">
            <h2>
                <i class="ri-folder-check-line"></i>
                Pending Milestone Approvals
                <?php if (!empty($pendingMilestones)): ?>
                    <span class="pending-badge"><?= count($pendingMilestones) ?></span>
                <?php endif; ?>
            </h2>

            <?php if (!empty($pendingMilestones)): ?>
                <div class="pending-titles-grid">
                    <?php foreach ($pendingMilestones as $pm):
                        $milestones = [
                            ['key' => 'proposal', 'label' => 'Proposal', 'icon' => 'ri-file-text-line'],
                            ['key' => 'final_defense', 'label' => 'Final Defense', 'icon' => 'ri-presentation-line'],
                            ['key' => 'applied_copyright', 'label' => 'Applied Copyright', 'icon' => 'ri-copyright-line'],
                            ['key' => 'research_presented', 'label' => 'Research Presented', 'icon' => 'ri-slideshow-3-line'],
                            ['key' => 'research_published', 'label' => 'Research Published', 'icon' => 'ri-newspaper-line'],
                            ['key' => 'copyright_approved', 'label' => 'Copyright Approved', 'icon' => 'ri-shield-check-line'],
                        ];
                        $hasPending = false;
                        foreach ($milestones as $m) {
                            $fileKey = $m['key'] . '_file_path';
                            $statusKey = $m['key'] . '_status';
                            if (!empty($pm[$fileKey]) && !in_array($pm[$statusKey] ?? 'pending', ['completed', 'rejected'])) {
                                $hasPending = true;
                                break;
                            }
                        }
                        if (!$hasPending) continue;
                    ?>
                        <div class="pending-title-compact">
                            <div class="compact-header">
                                <div class="compact-group-name">
                                    <i class="ri-team-line"></i>
                                    <span><?= htmlspecialchars($pm['name']) ?></span>
                                </div>
                            </div>
                            <div class="compact-title"><?= htmlspecialchars($pm['research_title']) ?></div>
                            <div class="compact-meta" style="margin-bottom:10px;">
                                <span><i class="ri-user-star-line"></i><?= htmlspecialchars($pm['advisor_name']) ?></span>
                                <span><i class="ri-user-line"></i><?= htmlspecialchars($pm['leader_name'] ?? 'No leader') ?></span>
                            </div>

                            <?php foreach ($milestones as $m):
                                $fileKey = $m['key'] . '_file_path';
                                $filenameKey = $m['key'] . '_original_filename';
                                $dateKey = $m['key'] . '_uploaded_at';
                                $statusKey = $m['key'] . '_status';
                                $currentStatus = $pm[$statusKey] ?? 'pending';

                                if (!empty($pm[$fileKey]) && !in_array($currentStatus, ['completed', 'rejected'])):
                            ?>
                                    <div class="milestone-card" data-milestone="<?= $m['key'] ?>" data-group="<?= $pm['id'] ?>">
                                        <div class="milestone-card-header">
                                            <div class="milestone-title">
                                                <i class="<?= $m['icon'] ?>"></i>
                                                <?= $m['label'] ?>
                                            </div>
                                            <div>
                                                <span class="milestone-status pending">Pending Review</span>
                                            </div>
                                        </div>
                                        <div class="milestone-file-info">
                                            <i class="ri-file-line"></i><?= htmlspecialchars($pm[$filenameKey]) ?>
                                        </div>
                                        <div class="milestone-file-info">
                                            <i class="ri-calendar-line"></i><?= date("M d, Y • h:i A", strtotime($pm[$dateKey])) ?>
                                        </div>
                                        <div style="margin-top:8px;display:flex;gap:6px;">
                                            <a href="<?= htmlspecialchars($pm[$fileKey]) ?>" target="_blank" style="padding:4px 10px;background:#17a2b8;color:#fff;border-radius:4px;font-size:12px;text-decoration:none;">
                                                <i class="ri-eye-line"></i> View
                                            </a>
                                            <button onclick="approveMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" style="padding:4px 10px;background:#28a745;color:#fff;border:none;border-radius:4px;font-size:12px;cursor:pointer;">
                                                <i class="ri-check-line"></i> Approve
                                            </button>
                                            <button onclick="rejectMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" style="padding:4px 10px;background:#dc3545;color:#fff;border:none;border-radius:4px;font-size:12px;cursor:pointer;">
                                                <i class="ri-close-line"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                            <?php endif;
                            endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-pending">
                    <i class="ri-checkbox-circle-line"></i>
                    <p>No milestones pending approval</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="export-section">
            <h2><i class="ri-download-line"></i> Export Reports</h2>
            <div class="export-buttons">
                <button class="btn-export" onclick="exportReport('status')">
                    <i class="ri-file-text-line"></i> Status Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('sdg')">
                    <i class="ri-global-line"></i> SDG Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('thrust')">
                    <i class="ri-compass-3-line"></i> Thrust Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('full')">
                    <i class="ri-file-download-line"></i> Full Report (CSV)
                </button>
            </div>
        </div>
        <div class="space"></div>
    </main>

    <div class="comment-modal" id="commentModal">
        <div class="comment-modal-content">
            <h3 id="commentModalTitle">Add Comment</h3>
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
            <textarea id="milestoneCommentText" placeholder="Enter your comment or reason (optional)..."></textarea>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeMilestoneCommentModal()">Cancel</button>
                <button class="btn-submit" onclick="submitMilestoneAction()">Submit</button>
            </div>
        </div>
    </div>

    <script>
        let currentActionGroupId = null,
            currentActionType = null,
            currentMilestoneType = null;

        function showToast(m, t = 'success') {
            const toast = document.getElementById('exportToast'),
                icon = t === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line';
            toast.className = `toast ${t}`;
            toast.innerHTML = `<i class="${icon}"></i><span>${m}</span>`;
            toast.classList.add('show');
            clearTimeout(toast._hideTimer);
            toast._hideTimer = setTimeout(() => toast.classList.remove('show'), 3500)
        }

        function toggleTitleMenu(e, el) {
            e.stopPropagation();
            document.querySelectorAll('.card-menu-dropdown').forEach(d => {
                if (d !== el.nextElementSibling) d.classList.remove('show')
            });
            el.nextElementSibling.classList.toggle('show')
        }

        function approveTitleAction(gid) {
            currentActionGroupId = gid;
            currentActionType = 'approve';
            document.getElementById('commentModalTitle').textContent = 'Approve Title';
            document.getElementById('commentText').value = '';
            document.getElementById('commentText').placeholder = 'Add approval comment (optional)...';
            document.getElementById('commentModal').style.display = 'flex'
        }

        function rejectTitleAction(gid) {
            currentActionGroupId = gid;
            currentActionType = 'reject';
            document.getElementById('commentModalTitle').textContent = 'Reject Title';
            document.getElementById('commentText').value = '';
            document.getElementById('commentText').placeholder = 'Reason for rejection (optional)...';
            document.getElementById('commentModal').style.display = 'flex'
        }

        function closeCommentModal() {
            document.getElementById('commentModal').style.display = 'none';
            currentActionGroupId = null;
            currentActionType = null
        }
        async function submitTitleAction() {
            if (!currentActionGroupId || !currentActionType) return;
            const comment = document.getElementById('commentText').value.trim(),
                action = currentActionType === 'approve' ? 'approve_title' : 'reject_title';
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('group_id', currentActionGroupId);
                formData.append('comment', comment);
                const res = await fetch('report.php', {
                        method: 'POST',
                        body: formData
                    }),
                    data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeCommentModal();
                    const card = document.querySelector(`.pending-title-compact[data-group-id="${currentActionGroupId}"]`);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        card.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            const container = document.querySelector('.pending-titles-grid');
                            if (container && container.children.length === 0) {
                                setTimeout(() => location.reload(), 500)
                            } else {
                                const badge = document.querySelector('.pending-badge');
                                if (badge) badge.textContent = container.children.length
                            }
                        }, 300)
                    }
                } else {
                    showToast(data.message || 'Action failed', 'error')
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error')
            }
        }

        function approveMilestoneAction(gid, mtype) {
            currentActionGroupId = gid;
            currentMilestoneType = mtype;
            currentActionType = 'approve';
            document.getElementById('milestoneCommentModalTitle').textContent = 'Approve Milestone';
            document.getElementById('milestoneCommentText').value = '';
            document.getElementById('milestoneCommentText').placeholder = 'Add approval comment (optional)...';
            document.getElementById('milestoneCommentModal').style.display = 'flex'
        }

        function rejectMilestoneAction(gid, mtype) {
            currentActionGroupId = gid;
            currentMilestoneType = mtype;
            currentActionType = 'reject';
            document.getElementById('milestoneCommentModalTitle').textContent = 'Reject Milestone';
            document.getElementById('milestoneCommentText').value = '';
            document.getElementById('milestoneCommentText').placeholder = 'Reason for rejection (optional)...';
            document.getElementById('milestoneCommentModal').style.display = 'flex'
        }

        function closeMilestoneCommentModal() {
            document.getElementById('milestoneCommentModal').style.display = 'none';
            currentActionGroupId = null;
            currentMilestoneType = null;
            currentActionType = null
        }
        async function submitMilestoneAction() {
            if (!currentActionGroupId || !currentMilestoneType || !currentActionType) return;
            const comment = document.getElementById('milestoneCommentText').value.trim(),
                action = currentActionType === 'approve' ? 'approve_milestone' : 'reject_milestone';
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('group_id', currentActionGroupId);
                formData.append('milestone_type', currentMilestoneType);
                formData.append('comment', comment);
                const res = await fetch('report.php', {
                        method: 'POST',
                        body: formData
                    }),
                    data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeMilestoneCommentModal();
                    const card = document.querySelector(`.milestone-card[data-milestone="${currentMilestoneType}"][data-group="${currentActionGroupId}"]`);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        card.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            const container = card.closest('.pending-title-compact');
                            if (container && container.querySelectorAll('.milestone-card').length === 0) {
                                container.style.opacity = '0';
                                container.style.transform = 'scale(0.95)';
                                setTimeout(() => container.remove(), 300)
                            }
                        }, 300)
                    }
                } else {
                    showToast(data.message || 'Action failed', 'error')
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error')
            }
        }

        async function exportReport(type) {
            try {
                const response = await fetch('php/export_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `type=${type}`
                });
                if (!response.ok) throw new Error('Server returned ' + response.status);
                const blob = await response.blob(),
                    url = window.URL.createObjectURL(blob),
                    a = document.createElement('a');
                a.href = url;
                a.download = `${type}_report_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                const labels = {
                    status: 'Status',
                    sdg: 'SDG',
                    thrust: 'Thrust',
                    full: 'Full'
                };
                showToast(`${labels[type]??'Report'} report downloaded successfully!`, 'success')
            } catch (error) {
                showToast('Export failed: ' + error.message, 'error')
            }
        }
        document.addEventListener('click', e => {
            if (!e.target.closest('.card-menu-wrapper')) document.querySelectorAll('.card-menu-dropdown').forEach(d => d.classList.remove('show'))
        });

    const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
    </script>
    <script src="js/session_monitor.js"></script>
</body>
</html>