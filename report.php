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
        if (!$groupId) { echo json_encode(['success' => false, 'message' => 'Group ID required']); exit; }
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
            echo json_encode(['success' => true, 'message' => $action === 'approve_title' ? 'Title approved successfully' : 'Title rejected', 'new_status' => $newStatus]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action === 'approve_milestone' || $action === 'reject_milestone') {
        $groupId = $_POST['group_id'] ?? null;
        $milestoneType = $_POST['milestone_type'] ?? null;
        $comment = trim($_POST['comment'] ?? '');
        if (!$groupId || !$milestoneType) { echo json_encode(['success' => false, 'message' => 'Invalid parameters']); exit; }
        $validMilestones = ['proposal', 'final_defense', 'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'];
        if (!in_array($milestoneType, $validMilestones)) { echo json_encode(['success' => false, 'message' => 'Invalid milestone type']); exit; }
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
                    (g.proposal_file_path IS NOT NULL AND COALESCE(gm.proposal_status,'pending') NOT IN ('completed','rejected'))
                    OR (g.final_defense_file_path IS NOT NULL AND COALESCE(gm.final_defense_status,'pending') NOT IN ('completed','rejected'))
                    OR (g.applied_copyright_file_path IS NOT NULL AND COALESCE(gm.applied_copyright_status,'pending') NOT IN ('completed','rejected'))
                    OR (g.research_presented_file_path IS NOT NULL AND COALESCE(gm.research_presented_status,'pending') NOT IN ('completed','rejected'))
                    OR (g.research_published_file_path IS NOT NULL AND COALESCE(gm.research_published_status,'pending') NOT IN ('completed','rejected'))
                    OR (g.copyright_approved_file_path IS NOT NULL AND COALESCE(gm.copyright_approved_status,'pending') NOT IN ('completed','rejected'))
                )
            ")->fetchColumn();
            echo json_encode(['titles' => (int)$tc, 'milestones' => (int)$mc]);
        } catch (Exception $e) { echo json_encode(['titles' => 0, 'milestones' => 0]); }
        exit;
    }
}

if (!isset($_SESSION['submit'])) { header('Location: home.php'); exit; }

$statsQuery = $con->query("
    SELECT COUNT(DISTINCT g.id) as total_groups, COUNT(DISTINCT s.id) as total_students, COUNT(DISTINCT a.id) as total_advisors
    FROM groups g
    LEFT JOIN student s ON g.id = s.group_id
    LEFT JOIN advisor a ON g.adviser_id = a.id
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$statusQuery = $con->query("
    SELECT SUM(CASE WHEN u.status = 'approved' THEN 1 ELSE 0 END) as approved,
           SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending,
           SUM(CASE WHEN u.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM uploads u
");
$statusData = $statusQuery->fetch(PDO::FETCH_ASSOC);

$sdgQuery = $con->query("
    SELECT us.id as sdg_id, us.name as sdg_name, COUNT(DISTINCT gs.group_id) as group_count
    FROM un_sdgs us
    LEFT JOIN group_sdgs gs ON us.id = gs.sdg_id
    GROUP BY us.id, us.name
    HAVING COUNT(DISTINCT gs.group_id) > 0
    ORDER BY group_count DESC, us.name
");
$sdgData = $sdgQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedSdgCount = $con->query("SELECT COUNT(DISTINCT g.id) FROM groups g LEFT JOIN group_sdgs gs ON g.id = gs.group_id WHERE gs.id IS NULL")->fetchColumn();
if ($unassignedSdgCount > 0) $sdgData[] = ['sdg_id' => 0, 'sdg_name' => 'Unassigned', 'group_count' => $unassignedSdgCount];

$thrustQuery = $con->query("
    SELECT rt.id as thrust_id, rt.name as thrust_name, COUNT(DISTINCT gt.group_id) as group_count
    FROM research_thrusts rt
    LEFT JOIN group_thrusts gt ON rt.id = gt.thrust_id
    GROUP BY rt.id, rt.name
    HAVING COUNT(DISTINCT gt.group_id) > 0
    ORDER BY group_count DESC, rt.name
");
$thrustData = $thrustQuery->fetchAll(PDO::FETCH_ASSOC);

$unassignedThrustCount = $con->query("SELECT COUNT(DISTINCT g.id) FROM groups g LEFT JOIN group_thrusts gt ON g.id = gt.group_id WHERE gt.id IS NULL")->fetchColumn();
if ($unassignedThrustCount > 0) $thrustData[] = ['thrust_id' => 0, 'thrust_name' => 'Unassigned', 'group_count' => $unassignedThrustCount];

$pendingTitles = $con->query("
    SELECT g.id, g.name, g.research_title, g.title_proposal_file, g.title_proposal_filename, g.adviser_id,
           COALESCE(a.name, 'Not assigned') as advisor_name,
           (SELECT s.name FROM student s WHERE s.group_id = g.id AND s.is_leader = TRUE LIMIT 1) as leader_name
    FROM groups g
    LEFT JOIN advisor a ON g.adviser_id = a.id
    WHERE g.title_status = 'pending_approval'
    ORDER BY g.name
")->fetchAll(PDO::FETCH_ASSOC);

$pendingMilestones = $con->query("
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
        (g.proposal_file_path IS NOT NULL AND COALESCE(gm.proposal_status,'pending') NOT IN ('completed','rejected'))
        OR (g.final_defense_file_path IS NOT NULL AND COALESCE(gm.final_defense_status,'pending') NOT IN ('completed','rejected'))
        OR (g.applied_copyright_file_path IS NOT NULL AND COALESCE(gm.applied_copyright_status,'pending') NOT IN ('completed','rejected'))
        OR (g.research_presented_file_path IS NOT NULL AND COALESCE(gm.research_presented_status,'pending') NOT IN ('completed','rejected'))
        OR (g.research_published_file_path IS NOT NULL AND COALESCE(gm.research_published_status,'pending') NOT IN ('completed','rejected'))
        OR (g.copyright_approved_file_path IS NOT NULL AND COALESCE(gm.copyright_approved_status,'pending') NOT IN ('completed','rejected'))
    )
    ORDER BY g.name
")->fetchAll(PDO::FETCH_ASSOC);

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status FROM system_notifications
    WHERE (recipient_type = 'all' OR recipient_type = 'coordinators' OR (recipient_type = 'specific' AND recipient_id = :user_id))
    AND status != 'deleted' ORDER BY created_at DESC LIMIT 10
");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
$totalGroups = $stats['total_groups'];
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
                <div class="stat-card-top">
                    <span class="stat-label">Total Groups</span>
                    <div class="stat-icon-wrap"><i class="ri-team-line"></i></div>
                </div>
                <div class="stat-number"><?= $stats['total_groups'] ?></div>
                <div class="stat-trend">Research groups registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-label">Total Students</span>
                    <div class="stat-icon-wrap"><i class="ri-user-line"></i></div>
                </div>
                <div class="stat-number"><?= $stats['total_students'] ?></div>
                <div class="stat-trend">Enrolled across all groups</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-label">Total Advisors</span>
                    <div class="stat-icon-wrap"><i class="ri-user-star-line"></i></div>
                </div>
                <div class="stat-number"><?= $stats['total_advisors'] ?></div>
                <div class="stat-trend">Faculty advisors assigned</div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-pie-chart-line"></i></div>
                    <span class="section-title">Submission Status Overview</span>
                </div>
            </div>
            <div class="section-body">
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
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-global-line"></i></div>
                    <span class="section-title">UN SDG Distribution</span>
                </div>
            </div>
            <div class="section-body">
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SDG Name</th>
                                <th>Groups</th>
                                <th style="min-width:180px;">Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sdgData as $sdg):
                                $pct = $totalGroups > 0 ? round(($sdg['group_count'] / $totalGroups) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($sdg['sdg_name']) ?></td>
                                <td><strong><?= $sdg['group_count'] ?></strong></td>
                                <td>
                                    <div class="pct-bar-wrap">
                                        <div class="pct-bar">
                                            <div class="pct-bar-fill" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="pct-text"><?= $pct ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-compass-3-line"></i></div>
                    <span class="section-title">Research Thrust Distribution</span>
                </div>
            </div>
            <div class="section-body">
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Research Thrust</th>
                                <th>Groups</th>
                                <th style="min-width:180px;">Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($thrustData as $thrust):
                                $pct = $totalGroups > 0 ? round(($thrust['group_count'] / $totalGroups) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($thrust['thrust_name']) ?></td>
                                <td><strong><?= $thrust['group_count'] ?></strong></td>
                                <td>
                                    <div class="pct-bar-wrap">
                                        <div class="pct-bar">
                                            <div class="pct-bar-fill" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="pct-text"><?= $pct ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-bookmark-line"></i></div>
                    <span class="section-title">Pending Title Approvals</span>
                </div>
                <span class="pending-badge" id="titleBadge" <?= empty($pendingTitles) ? 'style="display:none"' : '' ?>><?= count($pendingTitles) ?> pending</span>
            </div>
            <div class="section-body">
                <?php if (!empty($pendingTitles)): ?>
                    <div class="pending-grid">
                        <?php foreach ($pendingTitles as $pt): ?>
                            <div class="pcard" data-group-id="<?= $pt['id'] ?>">
                                <div class="pcard-header">
                                    <div class="pcard-name">
                                        <i class="ri-team-line"></i>
                                        <?= htmlspecialchars($pt['name']) ?>
                                    </div>
                                    <div class="card-menu-wrapper">
                                        <div class="card-menu-icon" onclick="toggleTitleMenu(event,this)">
                                            <i class="ri-more-2-fill"></i>
                                        </div>
                                        <div class="card-menu-dropdown">
                                            <?php if (!empty($pt['title_proposal_file'])): ?>
                                                <a href="<?= htmlspecialchars($pt['title_proposal_file']) ?>" target="_blank" class="menu-item">
                                                    <i class="ri-file-text-line"></i> View Proposal
                                                </a>
                                            <?php endif; ?>
                                            <button class="menu-item approve" onclick="approveTitleAction(<?= $pt['id'] ?>)">
                                                <i class="ri-check-line"></i> Approve Title
                                            </button>
                                            <button class="menu-item reject" onclick="rejectTitleAction(<?= $pt['id'] ?>)">
                                                <i class="ri-close-line"></i> Reject Title
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pcard-title"><?= htmlspecialchars($pt['research_title']) ?></div>
                                <div class="pcard-meta">
                                    <div class="pcard-meta-item">
                                        <i class="ri-user-star-line"></i>
                                        <?= htmlspecialchars($pt['advisor_name']) ?>
                                    </div>
                                    <div class="pcard-meta-item">
                                        <i class="ri-user-line"></i>
                                        <?= htmlspecialchars($pt['leader_name'] ?? 'No leader') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pending">
                        <i class="ri-checkbox-circle-line"></i>
                        <p>All titles are up to date</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-block">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-folder-check-line"></i></div>
                    <span class="section-title">Pending Milestone Approvals</span>
                </div>
                <span class="pending-badge" id="milestoneBadge" <?= empty($pendingMilestones) ? 'style="display:none"' : '' ?>><?= count($pendingMilestones) ?> pending</span>
            </div>
            <div class="section-body">
                <?php
                $milestoneTypes = [
                    ['key' => 'proposal',           'label' => 'Proposal',          'icon' => 'ri-file-text-line'],
                    ['key' => 'final_defense',      'label' => 'Final Defense',     'icon' => 'ri-presentation-line'],
                    ['key' => 'applied_copyright',  'label' => 'Applied Copyright', 'icon' => 'ri-copyright-line'],
                    ['key' => 'research_presented', 'label' => 'Presented',         'icon' => 'ri-slideshow-3-line'],
                    ['key' => 'research_published', 'label' => 'Published',         'icon' => 'ri-newspaper-line'],
                    ['key' => 'copyright_approved', 'label' => 'Copyright OK',      'icon' => 'ri-shield-check-line'],
                ];
                if (!empty($pendingMilestones)): ?>
                    <div class="pending-grid">
                        <?php foreach ($pendingMilestones as $pm):
                            $hasPending = false;
                            foreach ($milestoneTypes as $m) {
                                $fk = $m['key'] . '_file_path'; $sk = $m['key'] . '_status';
                                if (!empty($pm[$fk]) && !in_array($pm[$sk] ?? 'pending', ['completed','rejected'])) { $hasPending = true; break; }
                            }
                            if (!$hasPending) continue;
                        ?>
                            <div class="pcard">
                                <div class="pcard-header">
                                    <div class="pcard-name">
                                        <i class="ri-team-line"></i>
                                        <?= htmlspecialchars($pm['name']) ?>
                                    </div>
                                </div>
                                <div class="pcard-title"><?= htmlspecialchars($pm['research_title']) ?></div>
                                <div class="pcard-meta">
                                    <div class="pcard-meta-item">
                                        <i class="ri-user-star-line"></i>
                                        <?= htmlspecialchars($pm['advisor_name']) ?>
                                    </div>
                                    <div class="pcard-meta-item">
                                        <i class="ri-user-line"></i>
                                        <?= htmlspecialchars($pm['leader_name'] ?? 'No leader') ?>
                                    </div>
                                </div>
                                <?php foreach ($milestoneTypes as $m):
                                    $fk = $m['key'] . '_file_path';
                                    $nk = $m['key'] . '_original_filename';
                                    $dk = $m['key'] . '_uploaded_at';
                                    $sk = $m['key'] . '_status';
                                    $cs = $pm[$sk] ?? 'pending';
                                    if (!empty($pm[$fk]) && !in_array($cs, ['completed','rejected'])):
                                ?>
                                    <div class="ms-card" data-milestone="<?= $m['key'] ?>" data-group="<?= $pm['id'] ?>">
                                        <div class="ms-card-top">
                                            <div class="ms-name">
                                                <i class="<?= $m['icon'] ?>"></i>
                                                <?= $m['label'] ?>
                                            </div>
                                            <span class="ms-badge">Pending Review</span>
                                        </div>
                                        <div class="ms-meta">
                                            <i class="ri-file-line"></i>
                                            <?= htmlspecialchars($pm[$nk]) ?>
                                        </div>
                                        <div class="ms-meta">
                                            <i class="ri-calendar-line"></i>
                                            <?= date("M d, Y • h:i A", strtotime($pm[$dk])) ?>
                                        </div>
                                        <div class="ms-actions">
                                            <a href="<?= htmlspecialchars($pm[$fk]) ?>" target="_blank" class="ms-btn view">
                                                <i class="ri-eye-line"></i> View
                                            </a>
                                            <button onclick="approveMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" class="ms-btn approve">
                                                <i class="ri-check-line"></i> Approve
                                            </button>
                                            <button onclick="rejectMilestoneAction(<?= $pm['id'] ?>, '<?= $m['key'] ?>')" class="ms-btn reject">
                                                <i class="ri-close-line"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pending">
                        <i class="ri-checkbox-circle-line"></i>
                        <p>All milestones are up to date</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="export-section">
            <div class="section-head">
                <div class="section-head-left">
                    <div class="section-icon"><i class="ri-download-line"></i></div>
                    <span class="section-title">Export Reports</span>
                </div>
            </div>
            <div class="export-buttons">
                <button class="btn-export" onclick="exportReport('status')">
                    <i class="ri-file-text-line"></i> Status Report
                </button>
                <button class="btn-export" onclick="exportReport('sdg')">
                    <i class="ri-global-line"></i> SDG Report
                </button>
                <button class="btn-export" onclick="exportReport('thrust')">
                    <i class="ri-compass-3-line"></i> Thrust Report
                </button>
                <button class="btn-export" onclick="exportReport('full')">
                    <i class="ri-file-download-line"></i> Full Report
                </button>
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
            const action = currentActionType === 'approve' ? 'approve_title' : 'reject_title';
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
            const action = currentActionType === 'approve' ? 'approve_milestone' : 'reject_milestone';
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
                    lastTitleCount = data.titles;
                    lastMilestoneCount = data.milestones;
                    location.reload();
                }
            } catch {}
        }, 15000);

        const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
    </script>
    <script src="js/session_monitor.js"></script>
</body>
</html>