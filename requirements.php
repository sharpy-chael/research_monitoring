<?php
session_start();
include('connect.php');
include("php/get_setting.php");
include("check_session.php");

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_milestone') {
    header('Content-Type: application/json');

    $school_id   = $_SESSION['school_id'];
    $studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
    $studentStmt->execute(['school_id' => $school_id]);
    $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentRow) { echo json_encode(['success' => false, 'message' => 'Student not found']); exit; }

    $group_id = $_POST['group_id'] ?? null;
    if (!$group_id) { echo json_encode(['success' => false, 'message' => 'Group ID required']); exit; }

    $memberCheck = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
    $memberCheck->execute(['student_id' => $studentRow['id'], 'group_id' => $group_id]);
    if (!$memberCheck->fetch()) { echo json_encode(['success' => false, 'message' => 'You are not in this group']); exit; }

    $grpStmt = $con->prepare("SELECT title_status, research_id FROM groups WHERE id = :group_id");
    $grpStmt->execute(['group_id' => $group_id]);
    $groupRow = $grpStmt->fetch(PDO::FETCH_ASSOC);

    if (!$groupRow || $groupRow['title_status'] !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Research title must be approved before uploading milestone documents']);
        exit;
    }

    $researchId     = $groupRow['research_id'];
    $milestone_type = $_POST['milestone_type'] ?? '';
    $valid_types    = [
        'proposal', 'urec_form', 'urec_clearance', 'final_defense',
        'hardbound_submitted', 'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'
    ];

    if (!in_array($milestone_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid milestone type']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }

    $file               = $_FILES['file'];
    $maxUploadSizeMB    = getSettingInt($con, 'max_upload_size', 10);
    $maxUploadSizeBytes = $maxUploadSizeMB * 1024 * 1024;
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') { echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']); exit; }
    if ($file['size'] > $maxUploadSizeBytes) { echo json_encode(['success' => false, 'message' => "File size must be less than {$maxUploadSizeMB}MB"]); exit; }

    $originalFilename = basename($file['name']);
    $fileExtension    = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    $uploadDir        = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath       = $uploadDir . $uniqueFilename;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit;
    }

    $approved_at = !empty($_POST['approved_at']) ? $_POST['approved_at'] : null;
    if (!$approved_at) {
        echo json_encode(['success' => false, 'message' => 'Date approved is required']);
        exit;
    }

    try {
        if ($milestone_type === 'urec_form' || $milestone_type === 'urec_clearance') {
            $docType    = ($milestone_type === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
            $existsStmt = $con->prepare("SELECT id FROM urec_documents WHERE research_id = :research_id AND document_type = :doc_type ORDER BY uploaded_at DESC LIMIT 1");
            $existsStmt->execute(['research_id' => $researchId, 'doc_type' => $docType]);
            $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $con->prepare("UPDATE urec_documents SET file_path = :file_path, original_filename = :original_filename, status = 'pending', uploaded_at = NOW(), approved_at = :approved_at, comment = NULL WHERE id = :id")
                    ->execute(['file_path' => $filePath, 'original_filename' => $originalFilename, 'approved_at' => $approved_at, 'id' => $existing['id']]);
            } else {
                $con->prepare("INSERT INTO urec_documents (research_id, document_type, file_path, original_filename, status, uploaded_at, approved_at) VALUES (:research_id, :document_type, :file_path, :original_filename, 'pending', NOW(), :approved_at)")
                    ->execute(['research_id' => $researchId, 'document_type' => $docType, 'file_path' => $filePath, 'original_filename' => $originalFilename, 'approved_at' => $approved_at]);
            }
        } else {
            $milestoneLabels = [
                'proposal'            => 'Proposal Approved',
                'final_defense'       => 'Research Completed',
                'hardbound_submitted' => 'Hardbound Submitted',
                'applied_copyright'   => 'Copyright Applied',
                'research_presented'  => 'Research Presented',
                'research_published'  => 'Research Published',
                'copyright_approved'  => 'Copyright Approved',
            ];
            $milestoneLabel = $milestoneLabels[$milestone_type] ?? $milestone_type;
            $con->prepare("INSERT INTO research_updates (research_id, milestone_type, file_path, original_filename, uploaded_at, date_accomplished) VALUES (:research_id, :milestone_type, :file_path, :original_filename, NOW(), :date_accomplished)")
                ->execute(['research_id' => $researchId, 'milestone_type' => $milestoneLabel, 'file_path' => $filePath, 'original_filename' => $originalFilename, 'date_accomplished' => $approved_at]);
            $milestoneColumn  = $milestone_type . '_status';
            $approvedAtColumn = $milestone_type . '_approved_at';
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $group_id]);
            if ($checkStmt->rowCount() > 0) {
                $con->prepare("UPDATE group_milestones SET {$milestoneColumn} = 'pending', {$approvedAtColumn} = :approved_at WHERE group_id = :group_id")
                    ->execute(['approved_at' => $approved_at, 'group_id' => $group_id]);
            } else {
                $con->prepare("INSERT INTO group_milestones (group_id, {$milestoneColumn}, {$approvedAtColumn}) VALUES (:group_id, 'pending', :approved_at)")
                    ->execute(['group_id' => $group_id, 'approved_at' => $approved_at]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Document uploaded successfully. Your adviser will review it.']);
    } catch (Exception $e) {
        if (file_exists($filePath)) unlink($filePath);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'poll_status') {
    header('Content-Type: application/json');
    $school_id   = $_SESSION['school_id'];
    $studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
    $studentStmt->execute(['school_id' => $school_id]);
    $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$studentRow) { echo json_encode([]); exit; }

    $sgStmt = $con->prepare("SELECT group_id FROM student_groups WHERE student_id = :student_id");
    $sgStmt->execute(['student_id' => $studentRow['id']]);
    $groupIds = $sgStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($groupIds)) { echo json_encode([]); exit; }

    $result = [];
    foreach ($groupIds as $gid) {
        $grpStmt = $con->prepare("SELECT title_status, research_id FROM groups WHERE id = :id");
        $grpStmt->execute(['id' => $gid]);
        $gRow = $grpStmt->fetch(PDO::FETCH_ASSOC);
        $rid  = $gRow['research_id'] ?? null;

        $mStmt = $con->prepare("SELECT proposal_status, final_defense_status, hardbound_submitted_status, applied_copyright_status, research_presented_status, research_published_status, copyright_approved_status FROM group_milestones WHERE group_id = :id");
        $mStmt->execute(['id' => $gid]);
        $mRow = $mStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $urecMap = [];
        if ($rid) {
            $ufStmt = $con->prepare("SELECT document_type, status FROM urec_documents WHERE research_id = :rid ORDER BY uploaded_at DESC");
            $ufStmt->execute(['rid' => $rid]);
            foreach ($ufStmt->fetchAll(PDO::FETCH_ASSOC) as $ur) {
                if (!isset($urecMap[$ur['document_type']])) $urecMap[$ur['document_type']] = $ur['status'];
            }
        }

        $mapStatus    = fn($v) => match($v) { 'completed' => 'approved', 'endorsed' => 'endorsed', 'pending' => 'pending', 'rejected' => 'rejected', default => 'missing' };
        $rawTitle     = $gRow['title_status'] ?? '';
        $result[$gid] = [
            'title_status'                  => match($rawTitle) { 'approved' => 'approved', 'pending_approval' => 'pending_approval', 'rejected' => 'rejected', default => 'missing' },
            'proposal_status'               => $mapStatus($mRow['proposal_status']               ?? ''),
            'final_defense_status'          => $mapStatus($mRow['final_defense_status']          ?? ''),
            'hardbound_submitted_status'    => $mapStatus($mRow['hardbound_submitted_status']    ?? ''),
            'applied_copyright_status'      => $mapStatus($mRow['applied_copyright_status']      ?? ''),
            'research_presented_status'     => $mapStatus($mRow['research_presented_status']     ?? ''),
            'research_published_status'     => $mapStatus($mRow['research_published_status']     ?? ''),
            'copyright_approved_status'     => $mRow['copyright_approved_status'] ?: 'missing',
            'urec_form_status'              => $urecMap['UREC Form']      ?? 'missing',
            'urec_clearance_status'         => $urecMap['UREC Clearance'] ?? 'missing',
        ];
    }
    echo json_encode($result);
    exit;
}

$school_id = $_SESSION['school_id'];

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (recipient_type = 'all' OR recipient_type = 'students' OR (recipient_type = 'specific' AND recipient_id = :user_id))
    AND status != 'deleted'
    ORDER BY created_at DESC LIMIT 10
");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
$studentStmt->execute(['school_id' => $school_id]);
$studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);
$studentId  = $studentRow['id'] ?? null;

$enabledStatusesStmt = $con->query("SELECT name FROM research_statuses WHERE is_active = TRUE");
$enabledStatuses     = $enabledStatusesStmt->fetchAll(PDO::FETCH_COLUMN);
$maxUploadSizeMB     = getSettingInt($con, 'max_upload_size', 10);

$milestoneKeyMap = [
    'Proposal Approved'  => 'proposal',
    'Research Completed' => 'final_defense',
    'Hardbound Submitted'=> 'hardbound_submitted',
    'Copyright Applied'  => 'applied_copyright',
    'Research Presented' => 'research_presented',
    'Research Published' => 'research_published',
    'Copyright Approved' => 'copyright_approved',
];

$groups = [];

if ($studentId) {
    $sgStmt = $con->prepare("
        SELECT sg.group_id, sg.is_leader, g.name as group_name, g.research_title, g.title_status,
               g.research_id, f.name AS adviser_name
        FROM student_groups sg
        JOIN groups g ON sg.group_id = g.id
        LEFT JOIN faculties f ON g.adviser_id = f.id
        WHERE sg.student_id = :student_id
        ORDER BY g.name
    ");
    $sgStmt->execute(['student_id' => $studentId]);
    $assignedGroups = $sgStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assignedGroups as $ag) {
        $groupId     = $ag['group_id'];
        $researchId  = $ag['research_id'];
        $titleStatus = $ag['title_status'] ?? 'missing';
        $adviserName = !empty($ag['adviser_name']) ? $ag['adviser_name'] : 'Adviser';

        $urecFormStatus      = 'missing'; $urecFormFile      = null;
        $urecClearanceStatus = 'missing'; $urecClearanceFile = null;

        if ($researchId) {
            $urecFormStmt = $con->prepare("SELECT status, original_filename, file_path, uploaded_at, comment, approved_at FROM urec_documents WHERE research_id = :rid AND document_type = 'UREC Form' ORDER BY uploaded_at DESC LIMIT 1");
            $urecFormStmt->execute(['rid' => $researchId]);
            $urecFormRow = $urecFormStmt->fetch(PDO::FETCH_ASSOC);
            if ($urecFormRow) { $urecFormStatus = $urecFormRow['status']; $urecFormFile = $urecFormRow; }

            $urecClearanceStmt = $con->prepare("SELECT status, original_filename, file_path, uploaded_at, comment, approved_at FROM urec_documents WHERE research_id = :rid AND document_type = 'UREC Clearance' ORDER BY uploaded_at DESC LIMIT 1");
            $urecClearanceStmt->execute(['rid' => $researchId]);
            $urecClearanceRow = $urecClearanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($urecClearanceRow) { $urecClearanceStatus = $urecClearanceRow['status']; $urecClearanceFile = $urecClearanceRow; }
        }

        $proposalStatus = $finalDefenseStatus = $hardboundStatus = $appliedCopyrightStatus = 'missing';
        $researchPresentedStatus = $researchPublishedStatus = $copyrightApprovedStatus = 'missing';

        $milestoneStmt = $con->prepare("
            SELECT proposal_status, final_defense_status, hardbound_submitted_status, applied_copyright_status,
                   research_presented_status, research_published_status, copyright_approved_status,
                   proposal_approved_at, final_defense_approved_at, hardbound_submitted_approved_at,
                   applied_copyright_approved_at, research_presented_approved_at,
                   research_published_approved_at, copyright_approved_approved_at
            FROM group_milestones WHERE group_id = :group_id
        ");
        $milestoneStmt->execute(['group_id' => $groupId]);
        $milestone = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

        if ($milestone) {
            $mapStatus = fn($v) => match($v) { 'completed' => 'approved', 'endorsed' => 'endorsed', 'pending' => 'pending', 'rejected' => 'rejected', default => 'missing' };
            $proposalStatus          = $mapStatus($milestone['proposal_status']               ?? '');
            $finalDefenseStatus      = $mapStatus($milestone['final_defense_status']          ?? '');
            $hardboundStatus         = $mapStatus($milestone['hardbound_submitted_status']    ?? '');
            $appliedCopyrightStatus  = $mapStatus($milestone['applied_copyright_status']      ?? '');
            $researchPresentedStatus = $mapStatus($milestone['research_presented_status']     ?? '');
            $researchPublishedStatus = $mapStatus($milestone['research_published_status']     ?? '');
            $copyrightApprovedStatus = $milestone['copyright_approved_status'] ?? 'missing';
        }

        $milestoneFiles = [];
        if ($researchId) {
            $mfStmt = $con->prepare("SELECT milestone_type, file_path, original_filename, uploaded_at FROM research_updates WHERE research_id = :research_id AND milestone_type IS NOT NULL ORDER BY uploaded_at DESC");
            $mfStmt->execute(['research_id' => $researchId]);
            foreach ($mfStmt->fetchAll(PDO::FETCH_ASSOC) as $mf) {
                $key = $milestoneKeyMap[$mf['milestone_type']] ?? $mf['milestone_type'];
                if (!isset($milestoneFiles[$key])) $milestoneFiles[$key] = $mf;
            }
        }

        $assignedSdgs = $assignedThrusts = [];
        if ($researchId) {
            $sdgsStmt = $con->prepare("SELECT s.name FROM sdgs s JOIN thrusts_assignments ta ON s.id = ta.sdg_id WHERE ta.research_id = :research_id ORDER BY s.name");
            $sdgsStmt->execute(['research_id' => $researchId]);
            $assignedSdgs = $sdgsStmt->fetchAll(PDO::FETCH_COLUMN);

            $thrustsStmt = $con->prepare("SELECT t.name FROM thrusts t JOIN thrusts_assignments ta ON t.id = ta.thrust_id WHERE ta.research_id = :research_id ORDER BY t.name");
            $thrustsStmt->execute(['research_id' => $researchId]);
            $assignedThrusts = $thrustsStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $uploadMap = [];
        $uploadsStmt = $con->prepare("SELECT task_name, status, comment, file_path, original_filename, uploaded_at FROM uploads WHERE group_id = :group_id ORDER BY uploaded_at DESC");
        $uploadsStmt->execute(['group_id' => $groupId]);
        foreach ($uploadsStmt->fetchAll(PDO::FETCH_ASSOC) as $upload) {
            if (!isset($uploadMap[$upload['task_name']])) $uploadMap[$upload['task_name']] = $upload;
        }

        $approvedCount = 0;

        $milestoneCountStmt = $con->prepare("
            SELECT g.title_status, gm.proposal_status, gm.final_defense_status, gm.hardbound_submitted_status
            FROM groups g LEFT JOIN group_milestones gm ON g.id = gm.group_id WHERE g.id = :group_id
        ");
        $milestoneCountStmt->execute(['group_id' => $groupId]);
        $gm = $milestoneCountStmt->fetch(PDO::FETCH_ASSOC);
        if ($gm) {
            if (($gm['title_status']               ?? '') === 'approved')  $approvedCount++;
            if (($gm['proposal_status']            ?? '') === 'completed') $approvedCount++;
            if (($gm['final_defense_status']       ?? '') === 'completed') $approvedCount++;
            if (($gm['hardbound_submitted_status'] ?? '') === 'completed') $approvedCount++;
        }
        if ($urecFormRow      && ($urecFormRow['status']      ?? '') === 'approved') $approvedCount++;
        if ($urecClearanceRow && ($urecClearanceRow['status'] ?? '') === 'approved') $approvedCount++;

        $progress = round(($approvedCount / 6) * 100);

        $milestoneTypesArr = [
            ['key' => 'proposal',            'label' => 'Proposal Approved',        'icon' => 'ri-file-text-line',     'evidence' => 'Rating sheets (graded)',                                                       'status' => $proposalStatus,          'file_path' => $milestoneFiles['proposal']['file_path']                ?? '', 'filename' => $milestoneFiles['proposal']['original_filename']                ?? '', 'uploaded_at' => $milestoneFiles['proposal']['uploaded_at']                ?? '', 'approved_at' => $milestone['proposal_approved_at']                ?? ''],
            ['key' => 'urec_form',           'label' => 'UREC Applied',             'icon' => 'ri-file-shield-line',   'evidence' => 'Received letter of intent',                                                    'status' => $urecFormStatus,          'file_path' => $urecFormFile['file_path']                              ?? '', 'filename' => $urecFormFile['original_filename']                              ?? '', 'uploaded_at' => $urecFormFile['uploaded_at']                              ?? '', 'comment' => $urecFormFile['comment'] ?? '', 'approved_at' => $urecFormFile['approved_at'] ?? ''],
            ['key' => 'urec_clearance',      'label' => 'UREC Approved',            'icon' => 'ri-file-shield-2-line', 'evidence' => 'Copy of the clearance',                                                        'status' => $urecClearanceStatus,     'file_path' => $urecClearanceFile['file_path']                         ?? '', 'filename' => $urecClearanceFile['original_filename']                         ?? '', 'uploaded_at' => $urecClearanceFile['uploaded_at']                         ?? '', 'comment' => $urecClearanceFile['comment'] ?? '', 'approved_at' => $urecClearanceFile['approved_at'] ?? ''],
            ['key' => 'final_defense',       'label' => 'Research Completed',       'icon' => 'ri-presentation-line',  'evidence' => 'Final defense rating sheet (graded)',                                          'status' => $finalDefenseStatus,      'file_path' => $milestoneFiles['final_defense']['file_path']           ?? '', 'filename' => $milestoneFiles['final_defense']['original_filename']           ?? '', 'uploaded_at' => $milestoneFiles['final_defense']['uploaded_at']           ?? '', 'approved_at' => $milestone['final_defense_approved_at']           ?? ''],
            ['key' => 'hardbound_submitted', 'label' => 'Hardbound Submitted',      'icon' => 'ri-book-2-line',        'evidence' => 'Photo of the hardbound thesis with student and adviser',                       'status' => $hardboundStatus,         'file_path' => $milestoneFiles['hardbound_submitted']['file_path']     ?? '', 'filename' => $milestoneFiles['hardbound_submitted']['original_filename']     ?? '', 'uploaded_at' => $milestoneFiles['hardbound_submitted']['uploaded_at']     ?? '', 'approved_at' => $milestone['hardbound_submitted_approved_at']     ?? ''],
            ['key' => 'applied_copyright',   'label' => 'Copyright Applied',        'icon' => 'ri-copyright-line',     'evidence' => 'Certificate of copyright with copyright number',                               'status' => $appliedCopyrightStatus,  'file_path' => $milestoneFiles['applied_copyright']['file_path']       ?? '', 'filename' => $milestoneFiles['applied_copyright']['original_filename']       ?? '', 'uploaded_at' => $milestoneFiles['applied_copyright']['uploaded_at']       ?? '', 'approved_at' => $milestone['applied_copyright_approved_at']       ?? ''],
            ['key' => 'research_presented',  'label' => 'Research Presented',       'icon' => 'ri-slideshow-3-line',   'evidence' => 'Letter of acceptance and certificate of recognition',                          'status' => $researchPresentedStatus, 'file_path' => $milestoneFiles['research_presented']['file_path']      ?? '', 'filename' => $milestoneFiles['research_presented']['original_filename']      ?? '', 'uploaded_at' => $milestoneFiles['research_presented']['uploaded_at']      ?? '', 'approved_at' => $milestone['research_presented_approved_at']      ?? ''],
            ['key' => 'research_published',  'label' => 'Research Published',       'icon' => 'ri-newspaper-line',     'evidence' => 'Letter of acceptance, copy of published article, and DOI link (if possible)',  'status' => $researchPublishedStatus, 'file_path' => $milestoneFiles['research_published']['file_path']      ?? '', 'filename' => $milestoneFiles['research_published']['original_filename']      ?? '', 'uploaded_at' => $milestoneFiles['research_published']['uploaded_at']      ?? '', 'approved_at' => $milestone['research_published_approved_at']      ?? ''],
            ['key' => 'copyright_approved',  'label' => 'Copyright Approved',       'icon' => 'ri-shield-check-line',  'evidence' => 'Certificate',                                                                  'status' => ($copyrightApprovedStatus === 'completed') ? 'approved' : $copyrightApprovedStatus, 'file_path' => $milestoneFiles['copyright_approved']['file_path'] ?? '', 'filename' => $milestoneFiles['copyright_approved']['original_filename'] ?? '', 'uploaded_at' => $milestoneFiles['copyright_approved']['uploaded_at'] ?? '', 'approved_at' => $milestone['copyright_approved_approved_at'] ?? ''],
        ];

        $groups[] = [
            'group_id'                      => $groupId,
            'group_name'                    => $ag['group_name'],
            'research_title'                => $ag['research_title'] ?? '',
            'title_status'                  => $titleStatus,
            'research_id'                   => $researchId,
            'is_leader'                     => $ag['is_leader'],
            'adviser_name'                  => $adviserName,
            'progress'                      => $progress,
            'approved_count'                => $approvedCount,
            'milestone_types'               => $milestoneTypesArr,
            'upload_map'                    => $uploadMap,
            'assigned_sdgs'                 => $assignedSdgs,
            'assigned_thrusts'              => $assignedThrusts,
            'title_status_raw'              => $titleStatus,
            'proposal_status'               => $proposalStatus,
            'final_defense_status'          => $finalDefenseStatus,
            'hardbound_submitted_status'    => $hardboundStatus,
            'applied_copyright_status'      => $appliedCopyrightStatus,
            'research_presented_status'     => $researchPresentedStatus,
            'research_published_status'     => $researchPublishedStatus,
            'copyright_approved_status'     => $copyrightApprovedStatus,
            'urec_form_status'              => $urecFormStatus,
            'urec_clearance_status'         => $urecClearanceStatus,
            'is_manuscript_unlocked'        => ($copyrightApprovedStatus === 'completed'),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/requirements.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>

<?php include("templates/aside_student.html"); ?>

<div class="wrapper">
    <h1 class="req-title">Requirements</h1>

    <?php if (empty($groups)): ?>
        <div class="no-groups-message">
            <i class="ri-folder-unknow-line"></i>
            <h3>No Groups Yet</h3>
            <p>You have not been assigned to any group.</p>
        </div>
    <?php else: ?>
        <div class="group-cards-wrapper">
            <?php foreach ($groups as $grp): ?>
                <div class="group-card">
                    <div class="group-header" onclick="toggleGroupDetails(this)">
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
                        <i class="ri-arrow-right-s-line expand-icon"></i>
                    </div>

                    <div class="group-details" style="display:none;">

                        <div class="title-section" style="margin-bottom:10px;padding:12px;border:1px solid #ddd;border-radius:6px;background:#f8f9fa;">
                            <strong><i class="ri-book-mark-line"></i> Research Title:</strong>
                            <?php if (!empty($grp['research_title'])): ?>
                                <p style="margin:5px 0 0;font-size:14px;"><?= htmlspecialchars($grp['research_title']) ?></p>
                                <?php if ($grp['title_status'] === 'approved'): ?>
                                    <span class="status-badge status-approved">Approved</span>
                                <?php elseif ($grp['title_status'] === 'pending_approval'): ?>
                                    <span class="status-badge status-pending">Pending Approval</span>
                                <?php elseif ($grp['title_status'] === 'rejected'): ?>
                                    <span class="status-badge status-rejected">Rejected</span>
                                <?php else: ?>
                                    <span class="status-badge status-missing">Missing</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#999;font-size:14px;">Not set yet</span>
                                <span class="status-badge status-missing">Missing</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($grp['assigned_sdgs']) || !empty($grp['assigned_thrusts'])): ?>
                        <div class="assignments-banner" style="margin-bottom:16px;">
                            <div class="assignments-grid">
                                <?php if (!empty($grp['assigned_sdgs'])): ?>
                                <div class="assignment-section">
                                    <h3><i class="ri-global-line"></i> UN SDGs</h3>
                                    <div class="assignment-tags">
                                        <?php foreach ($grp['assigned_sdgs'] as $sdg): ?>
                                            <span class="assignment-tag"><i class="ri-checkbox-circle-fill"></i> <?= htmlspecialchars($sdg) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($grp['assigned_thrusts'])): ?>
                                <div class="assignment-section">
                                    <h3><i class="ri-flashlight-line"></i> Research Thrusts</h3>
                                    <div class="assignment-tags">
                                        <?php foreach ($grp['assigned_thrusts'] as $thrust): ?>
                                            <span class="assignment-tag thrust"><i class="ri-focus-3-line"></i> <?= htmlspecialchars($thrust) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <h4 class="milestone-section-title"><i class="ri-flag-line"></i> Research Milestones</h4>

                        <?php if ($grp['title_status'] !== 'approved'): ?>
                            <div class="milestone-locked-notice">
                                <i class="ri-lock-line"></i>
                                <h4>Milestone Uploads Locked</h4>
                                <p>Your research title must be approved by the coordinator before you can upload milestone documents.</p>
                            </div>
                        <?php else: ?>
                            <div class="milestone-cards-grid">
                                <?php foreach ($grp['milestone_types'] as $m):
                                    $hasFile   = !empty($m['file_path']);
                                    $rawStatus = $m['status'] ?? 'missing';
                                    $sc = 'status-missing'; $st = 'Not Uploaded';
                                    if ($hasFile) {
                                        if ($rawStatus === 'approved')       { $sc = 'status-approved'; $st = 'Approved'; }
                                        elseif ($rawStatus === 'endorsed')   { $sc = 'status-endorsed'; $st = 'Endorsed'; }
                                        elseif ($rawStatus === 'rejected')   { $sc = 'status-rejected'; $st = 'Rejected'; }
                                        else                                 { $sc = 'status-pending';  $st = 'Pending Review'; }
                                    }
                                    $comment = $m['comment'] ?? '';
                                ?>
                                    <div class="milestone-card">
                                        <div class="milestone-card-header">
                                            <i class="<?= $m['icon'] ?>"></i>
                                            <span class="mc-label"><?= $m['label'] ?></span>
                                            <span class="status-badge <?= $sc ?>"><?= $st ?></span>
                                        </div>
                                        <div class="milestone-card-evidence"><i class="ri-file-list-line"></i> <?= htmlspecialchars($m['evidence']) ?></div>
                                        <?php if ($hasFile): ?>
                                            <div class="milestone-card-file">
                                                <i class="ri-file-pdf-line"></i>
                                                <span class="mf-name"><?= htmlspecialchars($m['filename']) ?></span>
                                                <span class="mf-date"><?= date("M d, Y", strtotime($m['uploaded_at'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($m['approved_at'])): ?>
                                            <div class="milestone-approved-date">
                                                <i class="ri-checkbox-circle-fill"></i>
                                                Approved: <?= date("M d, Y", strtotime($m['approved_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($comment): ?>
                                            <div class="mc-comment-box"><i class="ri-chat-3-line"></i><strong><?= htmlspecialchars($grp['adviser_name']) ?>:</strong> <?= htmlspecialchars($comment) ?></div>
                                        <?php endif; ?>
                                        <div class="milestone-card-actions">
                                            <?php if ($rawStatus === 'approved' || $rawStatus === 'endorsed'): ?>
                                                <span class="mc-approved-label"><i class="ri-checkbox-circle-fill"></i> <?= $rawStatus === 'endorsed' ? 'Endorsed — awaiting coordinator' : 'Approved — locked' ?></span>
                                            <?php else: ?>
                                                <button class="mc-upload-btn <?= $hasFile ? 'replace' : '' ?>"
                                                    onclick="openMilestoneModal('<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['evidence'], ENT_QUOTES) ?>', <?= $grp['group_id'] ?>)">
                                                    <i class="ri-upload-line"></i> <?= $hasFile ? 'Replace' : 'Upload' ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h4 class="milestone-section-title" style="margin-top:20px;"><i class="ri-file-text-line"></i> Manuscript Submission</h4>

                        <?php if (!$grp['is_manuscript_unlocked']): ?>
                            <div class="manuscript-locked-notice">
                                <i class="ri-lock-line"></i>
                                <span>The <strong>Full Manuscript</strong> submission is locked. Your adviser must approve the <strong>Copyright Approved</strong> milestone first.</span>
                            </div>
                        <?php endif; ?>

                        <?php
                        $task       = "Full Manuscript (Chapter 1-5)";
                        $taskUpload = $grp['upload_map'][$task] ?? null;
                        $mStatus    = $taskUpload['status'] ?? 'missing';
                        $mComment   = $taskUpload['comment'] ?? '';
                        $mStatusClass = 'status-missing'; $mStatusText = 'Missing';
                        if ($mStatus === 'approved')      { $mStatusClass = 'status-approved'; $mStatusText = 'Approved'; }
                        elseif ($mStatus === 'rejected')  { $mStatusClass = 'status-rejected'; $mStatusText = 'Rejected'; }
                        elseif ($mStatus === 'pending')   { $mStatusClass = 'status-pending';  $mStatusText = 'Pending'; }
                        $isTaskEnabled = in_array("Completed", $enabledStatuses);
                        $isActive      = $grp['is_manuscript_unlocked'] && $isTaskEnabled && ($mStatus !== 'approved');
                        $isApproved    = ($mStatus === 'approved');
                        ?>
                        <div class="req-card <?= (!$isActive && !$isApproved) ? 'locked' : '' ?>" style="margin-bottom:15px;">
                            <div class="req-header">
                                <span class="req-title-text"><?= $task ?></span>
                                <?php if (!$grp['is_manuscript_unlocked']): ?>
                                    <button class="add-btn disabled" disabled><i class="ri-lock-line"></i> Locked</button>
                                <?php elseif (!$isTaskEnabled): ?>
                                    <button class="add-btn disabled" disabled><i class="ri-lock-line"></i> Disabled</button>
                                <?php elseif ($isApproved): ?>
                                    <button class="add-btn disabled" disabled>Completed</button>
                                <?php else: ?>
                                    <button class="add-btn">+ Add Work</button>
                                <?php endif; ?>
                            </div>
                            <div class="req-body">
                                <div class="left-info">
                                    <i class="ri-file-list-3-line"></i>
                                    <span class="status-text">Status: <span class="status-badge <?= $mStatusClass ?>"><?= $mStatusText ?></span></span>
                                </div>
                                <form action="php/upload_handler.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="task_name" value="<?= $task ?>">
                                    <input type="hidden" name="group_id" value="<?= $grp['group_id'] ?>">
                                    <label class="choose-file-btn">
                                        Choose File
                                        <input type="file" name="file_upload" <?= $isActive ? '' : 'disabled' ?> onchange="this.form.submit()">
                                    </label>
                                </form>
                            </div>
                            <div class="add-comment"
                                data-task="<?= htmlspecialchars($task) ?>"
                                data-comment="<?= htmlspecialchars($mComment) ?>"
                                data-filename="<?= $taskUpload ? htmlspecialchars($taskUpload['original_filename']) : '' ?>"
                                data-filedate="<?= $taskUpload ? date("M d, Y", strtotime($taskUpload['uploaded_at'])) : '' ?>"
                                data-filepath="<?= $taskUpload ? htmlspecialchars($taskUpload['file_path']) : '' ?>"
                                data-adviser="<?= htmlspecialchars($grp['adviser_name']) ?>">
                                View Comment
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <button id="aiChatButton">AI Chat</button>
    <div id="aiChatbox">
        <iframe src="https://ragreecorassistant.onrender.com/" frameborder="0"></iframe>
    </div>
</div>

<div class="modal" id="commentModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="modalTitle">Comment</h3>
        <div class="modal-body">
            <div class="comment-section">
                <p class="comment-empty">No comments yet.</p>
                <div class="comment-with-data" style="display:none;">
                    <div class="comment-text-wrapper">
                        <p><span class="adviser-name"></span><span class="comment-text"></span></p>
                    </div>
                    <a href="#" class="file-info-modal" id="fileDownloadLink" download>
                        <i class="ri-folder-line"></i>
                        <span class="file-name"></span>
                        <span class="file-date"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="upload-modal-overlay" id="milestoneUploadOverlay">
    <div class="upload-modal-box">
        <h3 id="milestoneModalTitle"><i class="ri-upload-cloud-2-line"></i> Upload Document</h3>
        <p class="upload-modal-evidence" id="milestoneModalEvidence"></p>
        <div class="upload-modal-dropzone" id="milestoneDropzone">
            <i class="ri-file-pdf-line"></i>
            <span>Click to browse or drag &amp; drop your file here</span>
            <small>PDF only · Max <?= $maxUploadSizeMB ?>MB</small>
            <input type="file" id="milestoneModalFile" accept=".pdf">
        </div>
        <div class="upload-modal-selected" id="milestoneSelectedInfo">
            <i class="ri-file-pdf-2-line"></i>
            <span class="sel-name" id="milestoneSelectedName"></span>
            <span class="sel-size" id="milestoneSelectedSize"></span>
        </div>
        <div class="upload-modal-date-group">
            <label class="upload-modal-date-label" for="milestoneApprovedDate">
                <i class="ri-calendar-check-line"></i> Date Approved <span class="upload-modal-date-required">*</span>
            </label>
            <input type="date" id="milestoneApprovedDate" class="upload-modal-date-input" max="<?= date('Y-m-d') ?>">
            <small class="upload-modal-date-hint">Enter the date this milestone was officially approved</small>
        </div>
        <div class="upload-modal-actions">
            <button class="upload-modal-cancel" onclick="closeMilestoneModal()">Cancel</button>
            <button class="upload-modal-submit" id="milestoneSubmitBtn" onclick="submitStudentMilestone()" disabled>
                <i class="ri-upload-line"></i> Upload
            </button>
        </div>
    </div>
</div>

<script>
function toggleGroupDetails(header) {
    const details = header.nextElementSibling;
    const icon    = header.querySelector('.expand-icon');
    const isOpen  = details.style.display === 'block';
    details.style.display = isOpen ? 'none' : 'block';
    icon.style.transform  = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    const iconMap = { success: 'ri-checkbox-circle-line', error: 'ri-error-warning-line', warning: 'ri-alert-line' };
    toast.innerHTML = `<i class="${iconMap[type] || iconMap.success}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.add('removing'); setTimeout(() => toast.remove(), 300); }, 3000);
}

const modal = document.getElementById('commentModal');
document.querySelectorAll('.add-comment').forEach(btn => {
    btn.addEventListener('click', () => {
        const task     = btn.dataset.task;
        const comment  = btn.dataset.comment;
        const filename = btn.dataset.filename;
        const filedate = btn.dataset.filedate;
        const filepath = btn.dataset.filepath;
        const adviser  = btn.dataset.adviser;
        document.getElementById('modalTitle').textContent = task + " – Adviser Comment";
        if (comment && comment.trim() !== "") {
            modal.querySelector('.adviser-name').textContent = adviser + ": ";
            modal.querySelector('.comment-text').textContent = comment;
            document.getElementById('fileDownloadLink').href = filepath;
            modal.querySelector('.file-name').textContent = filename;
            modal.querySelector('.file-date').textContent = filedate;
            modal.querySelector('.comment-with-data').style.display = "block";
            modal.querySelector('.comment-empty').style.display = "none";
        } else {
            modal.querySelector('.comment-empty').style.display = "block";
            modal.querySelector('.comment-with-data').style.display = "none";
        }
        modal.classList.add('open');
    });
});
modal.querySelector('.modal-overlay').onclick =
modal.querySelector('.modal-close').onclick = () => modal.classList.remove('open');

let activeMilestoneType = null;
let activeMilestoneGroupId = null;

function openMilestoneModal(type, label, evidence, groupId) {
    activeMilestoneType    = type;
    activeMilestoneGroupId = groupId;
    document.getElementById('milestoneModalTitle').innerHTML = '<i class="ri-upload-cloud-2-line"></i> Upload ' + label;
    document.getElementById('milestoneModalEvidence').innerHTML = '<strong>Required evidence:</strong> ' + evidence;
    document.getElementById('milestoneModalFile').value = '';
    document.getElementById('milestoneApprovedDate').value = '';
    document.getElementById('milestoneSelectedInfo').classList.remove('visible');
    document.getElementById('milestoneSubmitBtn').disabled = true;
    document.getElementById('milestoneUploadOverlay').classList.add('open');
}

function closeMilestoneModal() {
    document.getElementById('milestoneUploadOverlay').classList.remove('open');
    activeMilestoneType = null;
    activeMilestoneGroupId = null;
}

document.getElementById('milestoneUploadOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeMilestoneModal();
});

document.getElementById('milestoneModalFile').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const sizeKB = (file.size / 1024).toFixed(0);
    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
    document.getElementById('milestoneSelectedName').textContent = file.name;
    document.getElementById('milestoneSelectedSize').textContent = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
    document.getElementById('milestoneSelectedInfo').classList.add('visible');
    document.getElementById('milestoneSubmitBtn').disabled = false;
});

const dropzone = document.getElementById('milestoneDropzone');
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('milestoneModalFile').files = dt.files;
    document.getElementById('milestoneModalFile').dispatchEvent(new Event('change'));
});

async function submitStudentMilestone() {
    const fileInput  = document.getElementById('milestoneModalFile');
    const dateInput  = document.getElementById('milestoneApprovedDate');
    const file       = fileInput.files[0];
    const approvedAt = dateInput.value;

    if (!file) { showToast('Please select a file', 'error'); return; }
    if (file.type !== 'application/pdf') { showToast('Only PDF files are allowed', 'error'); return; }
    if (file.size > <?= $maxUploadSizeMB ?> * 1024 * 1024) { showToast('File size must be less than <?= $maxUploadSizeMB ?>MB', 'error'); return; }
    if (!approvedAt) { showToast('Please enter the date approved', 'error'); dateInput.focus(); return; }

    const formData = new FormData();
    formData.append('action', 'upload_milestone');
    formData.append('milestone_type', activeMilestoneType);
    formData.append('group_id', activeMilestoneGroupId);
    formData.append('approved_at', approvedAt);
    formData.append('file', file);

    const btn = document.getElementById('milestoneSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite;"></i> Uploading...';

    try {
        const res  = await fetch('requirements.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'Uploaded successfully!', 'success');
            closeMilestoneModal();
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Upload failed', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-upload-line"></i> Upload';
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-upload-line"></i> Upload';
    }
}

(function() {
    const snapshot = {};
    <?php foreach ($groups as $grp): ?>
        snapshot[<?= $grp['group_id'] ?>] = {
            title_status:                '<?= $grp['title_status'] ?>',
            proposal_status:             '<?= $grp['proposal_status'] ?>',
            final_defense_status:        '<?= $grp['final_defense_status'] ?>',
            hardbound_submitted_status:  '<?= $grp['hardbound_submitted_status'] ?>',
            applied_copyright_status:    '<?= $grp['applied_copyright_status'] ?>',
            research_presented_status:   '<?= $grp['research_presented_status'] ?>',
            research_published_status:   '<?= $grp['research_published_status'] ?>',
            copyright_approved_status:   '<?= $grp['copyright_approved_status'] ?>',
            urec_form_status:            '<?= $grp['urec_form_status'] ?>',
            urec_clearance_status:       '<?= $grp['urec_clearance_status'] ?>',
        };
    <?php endforeach; ?>

    setInterval(async () => {
        try {
            const fd = new FormData();
            fd.append('action', 'poll_status');
            const data = await (await fetch('requirements.php', { method: 'POST', body: fd })).json();
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

document.getElementById('aiChatButton').addEventListener('click', function () {
    document.getElementById('aiChatbox').classList.toggle('open');
});
document.addEventListener('click', function (e) {
    const box    = document.getElementById('aiChatbox');
    const button = document.getElementById('aiChatButton');
    if (box.classList.contains('open') && !box.contains(e.target) && !button.contains(e.target)) {
        box.classList.remove('open');
    }
});

const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/notifications.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>