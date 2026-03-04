<?php
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'milestone_action') {
    header('Content-Type: application/json');
    $sessionUserId = $_SESSION['id'];
    $group_id = $_POST['group_id'] ?? null;
    $milestone_type = $_POST['milestone_type'] ?? '';
    $status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    $valid_types = [
        'proposal', 'urec_form', 'urec_clearance', 'final_defense',
        'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'
    ];
    $valid_statuses = ['completed', 'rejected'];

    if (!in_array($milestone_type, $valid_types) || !in_array($status, $valid_statuses) || !$group_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    try {
        if ($milestone_type === 'urec_form' || $milestone_type === 'urec_clearance') {
            $docType = ($milestone_type === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
            $urecStatus = ($status === 'completed') ? 'approved' : 'rejected';
            $stmt = $con->prepare("
                UPDATE urec_documents SET status = :status, comment = :comment
                WHERE group_id = :group_id AND document_type = :doc_type
            ");
            $stmt->execute([
                'status' => $urecStatus,
                'comment' => $comment,
                'group_id' => $group_id,
                'doc_type' => $docType
            ]);
        } else {
            $milestoneColumn = $milestone_type . '_status';
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $group_id]);
            if ($checkStmt->rowCount() > 0) {
                $stmt = $con->prepare("UPDATE group_milestones SET {$milestoneColumn} = :status, updated_at = NOW() WHERE group_id = :group_id");
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$milestoneColumn}, created_at, updated_at) VALUES (:group_id, :status, NOW(), NOW())");
            }
            $stmt->execute(['group_id' => $group_id, 'status' => $status]);

            if ($comment) {
                $commentCol = $milestone_type . '_comment';
                $colCheckStmt = $con->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'group_milestones' AND column_name = :col");
                $colCheckStmt->execute(['col' => $commentCol]);
                if ($colCheckStmt->rowCount() > 0) {
                    $con->prepare("UPDATE group_milestones SET {$commentCol} = :comment WHERE group_id = :group_id")
                        ->execute(['comment' => $comment, 'group_id' => $group_id]);
                }
            }
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_milestone') {
    header('Content-Type: application/json');
    $sessionUserId = $_SESSION['id'];
    $advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
    $advisorStmt->execute(['id' => $sessionUserId]);
    $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
    $advisorId = $advisorData['advisor_id'] ?? $sessionUserId;
    $milestone_type = $_POST['milestone_type'] ?? '';
    $group_id = $_POST['group_id'] ?? null;
    $valid_types = ['title', 'title_proposal'];
    if (!in_array($milestone_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid milestone type']);
        exit;
    }
    try {
        if ($milestone_type === 'title') {
            $title = trim($_POST['title'] ?? '');
            if (!$title) {
                echo json_encode(['success' => false, 'message' => 'Title cannot be empty']);
                exit;
            }
            $stmt = $con->prepare("UPDATE groups SET research_title = :title, title_status = 'pending_approval', title_submitted_at = NOW() WHERE id = :group_id");
            $stmt->execute(['title' => $title, 'group_id' => $group_id]);
            echo json_encode(['success' => true, 'message' => 'Title submitted for coordinator approval']);
            exit;
        }
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            exit;
        }
        $file = $_FILES['file'];
        $maxUploadSizeMB = getSettingInt($con, 'max_upload_size', 10);
        $maxUploadSizeBytes = $maxUploadSizeMB * 1024 * 1024;
        $allowedMimes = ['application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. PDF only']);
            exit;
        }
        if ($file['size'] > $maxUploadSizeBytes) {
            echo json_encode(['success' => false, 'message' => "File size must be less than {$maxUploadSizeMB}MB"]);
            exit;
        }
        $originalFilename = basename($file['name']);
        $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFilename;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit;
        }
        $stmt = $con->prepare("
            UPDATE groups
            SET title_proposal_file = :file_path,
                title_proposal_filename = :original_filename
            WHERE id = :group_id
        ");
        $stmt->execute(['file_path' => $filePath, 'original_filename' => $originalFilename, 'group_id' => $group_id]);
        echo json_encode(['success' => true, 'message' => 'Title proposal uploaded successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_group') {
    header('Content-Type: application/json');
    $groupId = $_POST['group_id'] ?? null;
    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'Group ID required']);
        exit;
    }
    try {
        $con->beginTransaction();
        $con->prepare("DELETE FROM group_milestones WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("DELETE FROM urec_documents WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("DELETE FROM group_sdgs WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("DELETE FROM group_thrusts WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("UPDATE student SET group_id = NULL, is_leader = NULL WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("DELETE FROM groups WHERE id = :group_id")->execute(['group_id' => $groupId]);
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'poll_status') {
    header('Content-Type: application/json');
    $sessionUserId = $_SESSION['id'];
    $aStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
    $aStmt->execute(['id' => $sessionUserId]);
    $aData = $aStmt->fetch(PDO::FETCH_ASSOC);
    $aId = $aData['advisor_id'] ?? $sessionUserId;
    $gStmt = $con->prepare("SELECT g.id, g.title_status, gm.proposal_status, gm.final_defense_status, gm.applied_copyright_status, gm.research_presented_status, gm.research_published_status, gm.copyright_approved_status FROM groups g LEFT JOIN group_milestones gm ON g.id = gm.group_id WHERE g.advisor_id = :aid");
    $gStmt->execute(['aid' => $aId]);
    $rows = $gStmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $r) {
        $uStmt = $con->prepare("SELECT document_type, status FROM urec_documents WHERE group_id = :gid ORDER BY uploaded_at DESC");
        $uStmt->execute(['gid' => $r['id']]);
        $uDocs = $uStmt->fetchAll(PDO::FETCH_ASSOC);
        $uMap = [];
        foreach ($uDocs as $ud) if (!isset($uMap[$ud['document_type']])) $uMap[$ud['document_type']] = $ud['status'];
        $result[$r['id']] = [
            'title_status' => $r['title_status'] ?? 'missing',
            'proposal_status' => $r['proposal_status'] ?? 'missing',
            'final_defense_status' => $r['final_defense_status'] ?? 'missing',
            'applied_copyright_status' => $r['applied_copyright_status'] ?? 'missing',
            'research_presented_status' => $r['research_presented_status'] ?? 'missing',
            'research_published_status' => $r['research_published_status'] ?? 'missing',
            'copyright_approved_status' => $r['copyright_approved_status'] ?? 'missing',
            'urec_form_status' => $uMap['UREC Form'] ?? 'missing',
            'urec_clearance_status' => $uMap['UREC Clearance'] ?? 'missing',
        ];
    }
    echo json_encode($result);
    exit;
}

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
        </html><?php
        exit;
    }
}

include('check_session.php');
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$sessionUserId = $_SESSION['id'];
$advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
$advisorStmt->execute(['id' => $sessionUserId]);
$advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
$advisorId = $advisorData['advisor_id'] ?? $sessionUserId;

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
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$groupsStmt = $con->prepare("
    SELECT g.id as group_id, g.name as group_name
    FROM groups g
    WHERE g.advisor_id = :advisor_id
    ORDER BY g.name
");
$groupsStmt->execute(['advisor_id' => $advisorId]);
$assignedGroups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

$groups = [];
foreach ($assignedGroups as $g) {
    $groupId = $g['group_id'];

    $leaderStmt = $con->prepare("
        SELECT s.id, s.full_name as name, s.school_id, g.research_title, g.title_status
        FROM student s
        LEFT JOIN groups g ON s.group_id = g.id
        WHERE s.group_id = :group_id AND s.is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['group_id' => $groupId]);
    $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);

    $memberStmt = $con->prepare("
        SELECT id, full_name as name
        FROM student
        WHERE group_id = :group_id
        AND (is_leader IS NULL OR is_leader = FALSE)
        ORDER BY full_name
    ");
    $memberStmt->execute(['group_id' => $groupId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

    $urecDocsStmt = $con->prepare("
        SELECT id, document_type, file_path, original_filename, status, comment, uploaded_at
        FROM urec_documents
        WHERE group_id = :group_id
        ORDER BY
            CASE document_type WHEN 'UREC Form' THEN 1 WHEN 'UREC Clearance' THEN 2 END,
            uploaded_at DESC
    ");
    $urecDocsStmt->execute(['group_id' => $groupId]);
    $allUrecDocs = $urecDocsStmt->fetchAll(PDO::FETCH_ASSOC);
    $urecDocsMap = [];
    foreach ($allUrecDocs as $doc) {
        if (!isset($urecDocsMap[$doc['document_type']])) $urecDocsMap[$doc['document_type']] = $doc;
    }
    $urecDocs = array_values($urecDocsMap);

    $groupFilesStmt = $con->prepare("
        SELECT title_proposal_file, title_proposal_filename,
               proposal_file_path, proposal_original_filename, proposal_uploaded_at,
               final_defense_file_path, final_defense_original_filename, final_defense_uploaded_at,
               applied_copyright_file_path, applied_copyright_original_filename, applied_copyright_uploaded_at,
               research_presented_file_path, research_presented_original_filename, research_presented_uploaded_at,
               research_published_file_path, research_published_original_filename, research_published_uploaded_at,
               copyright_approved_file_path, copyright_approved_original_filename, copyright_approved_uploaded_at
        FROM groups
        WHERE id = :group_id
    ");
    $groupFilesStmt->execute(['group_id' => $groupId]);
    $groupFiles = $groupFilesStmt->fetch(PDO::FETCH_ASSOC);

    $uploads  = [];
    $progress = 0;

    if ($leader) {
        $uploadsStmt = $con->prepare("
            SELECT upload_id, task_name, file_path, original_filename, uploaded_at, status, comment
            FROM uploads
            WHERE school_id = :school_id
            ORDER BY uploaded_at DESC
        ");
        $uploadsStmt->execute(['school_id' => $leader['school_id']]);
        $allUploads = $uploadsStmt->fetchAll(PDO::FETCH_ASSOC);

        $uploadMap = [];
        foreach ($allUploads as $upload) {
            if (!isset($uploadMap[$upload['task_name']])) $uploadMap[$upload['task_name']] = $upload;
        }
        $uploads = array_values($uploadMap);

        $approvedCount = 0;

        foreach ($uploadMap as $upload) {
            if ($upload['status'] === 'approved') {
                $approvedCount++;
                break;
            }
        }

        $milestoneCountStmt = $con->prepare("
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
        $milestoneCountStmt->execute(['group_id' => $groupId]);
        $groupMilestones = $milestoneCountStmt->fetch(PDO::FETCH_ASSOC);

        if ($groupMilestones) {
            if (($groupMilestones['title_status']              ?? '') === 'approved')  $approvedCount++;
            if (($groupMilestones['proposal_status']           ?? '') === 'completed') $approvedCount++;
            if (($groupMilestones['final_defense_status']      ?? '') === 'completed') $approvedCount++;
            if (($groupMilestones['applied_copyright_status']  ?? '') === 'completed') $approvedCount++;
            if (($groupMilestones['research_presented_status'] ?? '') === 'completed') $approvedCount++;
            if (($groupMilestones['research_published_status'] ?? '') === 'completed') $approvedCount++;
            if (($groupMilestones['copyright_approved_status'] ?? '') === 'completed') $approvedCount++;
        }

        foreach ($urecDocs as $doc) {
            if ($doc['status'] === 'approved') $approvedCount++;
        }

        $progress = round(($approvedCount / 10) * 100);
    }

    $milestoneStatusStmt = $con->prepare("
        SELECT proposal_status, final_defense_status, applied_copyright_status,
               research_presented_status, research_published_status, copyright_approved_status
        FROM group_milestones WHERE group_id = :group_id
    ");
    $milestoneStatusStmt->execute(['group_id' => $groupId]);
    $milestoneStatuses = $milestoneStatusStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $resTitle = $leader['research_title'] ?? null;
    if (!$resTitle) {
        $grpTitleStmt = $con->prepare("SELECT research_title, title_status FROM groups WHERE id = :id");
        $grpTitleStmt->execute(['id' => $groupId]);
        $grpTitleRow = $grpTitleStmt->fetch(PDO::FETCH_ASSOC);
        $resTitle    = $grpTitleRow['research_title'] ?? null;
        $titleStatus = $grpTitleRow['title_status']   ?? 'missing';
    } else {
        $titleStatus = $leader['title_status'] ?? 'missing';
    }

    $groups[] = [
        'group_id'         => $groupId,
        'group_name'       => $g['group_name'],
        'leader'           => $leader,
        'members'          => $members,
        'uploads'          => $uploads,
        'urec_docs'        => $urecDocs,
        'group_files'      => $groupFiles,
        'progress'         => $progress,
        'res_title'        => $resTitle,
        'title_status'     => $titleStatus,
        'milestone_statuses' => $milestoneStatuses,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/advisor.css">
    <link rel="stylesheet" href="css/notifications.css">
    <title>My Advisees</title>
</head>

<body>
    <?php include("templates/aside_advisor.html"); ?>
    <main class="main-content">
        <h2 id="head">My Advisees</h2>

        <div class="my-groups-container">
            <div class="groups-header-wrapper">
                <h2>
                    <i class="ri-team-line"></i>
                    My Groups
                    <?php if (!empty($groups)): ?>
                        <span class="groups-count"><?= count($groups) ?></span>
                    <?php endif; ?>
                </h2>
                <button class="create-group-btn" onclick="openCreateGroupModal()">
                    <i class="ri-add-circle-line"></i>
                    Add New Title
                </button>
            </div>

            <?php if (!empty($groups)): ?>
                <div class="group-cards-wrapper">
                    <?php foreach ($groups as $grp): ?>
                        <div class="group-card">
                            <div class="group-header">
                                <div class="group-title-wrapper">
                                    <div class="group-title">
                                        <i class="ri-folder-3-line"></i>
                                        <?= htmlspecialchars($grp['group_name']) ?>
                                    </div>
                                    <div class="progress-circle-wrapper">
                                        <div class="circular-progress">
                                            <svg width="50" height="50">
                                                <circle class="bg-circle" cx="25" cy="25" r="20"></circle>
                                                <circle class="progress-circle" cx="25" cy="25" r="20"
                                                    stroke-dasharray="<?= 2 * 3.14159 * 20 ?>"
                                                    stroke-dashoffset="<?= 2 * 3.14159 * 20 * (1 - $grp['progress'] / 100) ?>">
                                                </circle>
                                            </svg>
                                            <div class="progress-text"><?= $grp['progress'] ?>%</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <button class="delete-group-btn" onclick="deleteGroup(<?= $grp['group_id'] ?>, '<?= htmlspecialchars($grp['group_name'], ENT_QUOTES) ?>')" title="Delete Group">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                    <i class="ri-arrow-right-s-line expand-icon" onclick="toggleGroupDetails(this)"></i>
                                </div>
                            </div>

                            <div class="title-section" style="margin-bottom:10px;padding:12px;border:1px solid #ddd;border-radius:6px;background:#f8f9fa;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div>
                                        <strong><i class="ri-book-mark-line"></i> Research Title:</strong>
                                        <?php if (!empty($grp['res_title'])): ?>
                                            <p style="margin:5px 0 0;font-size:14px;"><?= htmlspecialchars($grp['res_title']) ?></p>
                                            <?php if ($grp['title_status'] === 'approved'): ?>
                                                <span class="status-badge status-approved">Approved</span>
                                            <?php elseif ($grp['title_status'] === 'pending_approval'): ?>
                                                <span class="status-badge status-pending_approval">Pending Approval</span>
                                            <?php elseif ($grp['title_status'] === 'rejected'): ?>
                                                <span class="status-badge status-rejected">Rejected</span>
                                            <?php else: ?>
                                                <span class="status-badge status-approved">Set</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#999;font-size:14px;">Not set yet</span>
                                            <span class="status-badge status-missing">Missing</span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="upload-milestone-btn" style="margin:0;" onclick="openTitleModal(<?= $grp['group_id'] ?>, '<?= htmlspecialchars($grp['res_title'] ?? '', ENT_QUOTES) ?>')">
                                        <i class="ri-edit-line"></i> <?= !empty($grp['res_title']) ? 'Edit' : 'Set' ?> Title
                                    </button>
                                </div>

                                <?php if (!empty($grp['res_title']) && $grp['title_status'] === 'pending_approval'): ?>
                                    <div style="margin-top:15px;padding:12px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:4px;">
                                        <p style="margin:0 0 8px;color:#856404;font-size:13px;"><i class="ri-time-line"></i> <strong>Title pending coordinator approval</strong></p>
                                        <?php
                                        $hasProposal = !empty($grp['group_files']['title_proposal_file']);
                                        if (!$hasProposal): ?>
                                            <p style="margin:0 0 10px;color:#856404;font-size:12px;">Upload Title Proposal to complete the submission.</p>
                                            <button class="upload-milestone-btn" style="margin:0;" onclick="openMilestoneUploadModal('title_proposal', 'Title Proposal', <?= $grp['group_id'] ?>, 'Title proposal document')">
                                                <i class="ri-upload-line"></i> Upload Title Proposal
                                            </button>
                                        <?php else: ?>
                                            <p style="margin:0;color:#856404;font-size:12px;"><i class="ri-checkbox-circle-line"></i> Title Proposal uploaded</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="group-details">
                                <?php if ($grp['leader']): ?>
                                    <div class="leader-section">
                                        <strong><i class="ri-star-fill"></i> Group Leader:</strong>
                                        <span class="leader-name"><?= htmlspecialchars($grp['leader']['name']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="leader-section" style="background:#fff3cd;border-left:4px solid #ffc107;padding:10px;margin-bottom:15px;">
                                        <i class="ri-alert-line" style="color:#856404;"></i>
                                        <span style="color:#856404;">No leader assigned yet</span>
                                        <button onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" style="padding:5px 10px;background:#ffc107;color:#000;border:none;border-radius:3px;cursor:pointer;font-size:12px;">
                                            <i class="ri-user-add-line"></i> Add Members &amp; Set Leader
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="members-sdg-grid">
                                    <div class="members-column">
                                        <div class="column-header">
                                            <h4>
                                                <i class="ri-group-line"></i>
                                                Members
                                                <span class="member-count"><?= count($grp['members']) ?></span>
                                            </h4>
                                            <button id="iconz" onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" class="add-member-btn">
                                                <i class="ri-user-add-line"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($grp['members'])): ?>
                                            <div class="members-list-vertical">
                                                <?php foreach ($grp['members'] as $member): ?>
                                                    <div class="member-item-compact">
                                                        <span class="member-left">
                                                            <i class="ri-user-3-line"></i>
                                                            <?= htmlspecialchars($member['name']) ?>
                                                        </span>
                                                        <button style="color:#fff !important;" id="iconz" class="remove-member-btn" title="Remove member"
                                                            onclick="removeMember(<?= $member['id'] ?>, <?= $grp['group_id'] ?>, this)">
                                                            <i style="color:#fff !important;" class="ri-user-unfollow-line"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-items-msg">No members added yet</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="sdg-column">
                                        <?php
                                        $groupSdgStmt = $con->prepare("
                                            SELECT us.id, us.name FROM un_sdgs us
                                            JOIN group_sdgs gs ON us.id = gs.sdg_id
                                            WHERE gs.group_id = :group_id ORDER BY us.name
                                        ");
                                        $groupSdgStmt->execute(['group_id' => $grp['group_id']]);
                                        $assignedSdgs = $groupSdgStmt->fetchAll(PDO::FETCH_ASSOC);

                                        $groupThrustStmt = $con->prepare("
                                            SELECT rt.id, rt.name FROM research_thrusts rt
                                            JOIN group_thrusts gt ON rt.id = gt.thrust_id
                                            WHERE gt.group_id = :group_id ORDER BY rt.name
                                        ");
                                        $groupThrustStmt->execute(['group_id' => $grp['group_id']]);
                                        $assignedThrusts = $groupThrustStmt->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <div class="column-header">
                                            <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                            <button style="color:#ffffff;" id="iconz" class="assign-btn-compact" onclick="openAssignModal('sdg', <?= $grp['group_id'] ?>)">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($assignedSdgs)): ?>
                                            <div class="tags-list-vertical">
                                                <?php foreach ($assignedSdgs as $sdg): ?>
                                                    <span class="tag-compact">
                                                        <?= htmlspecialchars($sdg['name']) ?>
                                                        <i class="ri-close-line" onclick="removeAssignment('sdg', <?= $grp['group_id'] ?>, <?= $sdg['id'] ?>, this)"></i>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-items-msg">No SDGs assigned</div>
                                        <?php endif; ?>

                                        <div class="column-header" style="margin-top:20px;">
                                            <h4><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                            <button id="iconz" class="assign-btn-compact" onclick="openAssignModal('thrust', <?= $grp['group_id'] ?>)">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($assignedThrusts)): ?>
                                            <div class="tags-list-vertical">
                                                <?php foreach ($assignedThrusts as $thrust): ?>
                                                    <span class="tag-compact thrust">
                                                        <?= htmlspecialchars($thrust['name']) ?>
                                                        <i class="ri-close-line" onclick="removeAssignment('thrust', <?= $grp['group_id'] ?>, <?= $thrust['id'] ?>, this)"></i>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-items-msg">No thrusts assigned</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($grp['title_status'] === 'approved'): ?>
                                    <div class="uploads-section" style="margin-top:20px;">
                                        <h4><i class="ri-folder-upload-line"></i> Research Milestone Documents</h4>
                                        <p style="font-size:13px;color:#6c757d;margin:0 0 12px;"><i class="ri-information-line"></i> Students upload milestone documents. Review and approve or reject each one below.</p>
                                        <?php
                                        $milestoneTypes = [
                                            ['key' => 'proposal',           'label' => 'Proposal',              'icon' => 'ri-file-text-line',     'src' => 'groups'],
                                            ['key' => 'urec_form',          'label' => 'UREC Processing',       'icon' => 'ri-file-shield-line',   'src' => 'urec'],
                                            ['key' => 'urec_clearance',     'label' => 'UREC Clearance',        'icon' => 'ri-file-shield-2-line', 'src' => 'urec'],
                                            ['key' => 'final_defense',      'label' => 'Final Defense',         'icon' => 'ri-presentation-line',  'src' => 'groups'],
                                            ['key' => 'applied_copyright',  'label' => 'Applied for Copyright', 'icon' => 'ri-copyright-line',     'src' => 'groups'],
                                            ['key' => 'research_presented', 'label' => 'Research Presented',    'icon' => 'ri-slideshow-3-line',   'src' => 'groups'],
                                            ['key' => 'research_published', 'label' => 'Research Published',    'icon' => 'ri-newspaper-line',     'src' => 'groups'],
                                            ['key' => 'copyright_approved', 'label' => 'Copyright Approved',    'icon' => 'ri-shield-check-line',  'src' => 'groups'],
                                        ];
                                        $evidenceLabels = [
                                            'proposal'           => 'Rating sheets (graded)',
                                            'urec_form'          => 'Received letter of intent',
                                            'urec_clearance'     => 'Copy of the clearance',
                                            'final_defense'      => 'Final defense rating sheet (graded)',
                                            'applied_copyright'  => 'Certificate of copyright with copyright number',
                                            'research_presented' => 'Letter of acceptance and certificate of recognition',
                                            'research_published' => 'Letter of acceptance, copy of published article, and DOI link (if possible)',
                                            'copyright_approved' => 'Certificate',
                                        ];
                                        $stepNum = 1;
                                        foreach ($milestoneTypes as $m):
                                            $hasFile = false;
                                            $fileName = '';
                                            $fileDate = '';
                                            $filePath = '';
                                            $docStatus = 'missing';
                                            $docComment = '';

                                            if ($m['src'] === 'urec') {
                                                $docType = ($m['key'] === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
                                                foreach ($grp['urec_docs'] as $doc) {
                                                    if ($doc['document_type'] === $docType) {
                                                        $hasFile = true;
                                                        $fileName = $doc['original_filename'];
                                                        $fileDate = date("M d, Y • h:i A", strtotime($doc['uploaded_at']));
                                                        $filePath = $doc['file_path'];
                                                        $docStatus = $doc['status'];
                                                        $docComment = $doc['comment'] ?? '';
                                                        break;
                                                    }
                                                }
                                            } else {
                                                $fk = $m['key'] . '_file_path';
                                                $nk = $m['key'] . '_original_filename';
                                                $dk = $m['key'] . '_uploaded_at';
                                                if (!empty($grp['group_files'][$fk])) {
                                                    $hasFile = true;
                                                    $fileName = $grp['group_files'][$nk];
                                                    $fileDate = date("M d, Y • h:i A", strtotime($grp['group_files'][$dk]));
                                                    $filePath = $grp['group_files'][$fk];
                                                    $statusKey = $m['key'] . '_status';
                                                    $rawStatus = $grp['milestone_statuses'][$statusKey] ?? 'pending';
                                                    $docStatus = $rawStatus ?: 'pending';
                                                }
                                            }

                                            $evidenceLabel = $evidenceLabels[$m['key']] ?? '';

                                            $statusBadgeClass = 'status-missing';
                                            $statusBadgeText  = 'Not Uploaded';
                                            if ($hasFile) {
                                                if ($docStatus === 'completed' || $docStatus === 'approved') { $statusBadgeClass = 'status-approved'; $statusBadgeText = 'Approved'; }
                                                elseif ($docStatus === 'rejected') { $statusBadgeClass = 'status-rejected'; $statusBadgeText = 'Rejected'; }
                                                else { $statusBadgeClass = 'status-pending_approval'; $statusBadgeText = 'Pending Review'; }
                                            }
                                        ?>
                                            <div class="milestone-upload-card">
                                                <div class="milestone-header">
                                                    <div class="milestone-title-status">
                                                        <span class="milestone-step-num <?= ($docStatus === 'completed' || $docStatus === 'approved') ? 'done' : '' ?>"><?= $stepNum++ ?></span>
                                                        <div>
                                                            <strong><i class="<?= $m['icon'] ?>"></i> <?= $m['label'] ?></strong>
                                                            <small style="color:#666;font-weight:normal;"><?= $evidenceLabel ?></small>
                                                        </div>
                                                        <span class="status-badge <?= $statusBadgeClass ?>"><?= $statusBadgeText ?></span>
                                                    </div>
                                                    <?php if ($hasFile && $docStatus !== 'completed' && $docStatus !== 'approved'): ?>
                                                        <button class="milestone-upload-btn" onclick="openMilestoneActionModal(<?= $grp['group_id'] ?>, '<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($docComment, ENT_QUOTES) ?>')">
                                                            <i class="ri-checkbox-circle-line"></i> Review
                                                        </button>
                                                    <?php elseif ($hasFile && ($docStatus === 'completed' || $docStatus === 'approved')): ?>
                                                        <button class="milestone-upload-btn" style="background:#6c757d;" onclick="openMilestoneActionModal(<?= $grp['group_id'] ?>, '<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($docComment, ENT_QUOTES) ?>')">
                                                            <i class="ri-edit-line"></i> Re-review
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="milestone-upload-btn" style="background:#adb5bd;cursor:not-allowed;" disabled>
                                                            <i class="ri-time-line"></i> Awaiting Upload
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($hasFile): ?>
                                                    <div class="milestone-file-details">
                                                        <p><i class="ri-file-line"></i> <?= htmlspecialchars($fileName) ?></p>
                                                        <p><?= $fileDate ?></p>
                                                        <?php if ($docComment): ?>
                                                            <p style="color:#856404;font-size:12px;"><i class="ri-chat-3-line"></i> <?= htmlspecialchars($docComment) ?></p>
                                                        <?php endif; ?>
                                                        <button class="preview-btn" onclick="openPreview('<?= htmlspecialchars($filePath, ENT_QUOTES) ?>', '<?= htmlspecialchars($fileName, ENT_QUOTES) ?>')">
                                                            <i class="ri-eye-line"></i> Preview
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="milestone-file-details" style="color:#adb5bd;font-style:italic;font-size:13px;">
                                                        <i class="ri-upload-cloud-line"></i> Waiting for student to upload this document.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="uploads-section" style="margin-top:20px;">
                                        <div style="padding:30px 20px;text-align:center;background:#f8f9fa;border:2px dashed #dee2e6;border-radius:8px;">
                                            <i class="ri-lock-line" style="font-size:48px;color:#adb5bd;margin-bottom:12px;"></i>
                                            <h4 style="margin:0 0 8px;color:#495057;">Research Milestones Locked</h4>
                                            <p style="margin:0;color:#6c757d;font-size:14px;">Research title must be approved by coordinator before students can upload milestone documents.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="uploads-section" style="margin-top:20px;">
                                    <h4>
                                        <i class="ri-file-upload-line"></i>
                                        Uploaded Manuscript
                                        <span style="font-size:.8em;color:#999;font-weight:normal;">(Latest upload only)</span>
                                    </h4>
                                    <?php if (!empty($grp['uploads'])): ?>
                                        <?php foreach ($grp['uploads'] as $upload):
                                            $statusClass = '';
                                            $statusText = 'Pending';
                                            if ($upload['status'] === 'approved') { $statusClass = 'approved'; $statusText = 'Approved'; }
                                            elseif ($upload['status'] === 'rejected') { $statusClass = 'rejected'; $statusText = 'Rejected'; }
                                        ?>
                                            <div class="upload-card <?= $statusClass ?>" data-upload-id="<?= $upload['upload_id'] ?>">
                                                <div class="upload-card-header">
                                                    <div class="upload-header-left">
                                                        <i class="ri-file-3-line file-icon"></i>
                                                        <span class="file-title">
                                                            <?= htmlspecialchars($upload['task_name']) ?>
                                                            <span class="status-badge status-<?= strtolower($statusText) ?>"><?= $statusText ?></span>
                                                        </span>
                                                    </div>
                                                    <div class="menu-wrapper">
                                                        <i class="ri-more-2-fill menu-toggle" onclick="toggleMenu(event, this)"></i>
                                                        <div class="menu-dropdown">
                                                            <?php if ($upload['status'] !== 'approved'): ?>
                                                                <button class="approve-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'approved')">
                                                                    <i class="ri-check-line"></i> Approve
                                                                </button>
                                                                <button class="reject-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'rejected')">
                                                                    <i class="ri-close-line"></i> Reject
                                                                </button>
                                                            <?php else: ?>
                                                                <div style="padding:8px 12px;color:#28a745;font-size:13px;border-bottom:1px solid #e9ecef;">
                                                                    <i class="ri-lock-line"></i> Approved - Locked
                                                                </div>
                                                            <?php endif; ?>
                                                            <button class="comment-btn" onclick="openCommentModal(<?= $upload['upload_id'] ?>, '<?= htmlspecialchars($upload['task_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($upload['comment'] ?? '', ENT_QUOTES) ?>')">
                                                                <i class="ri-chat-3-line"></i> <?= !empty($upload['comment']) ? 'Edit Comment' : 'Add Comment' ?>
                                                            </button>
                                                            <a href="<?= htmlspecialchars($upload['file_path']) ?>" download>
                                                                <i class="ri-download-line"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="upload-card-body">
                                                    <p class="file-filename"><?= htmlspecialchars($upload['original_filename']) ?></p>
                                                    <p class="file-date"><?= date("M d, Y • h:i A", strtotime($upload['uploaded_at'])) ?></p>
                                                    <?php if (!empty($upload['comment'])): ?>
                                                        <div class="file-comment">
                                                            <strong>Your Comment:</strong><br>
                                                            <?= htmlspecialchars($upload['comment']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-uploads-msg">
                                            <i class="ri-folder-open-line"></i> No uploaded files yet
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-groups-message">
                    <i class="ri-folder-unknow-line"></i>
                    <h3>No Groups Created Yet</h3>
                    <p>Click "Create New Group" to start managing your advisees.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="height:50px;" class="space"></div>
    </main>

    <div class="comment-modal" id="createGroupModal">
        <div class="comment-modal-content">
            <h3>Create New Group</h3>
            <input type="text" id="newGroupName" placeholder="Enter group name (e.g., Group5-BSIT)" style="width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:4px;">
            <input type="text" id="newGroupTitle" placeholder="Research title (optional)" style="width:100%;padding:10px;margin:5px 0;border:1px solid #ddd;border-radius:4px;">
            <p style="font-size:12px;color:#666;margin:4px 0 10px;">You may set a research title now or later.</p>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeCreateGroupModal()">Cancel</button>
                <button class="btn-submit" onclick="submitCreateGroup()">Create Group</button>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="addMembersModal">
        <div class="comment-modal-content" style="max-width:520px;width:95%;">
            <h3 id="addMembersTitle">Add Members to Group</h3>

            <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Leader (optional)</label>
            <div class="autocomplete-wrapper" id="leaderWrapper">
                <input type="text" id="leaderSearchInput" placeholder="Type student ID or name..."
                    autocomplete="off"
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;"
                    oninput="handleLeaderSearch(this.value)">
                <div class="autocomplete-list" id="leaderSuggestions" style="display:none;"></div>
            </div>
            <div id="leaderSelectedBox" class="selected-students-box" style="margin-bottom:12px;"></div>
            <input type="hidden" id="leaderSchoolId" value="">

            <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Members</label>
            <div class="autocomplete-wrapper" id="membersWrapper">
                <input type="text" id="memberSearchInput" placeholder="Type student ID or name..."
                    autocomplete="off"
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;"
                    oninput="handleMemberSearch(this.value)">
                <div class="autocomplete-list" id="memberSuggestions" style="display:none;"></div>
            </div>
            <div id="membersSelectedBox" class="selected-students-box" style="margin-bottom:12px;"></div>
            <input type="hidden" id="memberSchoolIds" value="">

            <p style="font-size:12px;color:#666;margin:4px 0 10px;">Leader is automatically included in the group.</p>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeAddMembersModal()">Cancel</button>
                <button class="btn-submit" onclick="submitAddMembers()">Add Members</button>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="commentModal">
        <div class="comment-modal-content">
            <h3 id="commentModalTitle">Add Comment</h3>
            <textarea id="commentText" placeholder="Enter your comment here..."></textarea>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeCommentModal()">Cancel</button>
                <button class="btn-submit" onclick="submitComment()">Submit</button>
            </div>
        </div>
    </div>

    <div id="documentPreviewModal" class="preview-modal">
        <div class="preview-modal-content">
            <div class="preview-header">
                <h3 id="previewTitle">Document Preview</h3>
                <div class="preview-actions">
                    <a id="downloadLinkBtn" href="#" download class="download-link-btn">
                        <i class="ri-download-line"></i> Download
                    </a>
                    <button class="preview-close" onclick="closePreviewModal()">×</button>
                </div>
            </div>
            <div id="previewContent" class="preview-content">
                <div class="preview-loading">
                    <i class="ri-loader-4-line" style="font-size:48px;animation:spin 1s linear infinite;"></i>
                    <p>Loading preview...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="milestoneUploadModal">
        <div class="comment-modal-content">
            <h3 id="milestoneUploadTitle">Upload Document</h3>
            <p id="milestoneEvidenceLabel" style="font-size:13px;color:#555;margin:0 0 10px;"><strong>Required:</strong> <span id="evidenceText"></span></p>
            <input type="file" id="milestoneFileInput" accept=".pdf" style="margin:15px 0;width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
            <p style="font-size:12px;color:#999;margin:5px 0;">PDF format only (Max <?= getSettingInt($con, 'max_upload_size', 10) ?>MB)</p>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeMilestoneUploadModal()">Cancel</button>
                <button class="btn-submit" onclick="submitMilestoneUpload()">Upload</button>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="advisorTitleModal">
        <div class="comment-modal-content">
            <h3>Set Research Title</h3>
            <input type="text" id="advisorTitleInput" placeholder="Enter research title" style="width:100%;padding:10px;margin:15px 0;border:1px solid #ddd;border-radius:4px;">
            <p style="font-size:12px;color:#856404;margin:5px 0 10px;"><i class="ri-information-line"></i> Title will be submitted for coordinator approval.</p>
            <div class="comment-modal-buttons">
                <button class="btn-cancel" onclick="closeAdvisorTitleModal()">Cancel</button>
                <button class="btn-submit" onclick="submitAdvisorTitle()">Submit Title</button>
            </div>
        </div>
    </div>

    <div class="comment-modal" id="milestoneActionModal" style="display:none;">
        <div class="comment-modal-content" style="max-width:480px;width:95%;">
            <h3 id="milestoneActionTitle">Review Milestone</h3>
            <p style="font-size:13px;color:#555;margin:0 0 12px;">Add a comment (required when rejecting):</p>
            <textarea id="milestoneActionComment" placeholder="Enter comment or rejection reason..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;min-height:90px;resize:vertical;box-sizing:border-box;font-family:inherit;font-size:14px;"></textarea>
            <div class="comment-modal-buttons" style="margin-top:14px;gap:8px;">
                <button class="btn-cancel" onclick="closeMilestoneActionModal()">Cancel</button>
                <button class="btn-submit" style="background:#dc3545;" onclick="submitMilestoneAction('rejected')"><i class="ri-close-circle-line"></i> Reject</button>
                <button class="btn-submit" onclick="submitMilestoneAction('completed')"><i class="ri-checkbox-circle-line"></i> Approve</button>
            </div>
        </div>
    </div>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">×</span>
            <h3 id="assignModalTitle">Assign</h3>
            <div id="assignCheckboxes" style="max-height:300px;overflow-y:auto;padding:10px;"></div>
            <button onclick="submitAssignment()" style="margin-top:15px;padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;">
                Assign Selected
            </button>
        </div>
    </div>

    <script src="js/timeout.js"></script>
    <script src="js/advisees.js"></script>
    <script src="js/notifications.js"></script>
    <script>
        async function removeMember(studentId, groupId, btn) {
            const row = btn.closest('.member-item-compact');
            const nameEl = row.querySelector('.member-left');
            const name = nameEl ? nameEl.textContent.trim() : 'this member';
            const confirmed = await showConfirmToast(`Remove ${name} from group?`, 'Remove', true);
            if (!confirmed) return;
            try {
                const res = await fetch('php/manage_coordinator.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove_member', student_id: studentId, group_id: groupId })
                });
                const data = await res.json();
                if (data.success) {
                    row.remove();
                    showToast('Member removed successfully', 'success');
                    const countEl = row.closest('.members-column')?.querySelector('.member-count');
                    if (countEl) countEl.textContent = parseInt(countEl.textContent || 0) - 1;
                } else {
                    showToast(data.message || 'Failed to remove member', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        }

        async function deleteGroup(groupId, groupName) {
            const confirmed = await showConfirmToast(`Delete group "<strong>${groupName}</strong>"?<br><small style="color:#888;">This will remove all members, uploads, and milestones. This cannot be undone.</small>`, 'Delete', true);
            if (!confirmed) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete_group');
                formData.append('group_id', groupId);
                const res = await fetch('advisees.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) { showToast('Group deleted successfully', 'success'); setTimeout(() => location.reload(), 800); }
                else { showToast(data.message || 'Failed to delete group', 'error'); }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        }

        (function() {
            const snapshot = {};
            <?php foreach ($groups as $grp):
                $ms = $grp['milestone_statuses'];
                $urecFormSnap = 'missing';
                $urecClearanceSnap = 'missing';
                foreach ($grp['urec_docs'] as $ud) {
                    if ($ud['document_type'] === 'UREC Form') $urecFormSnap = $ud['status'];
                    if ($ud['document_type'] === 'UREC Clearance') $urecClearanceSnap = $ud['status'];
                }
            ?>
            snapshot[<?= $grp['group_id'] ?>] = {
                title_status: '<?= $grp['title_status'] ?>',
                proposal_status: '<?= $ms['proposal_status'] ?? 'missing' ?>',
                final_defense_status: '<?= $ms['final_defense_status'] ?? 'missing' ?>',
                applied_copyright_status: '<?= $ms['applied_copyright_status'] ?? 'missing' ?>',
                research_presented_status: '<?= $ms['research_presented_status'] ?? 'missing' ?>',
                research_published_status: '<?= $ms['research_published_status'] ?? 'missing' ?>',
                copyright_approved_status: '<?= $ms['copyright_approved_status'] ?? 'missing' ?>',
                urec_form_status: '<?= $urecFormSnap ?>',
                urec_clearance_status: '<?= $urecClearanceSnap ?>',
            };
            <?php endforeach; ?>
            setInterval(async () => {
                try {
                    const fd = new FormData();
                    fd.append('action', 'poll_status');
                    const data = await (await fetch('advisees.php', { method: 'POST', body: fd })).json();
                    for (const gid of Object.keys(snapshot)) {
                        const curr = data[gid];
                        if (!curr) continue;
                        for (const key of Object.keys(snapshot[gid])) {
                            if (curr[key] !== undefined && curr[key] !== snapshot[gid][key]) {
                                location.reload();
                                return;
                            }
                        }
                    }
                } catch {}
            }, 10000);
        })();

        const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
    </script>
    <script src="js/session_monitor.js"></script>
</body>
</html>