<?php
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'milestone_action') {
    header('Content-Type: application/json');
    $group_id       = $_POST['group_id'] ?? null;
    $milestone_type = $_POST['milestone_type'] ?? '';
    $status         = $_POST['status'] ?? '';
    $comment        = trim($_POST['comment'] ?? '');

    $valid_types = [
        'proposal', 'urec_form', 'urec_clearance', 'final_defense',
        'hardbound_submitted', 'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'
    ];
    $valid_statuses = ['endorsed', 'rejected'];

    if (!in_array($milestone_type, $valid_types) || !in_array($status, $valid_statuses) || !$group_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    try {
        if ($milestone_type === 'urec_form' || $milestone_type === 'urec_clearance') {
            $docType    = ($milestone_type === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
            $urecStatus = ($status === 'endorsed') ? 'endorsed' : 'rejected';
            $groupResearchStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :group_id");
            $groupResearchStmt->execute(['group_id' => $group_id]);
            $groupResearch = $groupResearchStmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $con->prepare("UPDATE urec_documents SET status = :status, comment = :comment WHERE research_id = :research_id AND document_type = :doc_type");
            $stmt->execute(['status' => $urecStatus, 'comment' => $comment, 'research_id' => $groupResearch['research_id'], 'doc_type' => $docType]);
        } else {
            $milestoneColumn = $milestone_type . '_status';
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $group_id]);
            if ($checkStmt->rowCount() > 0) {
                $stmt = $con->prepare("UPDATE group_milestones SET {$milestoneColumn} = :status WHERE group_id = :group_id");
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$milestoneColumn}) VALUES (:group_id, :status)");
            }
            $stmt->execute(['group_id' => $group_id, 'status' => $status]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_milestone') {
    header('Content-Type: application/json');
    $milestone_type = $_POST['milestone_type'] ?? '';
    $group_id       = $_POST['group_id'] ?? null;
    $valid_types    = ['title', 'title_proposal'];

    if (!in_array($milestone_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid milestone type']);
        exit;
    }

    try {
        if ($milestone_type === 'title') {
            $title = trim($_POST['title'] ?? '');
            if (!$title) { echo json_encode(['success' => false, 'message' => 'Title cannot be empty']); exit; }
            $stmt = $con->prepare("UPDATE groups SET research_title = :title, title_status = 'pending_approval' WHERE id = :group_id");
            $stmt->execute(['title' => $title, 'group_id' => $group_id]);
            echo json_encode(['success' => true, 'message' => 'Title submitted for coordinator approval']);
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload failed']); exit;
        }

        $file               = $_FILES['file'];
        $maxUploadSizeMB    = getSettingInt($con, 'max_upload_size', 10);
        $maxUploadSizeBytes = $maxUploadSizeMB * 1024 * 1024;
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ['application/pdf'])) { echo json_encode(['success' => false, 'message' => 'Invalid file type. PDF only']); exit; }
        if ($file['size'] > $maxUploadSizeBytes) { echo json_encode(['success' => false, 'message' => "File size must be less than {$maxUploadSizeMB}MB"]); exit; }

        $originalFilename = basename($file['name']);
        $fileExtension    = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $uploadDir        = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath       = $uploadDir . $uniqueFilename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']); exit;
        }

        $stmt = $con->prepare("UPDATE groups SET title_proposal_file = :file_path, title_proposal_filename = :original_filename WHERE id = :group_id");
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
    if (!$groupId) { echo json_encode(['success' => false, 'message' => 'Group ID required']); exit; }
    try {
        $con->beginTransaction();
        $con->prepare("DELETE FROM group_milestones WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $grpRes = $con->prepare("SELECT research_id FROM groups WHERE id = :group_id");
        $grpRes->execute(['group_id' => $groupId]);
        $grpResRow = $grpRes->fetch(PDO::FETCH_ASSOC);
        if ($grpResRow && $grpResRow['research_id']) {
            $con->prepare("DELETE FROM urec_documents WHERE research_id = :research_id")->execute(['research_id' => $grpResRow['research_id']]);
            $con->prepare("DELETE FROM thrusts_assignments WHERE research_id = :research_id")->execute(['research_id' => $grpResRow['research_id']]);
        }
        $con->prepare("DELETE FROM student_groups WHERE group_id = :group_id")->execute(['group_id' => $groupId]);
        $con->prepare("DELETE FROM groups WHERE id = :group_id")->execute(['group_id' => $groupId]);
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename_group') {
    header('Content-Type: application/json');
    $groupId  = $_POST['group_id']   ?? null;
    $newName  = trim($_POST['new_name'] ?? '');
    if (!$groupId || !$newName) { echo json_encode(['success' => false, 'message' => 'Group ID and name are required']); exit; }
    try {
        $con->prepare("UPDATE groups SET name = :name WHERE id = :group_id")
            ->execute(['name' => $newName, 'group_id' => $groupId]);
        echo json_encode(['success' => true, 'message' => 'Group renamed successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'poll_status') {
    header('Content-Type: application/json');
    $advisorUserId = $_SESSION['id'];
    $facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
    $facStmt->execute(['user_id' => $advisorUserId]);
    $facRow    = $facStmt->fetch(PDO::FETCH_ASSOC);
    $facultyId = $facRow['id'] ?? null;

    if (!$facultyId) { echo json_encode([]); exit; }

    $gStmt = $con->prepare("
        SELECT g.id, g.title_status, gm.proposal_status, gm.final_defense_status, gm.hardbound_submitted_status,
               gm.applied_copyright_status, gm.research_presented_status,
               gm.research_published_status, gm.copyright_approved_status
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.adviser_id = :adviser_id
    ");
    $gStmt->execute(['adviser_id' => $facultyId]);
    $rows   = $gStmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $r) {
        $resStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :gid");
        $resStmt->execute(['gid' => $r['id']]);
        $resRow = $resStmt->fetch(PDO::FETCH_ASSOC);
        $uMap   = [];
        if ($resRow && $resRow['research_id']) {
            $uStmt = $con->prepare("SELECT document_type, status FROM urec_documents WHERE research_id = :rid ORDER BY id DESC");
            $uStmt->execute(['rid' => $resRow['research_id']]);
            $uDocs = $uStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($uDocs as $ud) if (!isset($uMap[$ud['document_type']])) $uMap[$ud['document_type']] = $ud['status'];
        }
        $result[$r['id']] = [
            'title_status'               => $r['title_status']                ?? 'missing',
            'proposal_status'            => $r['proposal_status']             ?? 'missing',
            'final_defense_status'       => $r['final_defense_status']        ?? 'missing',
            'hardbound_submitted_status' => $r['hardbound_submitted_status']  ?? 'missing',
            'applied_copyright_status'   => $r['applied_copyright_status']    ?? 'missing',
            'research_presented_status'  => $r['research_presented_status']   ?? 'missing',
            'research_published_status'  => $r['research_published_status']   ?? 'missing',
            'copyright_approved_status'  => $r['copyright_approved_status']   ?? 'missing',
            'urec_form_status'           => $uMap['UREC Form']      ?? 'missing',
            'urec_clearance_status'      => $uMap['UREC Clearance'] ?? 'missing',
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
        </html>
<?php
        exit;
    }
}

if (!isset($_SESSION['submit'])) { header('Location: home.php'); exit; }

$milestoneKeyMap = [
    'Proposal Approved'  => 'proposal',
    'Research Completed' => 'final_defense',
    'Hardbound Submitted'=> 'hardbound_submitted',
    'Copyright Applied'  => 'applied_copyright',
    'Research Presented' => 'research_presented',
    'Research Published' => 'research_published',
    'Copyright Approved' => 'copyright_approved',
];

$advisorUserId = $_SESSION['id'];
$facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
$facStmt->execute(['user_id' => $advisorUserId]);
$facRow    = $facStmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $facRow['id'] ?? null;

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (recipient_type = 'all' OR recipient_type = 'advisors' OR (recipient_type = 'specific' AND recipient_id = :user_id))
    AND status != 'deleted'
    ORDER BY created_at DESC LIMIT 10
");
$notificationsStmt->execute(['user_id' => $advisorUserId]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$groups = [];

if ($facultyId) {
    $groupsStmt = $con->prepare("SELECT g.id as group_id, g.name as group_name FROM groups g WHERE g.adviser_id = :adviser_id ORDER BY g.name");
    $groupsStmt->execute(['adviser_id' => $facultyId]);
    $assignedGroups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assignedGroups as $g) {
        $groupId = $g['group_id'];

        $leaderStmt = $con->prepare("
            SELECT s.id, TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(s.middlename,'') || ' ' || COALESCE(s.lastname,'')) as name,
                   s.school_id, gr.research_title, gr.title_status
            FROM students s
            JOIN student_groups sg ON s.id = sg.student_id
            LEFT JOIN groups gr ON sg.group_id = gr.id
            WHERE sg.group_id = :group_id AND sg.is_leader = TRUE
            LIMIT 1
        ");
        $leaderStmt->execute(['group_id' => $groupId]);
        $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);

        $memberStmt = $con->prepare("
            SELECT s.id, TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(s.middlename,'') || ' ' || COALESCE(s.lastname,'')) as name
            FROM students s
            JOIN student_groups sg ON s.id = sg.student_id
            WHERE sg.group_id = :group_id AND sg.is_leader = FALSE
            ORDER BY s.firstname
        ");
        $memberStmt->execute(['group_id' => $groupId]);
        $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

        $grpResStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :id");
        $grpResStmt->execute(['id' => $groupId]);
        $grpResRow  = $grpResStmt->fetch(PDO::FETCH_ASSOC);
        $researchId = $grpResRow['research_id'] ?? null;

        $urecDocs = [];
        if ($researchId) {
            $urecDocsStmt = $con->prepare("
                SELECT id, document_type, file_path, original_filename, status, comment, uploaded_at, approved_at
                FROM urec_documents WHERE research_id = :research_id
                ORDER BY CASE document_type WHEN 'UREC Form' THEN 1 WHEN 'UREC Clearance' THEN 2 END, uploaded_at DESC
            ");
            $urecDocsStmt->execute(['research_id' => $researchId]);
            $allUrecDocs = $urecDocsStmt->fetchAll(PDO::FETCH_ASSOC);
            $urecDocsMap = [];
            foreach ($allUrecDocs as $doc) {
                if (!isset($urecDocsMap[$doc['document_type']])) $urecDocsMap[$doc['document_type']] = $doc;
            }
            $urecDocs = array_values($urecDocsMap);
        }

        $milestoneFiles = [];
        if ($researchId) {
            $milestoneFilesStmt = $con->prepare("
                SELECT milestone_type, file_path, original_filename, uploaded_at
                FROM research_updates WHERE research_id = :research_id AND milestone_type IS NOT NULL
                ORDER BY uploaded_at DESC
            ");
            $milestoneFilesStmt->execute(['research_id' => $researchId]);
            $allMilestoneFiles = $milestoneFilesStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allMilestoneFiles as $mf) {
                $key = $milestoneKeyMap[$mf['milestone_type']] ?? $mf['milestone_type'];
                if (!isset($milestoneFiles[$key])) $milestoneFiles[$key] = $mf;
            }
        }

        $uploads      = [];
        $progress     = 0;
        $approvedCount = 0;

        if ($leader) {
            $milestoneCountStmt = $con->prepare("
                SELECT g.title_status, gm.proposal_status, gm.final_defense_status, gm.hardbound_submitted_status
                FROM groups g LEFT JOIN group_milestones gm ON g.id = gm.group_id WHERE g.id = :group_id
            ");
            $milestoneCountStmt->execute(['group_id' => $groupId]);
            $groupMilestones = $milestoneCountStmt->fetch(PDO::FETCH_ASSOC);

            if ($groupMilestones) {
                if (($groupMilestones['title_status']               ?? '') === 'approved')  $approvedCount++;
                if (($groupMilestones['proposal_status']            ?? '') === 'completed') $approvedCount++;
                if (($groupMilestones['final_defense_status']       ?? '') === 'completed') $approvedCount++;
                if (($groupMilestones['hardbound_submitted_status'] ?? '') === 'completed') $approvedCount++;
            }

            foreach ($urecDocs as $doc) {
                if ($doc['status'] === 'approved') $approvedCount++;
            }

            $progress = round(($approvedCount / 6) * 100);
        }

        $milestoneStatusStmt = $con->prepare("
            SELECT proposal_status, final_defense_status, hardbound_submitted_status, applied_copyright_status,
                   research_presented_status, research_published_status, copyright_approved_status,
                   proposal_approved_at, final_defense_approved_at, hardbound_submitted_approved_at,
                   applied_copyright_approved_at, research_presented_approved_at,
                   research_published_approved_at, copyright_approved_approved_at
            FROM group_milestones WHERE group_id = :group_id
        ");
        $milestoneStatusStmt->execute(['group_id' => $groupId]);
        $milestoneStatuses = $milestoneStatusStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $resTitle    = $leader['research_title'] ?? null;
        $titleStatus = 'missing';
        if (!$resTitle) {
            $grpTitleStmt = $con->prepare("SELECT research_title, title_status FROM groups WHERE id = :id");
            $grpTitleStmt->execute(['id' => $groupId]);
            $grpTitleRow = $grpTitleStmt->fetch(PDO::FETCH_ASSOC);
            $resTitle    = $grpTitleRow['research_title'] ?? null;
            $titleStatus = $grpTitleRow['title_status']   ?? 'missing';
        } else {
            $titleStatus = $leader['title_status'] ?? 'missing';
        }

        $assignedSdgs    = [];
        $assignedThrusts = [];
        if ($researchId) {
            $groupSdgStmt = $con->prepare("SELECT s.id, s.name FROM sdgs s JOIN thrusts_assignments ta ON s.id = ta.sdg_id WHERE ta.research_id = :research_id ORDER BY s.name");
            $groupSdgStmt->execute(['research_id' => $researchId]);
            $assignedSdgs = $groupSdgStmt->fetchAll(PDO::FETCH_ASSOC);

            $groupThrustStmt = $con->prepare("SELECT t.id, t.name FROM thrusts t JOIN thrusts_assignments ta ON t.id = ta.thrust_id WHERE ta.research_id = :research_id ORDER BY t.name");
            $groupThrustStmt->execute(['research_id' => $researchId]);
            $assignedThrusts = $groupThrustStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $groups[] = [
            'group_id'           => $groupId,
            'group_name'         => $g['group_name'],
            'research_id'        => $researchId,
            'leader'             => $leader,
            'members'            => $members,
            'uploads'            => $uploads,
            'urec_docs'          => $urecDocs,
            'milestone_files'    => $milestoneFiles,
            'progress'           => $progress,
            'res_title'          => $resTitle,
            'title_status'       => $titleStatus,
            'milestone_statuses' => $milestoneStatuses,
            'assigned_sdgs'      => $assignedSdgs,
            'assigned_thrusts'   => $assignedThrusts,
        ];
    }
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
                                        <span id="group-name-<?= $grp['group_id'] ?>"><?= htmlspecialchars($grp['group_name']) ?></span>
                                        <button class="rename-group-btn" onclick="openRenameModal(<?= $grp['group_id'] ?>, '<?= htmlspecialchars($grp['group_name'], ENT_QUOTES) ?>')" title="Rename group" style="background:none;border:none;cursor:pointer;color:#888;padding:2px 4px;margin-left:4px;border-radius:4px;transition:color 0.2s;">
                                            <i class="ri-pencil-line" style="font-size:14px;"></i>
                                        </button>
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
                                        <?php $hasProposal = !empty($grp['group_files']['title_proposal_file']); ?>
                                        <?php if (!$hasProposal): ?>
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
                                        <div class="column-header">
                                            <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                            <button style="color:#ffffff;" id="iconz" class="assign-btn-compact" onclick="openAssignModal('sdg', <?= $grp['group_id'] ?>, <?= $grp['research_id'] ?? 'null' ?>)">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($grp['assigned_sdgs'])): ?>
                                            <div class="tags-list-vertical">
                                                <?php foreach ($grp['assigned_sdgs'] as $sdg): ?>
                                                    <span class="tag-compact">
                                                        <?= htmlspecialchars($sdg['name']) ?>
                                                        <i class="ri-close-line" onclick="removeAssignment('sdg', <?= $grp['research_id'] ?? 'null' ?>, <?= $sdg['id'] ?>, this)"></i>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-items-msg">No SDGs assigned</div>
                                        <?php endif; ?>

                                        <div class="column-header" style="margin-top:20px;">
                                            <h4><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                            <button id="iconz" class="assign-btn-compact" onclick="openAssignModal('thrust', <?= $grp['group_id'] ?>, <?= $grp['research_id'] ?? 'null' ?>)">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($grp['assigned_thrusts'])): ?>
                                            <div class="tags-list-vertical">
                                                <?php foreach ($grp['assigned_thrusts'] as $thrust): ?>
                                                    <span class="tag-compact thrust">
                                                        <?= htmlspecialchars($thrust['name']) ?>
                                                        <i class="ri-close-line" onclick="removeAssignment('thrust', <?= $grp['research_id'] ?? 'null' ?>, <?= $thrust['id'] ?>, this)"></i>
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
                                        <p style="font-size:13px;color:#6c757d;margin:0 0 12px;"><i class="ri-information-line"></i> Review and endorse student milestone uploads. Endorsed milestones are forwarded to the coordinator for final approval.</p>
                                        <?php
                                        $milestoneTypes = [
                                            ['key' => 'proposal',            'label' => 'Proposal Approved',  'icon' => 'ri-file-text-line',     'src' => 'research_updates'],
                                            ['key' => 'urec_form',           'label' => 'UREC Applied',       'icon' => 'ri-file-shield-line',   'src' => 'urec'],
                                            ['key' => 'urec_clearance',      'label' => 'UREC Approved',      'icon' => 'ri-file-shield-2-line', 'src' => 'urec'],
                                            ['key' => 'final_defense',       'label' => 'Research Completed', 'icon' => 'ri-presentation-line',  'src' => 'research_updates'],
                                            ['key' => 'hardbound_submitted', 'label' => 'Hardbound Submitted','icon' => 'ri-book-2-line',        'src' => 'research_updates'],
                                            ['key' => 'applied_copyright',   'label' => 'Copyright Applied',  'icon' => 'ri-copyright-line',     'src' => 'research_updates'],
                                            ['key' => 'research_presented',  'label' => 'Research Presented', 'icon' => 'ri-slideshow-3-line',   'src' => 'research_updates'],
                                            ['key' => 'research_published',  'label' => 'Research Published', 'icon' => 'ri-newspaper-line',     'src' => 'research_updates'],
                                            ['key' => 'copyright_approved',  'label' => 'Copyright Approved', 'icon' => 'ri-shield-check-line',  'src' => 'research_updates'],
                                        ];
                                        $evidenceLabels = [
                                            'proposal'            => 'Rating sheets (graded)',
                                            'urec_form'           => 'Received letter of intent',
                                            'urec_clearance'      => 'Copy of the clearance',
                                            'final_defense'       => 'Final defense rating sheet (graded)',
                                            'hardbound_submitted' => 'Photo of the hardbound thesis with student and adviser',
                                            'applied_copyright'   => 'Certificate of copyright with copyright number',
                                            'research_presented'  => 'Letter of acceptance and certificate of recognition',
                                            'research_published'  => 'Letter of acceptance, copy of published article, and DOI link (if possible)',
                                            'copyright_approved'  => 'Certificate',
                                        ];
                                        $stepNum = 1;
                                        foreach ($milestoneTypes as $m):
                                            $hasFile    = false;
                                            $fileName   = '';
                                            $fileDate   = '';
                                            $filePath   = '';
                                            $docStatus  = 'missing';
                                            $docComment = '';

                                            if ($m['src'] === 'urec') {
                                                $docType = ($m['key'] === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
                                                foreach ($grp['urec_docs'] as $doc) {
                                                    if ($doc['document_type'] === $docType) {
                                                        $hasFile    = true;
                                                        $fileName   = $doc['original_filename'];
                                                        $fileDate   = date("M d, Y • h:i A", strtotime($doc['uploaded_at']));
                                                        $filePath   = $doc['file_path'];
                                                        $docStatus  = $doc['status'];
                                                        $docComment = $doc['comment'] ?? '';
                                                        break;
                                                    }
                                                }
                                            } else {
                                                if (isset($grp['milestone_files'][$m['key']])) {
                                                    $mf       = $grp['milestone_files'][$m['key']];
                                                    $hasFile  = true;
                                                    $fileName = $mf['original_filename'];
                                                    $fileDate = date("M d, Y • h:i A", strtotime($mf['uploaded_at']));
                                                    $filePath = $mf['file_path'];
                                                    $statusKey = $m['key'] . '_status';
                                                    $rawStatus = $grp['milestone_statuses'][$statusKey] ?? 'pending';
                                                    $docStatus = $rawStatus ?: 'pending';
                                                }
                                            }

                                            $evidenceLabel    = $evidenceLabels[$m['key']] ?? '';
                                            $statusBadgeClass = 'status-missing';
                                            $statusBadgeText  = 'Not Uploaded';
                                            if ($hasFile) {
                                                if ($docStatus === 'completed' || $docStatus === 'approved') {
                                                    $statusBadgeClass = 'status-approved';
                                                    $statusBadgeText  = 'Approved';
                                                } elseif ($docStatus === 'endorsed') {
                                                    $statusBadgeClass = 'status-approved';
                                                    $statusBadgeText  = 'Endorsed';
                                                } elseif ($docStatus === 'rejected') {
                                                    $statusBadgeClass = 'status-rejected';
                                                    $statusBadgeText  = 'Rejected';
                                                } else {
                                                    $statusBadgeClass = 'status-pending_approval';
                                                    $statusBadgeText  = 'Pending Review';
                                                }
                                            }
                                        ?>
                                            <div class="milestone-upload-card">
                                                <div class="milestone-header">
                                                    <div class="milestone-title-status">
                                                        <span class="milestone-step-num <?= ($docStatus === 'completed' || $docStatus === 'approved' || $docStatus === 'endorsed') ? 'done' : '' ?>"><?= $stepNum++ ?></span>
                                                        <div>
                                                            <strong><i class="<?= $m['icon'] ?>"></i> <?= $m['label'] ?></strong>
                                                            <small style="color:#666;font-weight:normal;"><?= $evidenceLabel ?></small>
                                                        </div>
                                                        <span class="status-badge <?= $statusBadgeClass ?>"><?= $statusBadgeText ?></span>
                                                    </div>
                                                    <?php if ($hasFile && $docStatus === 'pending'): ?>
                                                        <button class="milestone-upload-btn" onclick="openMilestoneActionModal(<?= $grp['group_id'] ?>, '<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($docComment, ENT_QUOTES) ?>')">
                                                            <i class="ri-checkbox-circle-line"></i> Review
                                                        </button>
                                                    <?php elseif ($hasFile && $docStatus === 'endorsed'): ?>
                                                        <button class="milestone-upload-btn" style="background:#6c757d;" onclick="openMilestoneActionModal(<?= $grp['group_id'] ?>, '<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($docComment, ENT_QUOTES) ?>')">
                                                            <i class="ri-edit-line"></i> Re-endorse
                                                        </button>
                                                    <?php elseif ($hasFile && ($docStatus === 'completed' || $docStatus === 'approved')): ?>
                                                        <button class="milestone-upload-btn" style="background:#28a745;cursor:default;" disabled>
                                                            <i class="ri-checkbox-circle-fill"></i> Approved
                                                        </button>
                                                    <?php elseif (!$hasFile): ?>
                                                        <button class="milestone-upload-btn" style="background:#adb5bd;cursor:not-allowed;" disabled>
                                                            <i class="ri-time-line"></i> Awaiting Upload
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($hasFile): ?>
                                                    <div class="milestone-file-details">
                                                        <p><i class="ri-file-line"></i> <?= htmlspecialchars($fileName) ?></p>
                                                        <p><i class="ri-calendar-line"></i> Uploaded: <?= $fileDate ?></p>
                                                        <?php
                                                        $approvedAt = null;
                                                        if ($m['src'] === 'urec') {
                                                            $docType = ($m['key'] === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
                                                            foreach ($grp['urec_docs'] as $doc) {
                                                                if ($doc['document_type'] === $docType && !empty($doc['approved_at'])) {
                                                                    $approvedAt = $doc['approved_at'];
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            $approvedAtKey = $m['key'] . '_approved_at';
                                                            if (!empty($grp['milestone_statuses'][$approvedAtKey])) {
                                                                $approvedAt = $grp['milestone_statuses'][$approvedAtKey];
                                                            }
                                                        }
                                                        if ($approvedAt): ?>
                                                            <p style="color:#155724;font-weight:600;"><i class="ri-checkbox-circle-line"></i> Date Approved: <?= date("M d, Y", strtotime($approvedAt)) ?></p>
                                                        <?php endif; ?>
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
                                            $statusText  = 'Pending';
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
                                                                <button class="approve-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'approved')"><i class="ri-check-line"></i> Approve</button>
                                                                <button class="reject-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'rejected')"><i class="ri-close-line"></i> Reject</button>
                                                            <?php else: ?>
                                                                <div style="padding:8px 12px;color:#28a745;font-size:13px;border-bottom:1px solid #e9ecef;"><i class="ri-lock-line"></i> Approved - Locked</div>
                                                            <?php endif; ?>
                                                            <button class="comment-btn" onclick="openCommentModal(<?= $upload['upload_id'] ?>, '<?= htmlspecialchars($upload['task_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($upload['comment'] ?? '', ENT_QUOTES) ?>')">
                                                                <i class="ri-chat-3-line"></i> <?= !empty($upload['comment']) ? 'Edit Comment' : 'Add Comment' ?>
                                                            </button>
                                                            <a href="<?= htmlspecialchars($upload['file_path']) ?>" download><i class="ri-download-line"></i> Download</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="upload-card-body">
                                                    <p class="file-filename"><?= htmlspecialchars($upload['original_filename'] ?? $upload['file_path']) ?></p>
                                                    <p class="file-date"><?= date("M d, Y • h:i A", strtotime($upload['uploaded_at'])) ?></p>
                                                    <?php if (!empty($upload['comment'])): ?>
                                                        <div class="file-comment"><strong>Your Comment:</strong><br><?= htmlspecialchars($upload['comment']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-uploads-msg"><i class="ri-folder-open-line"></i> No uploaded files yet</div>
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
                    <p>Click "Add New Title" to start managing your advisees.</p>
                </div>
            <?php endif; ?>
        </div>

        <button id="aiChatButton">AI Chat</button>
        <div id="aiChatbox">
            <iframe src="http://172.20.10.2:5000" frameborder="0"></iframe>
        </div>
        <div style="height:50px;" class="space"></div>
    </main>

    <div class="comment-modal" id="renameGroupModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:10px;padding:25px;width:90%;max-width:400px;position:relative;">
            <button onclick="closeRenameModal()" style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#999;">&times;</button>
            <h3 style="margin-bottom:15px;font-size:16px;color:#8B0000;"><i class="ri-pencil-line"></i> Rename Group</h3>
            <input type="hidden" id="renameGroupId">
            <input type="text" id="renameGroupInput" placeholder="Enter new group name" style="width:100%;padding:10px;margin:10px 0 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="closeRenameModal()" style="padding:8px 18px;border-radius:6px;border:none;background:#6c757d;color:#fff;cursor:pointer;">Cancel</button>
                <button onclick="submitRename()" style="padding:8px 18px;border-radius:6px;border:none;background:#8B0000;color:#fff;cursor:pointer;">Save</button>
            </div>
        </div>
    </div>

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
                <input type="text" id="leaderSearchInput" placeholder="Type student ID or name..." autocomplete="off"
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;"
                    oninput="handleLeaderSearch(this.value)">
                <div class="autocomplete-list" id="leaderSuggestions" style="display:none;"></div>
            </div>
            <div id="leaderSelectedBox" class="selected-students-box" style="margin-bottom:12px;"></div>
            <input type="hidden" id="leaderSchoolId" value="">
            <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Members</label>
            <div class="autocomplete-wrapper" id="membersWrapper">
                <input type="text" id="memberSearchInput" placeholder="Type student ID or name..." autocomplete="off"
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
                    <a id="downloadLinkBtn" href="#" download class="download-link-btn"><i class="ri-download-line"></i> Download</a>
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
                <button class="btn-submit" onclick="submitMilestoneAction('endorsed')"><i class="ri-checkbox-circle-line"></i> Endorse</button>
            </div>
        </div>
    </div>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">×</span>
            <h3 id="assignModalTitle">Assign</h3>
            <div id="assignCheckboxes" style="max-height:300px;overflow-y:auto;padding:10px;"></div>
            <button onclick="submitAssignment()" style="margin-top:15px;padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;">Assign Selected</button>
        </div>
    </div>

    <script src="js/timeout.js"></script>
    <script src="js/advisees.js"></script>
    <script src="js/notifications.js"></script>
    <script>
        async function removeMember(studentId, groupId, btn) {
            const row    = btn.closest('.member-item-compact');
            const nameEl = row.querySelector('.member-left');
            const name   = nameEl ? nameEl.textContent.trim() : 'this member';
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

        function openRenameModal(groupId, currentName) {
            document.getElementById('renameGroupId').value    = groupId;
            document.getElementById('renameGroupInput').value = currentName;
            const modal = document.getElementById('renameGroupModal');
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('renameGroupInput').focus(), 50);
        }

        function closeRenameModal() {
            document.getElementById('renameGroupModal').style.display = 'none';
        }

        document.getElementById('renameGroupInput')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') submitRename();
            if (e.key === 'Escape') closeRenameModal();
        });

        async function submitRename() {
            const groupId = document.getElementById('renameGroupId').value;
            const newName = document.getElementById('renameGroupInput').value.trim();
            if (!newName) { showToast('Please enter a group name', 'error'); return; }
            try {
                const formData = new FormData();
                formData.append('action',   'rename_group');
                formData.append('group_id', groupId);
                formData.append('new_name', newName);
                const res  = await fetch('advisees.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeRenameModal();
                    const nameSpan = document.getElementById('group-name-' + groupId);
                    if (nameSpan) nameSpan.textContent = newName;
                    showToast('Group renamed successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to rename group', 'error');
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
                const res  = await fetch('advisees.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast('Group deleted successfully', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Failed to delete group', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        }

        (function() {
            const snapshot = {};
            <?php foreach ($groups as $grp):
                $ms = $grp['milestone_statuses'];
                $urecFormSnap      = 'missing';
                $urecClearanceSnap = 'missing';
                foreach ($grp['urec_docs'] as $ud) {
                    if ($ud['document_type'] === 'UREC Form')      $urecFormSnap      = $ud['status'];
                    if ($ud['document_type'] === 'UREC Clearance') $urecClearanceSnap = $ud['status'];
                }
            ?>
                snapshot[<?= $grp['group_id'] ?>] = {
                    title_status:                '<?= $grp['title_status'] ?>',
                    proposal_status:             '<?= $ms['proposal_status']             ?? 'missing' ?>',
                    final_defense_status:        '<?= $ms['final_defense_status']        ?? 'missing' ?>',
                    hardbound_submitted_status:  '<?= $ms['hardbound_submitted_status']  ?? 'missing' ?>',
                    applied_copyright_status:    '<?= $ms['applied_copyright_status']    ?? 'missing' ?>',
                    research_presented_status:   '<?= $ms['research_presented_status']   ?? 'missing' ?>',
                    research_published_status:   '<?= $ms['research_published_status']   ?? 'missing' ?>',
                    copyright_approved_status:   '<?= $ms['copyright_approved_status']   ?? 'missing' ?>',
                    urec_form_status:            '<?= $urecFormSnap ?>',
                    urec_clearance_status:       '<?= $urecClearanceSnap ?>',
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