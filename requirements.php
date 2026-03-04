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

    $school_id = $_SESSION['school_id'];
    $studentStmt = $con->prepare("SELECT group_id, is_leader FROM student WHERE school_id = :school_id LIMIT 1");
    $studentStmt->execute(['school_id' => $school_id]);
    $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentInfo || !$studentInfo['group_id']) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to a group']);
        exit;
    }

    $group_id = $studentInfo['group_id'];

    $titleStmt = $con->prepare("SELECT title_status FROM groups WHERE id = :group_id");
    $titleStmt->execute(['group_id' => $group_id]);
    $groupRow = $titleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$groupRow || $groupRow['title_status'] !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Research title must be approved before uploading milestone documents']);
        exit;
    }

    $milestone_type = $_POST['milestone_type'] ?? '';
    $valid_types = [
        'proposal', 'urec_form', 'urec_clearance', 'final_defense',
        'applied_copyright', 'research_presented', 'research_published', 'copyright_approved'
    ];

    if (!in_array($milestone_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid milestone type']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }

    $file = $_FILES['file'];
    $maxUploadSizeMB = getSettingInt($con, 'max_upload_size', 10);
    $maxUploadSizeBytes = $maxUploadSizeMB * 1024 * 1024;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
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

    try {
        if ($milestone_type === 'urec_form' || $milestone_type === 'urec_clearance') {
            $docType = ($milestone_type === 'urec_form') ? 'UREC Form' : 'UREC Clearance';

            $existsStmt = $con->prepare("SELECT id FROM urec_documents WHERE group_id = :group_id AND document_type = :doc_type ORDER BY uploaded_at DESC LIMIT 1");
            $existsStmt->execute(['group_id' => $group_id, 'doc_type' => $docType]);
            $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $con->prepare("UPDATE urec_documents SET file_path = :file_path, original_filename = :original_filename, status = 'pending', uploaded_at = NOW(), comment = NULL WHERE id = :id");
                $stmt->execute(['file_path' => $filePath, 'original_filename' => $originalFilename, 'id' => $existing['id']]);
            } else {
                $stmt = $con->prepare("INSERT INTO urec_documents (group_id, adviser_id, document_type, file_path, original_filename, status, uploaded_at) VALUES (:group_id, NULL, :document_type, :file_path, :original_filename, 'pending', NOW())");
                $stmt->execute(['group_id' => $group_id, 'document_type' => $docType, 'file_path' => $filePath, 'original_filename' => $originalFilename]);
            }
        } else {
            $stmt = $con->prepare("
                UPDATE groups
                SET {$milestone_type}_file_path = :file_path,
                    {$milestone_type}_original_filename = :original_filename,
                    {$milestone_type}_uploaded_at = NOW()
                WHERE id = :group_id
            ");
            $stmt->execute(['file_path' => $filePath, 'original_filename' => $originalFilename, 'group_id' => $group_id]);

            $milestoneColumn = $milestone_type . '_status';
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $group_id]);
            if ($checkStmt->rowCount() > 0) {
                $stmt = $con->prepare("UPDATE group_milestones SET {$milestoneColumn} = 'pending', updated_at = NOW() WHERE group_id = :group_id");
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$milestoneColumn}, created_at, updated_at) VALUES (:group_id, 'pending', NOW(), NOW())");
            }
            $stmt->execute(['group_id' => $group_id]);
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
    $school_id = $_SESSION['school_id'];
    $studentStmt = $con->prepare("SELECT group_id FROM student WHERE school_id = :school_id LIMIT 1");
    $studentStmt->execute(['school_id' => $school_id]);
    $row = $studentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['group_id']) { echo json_encode([]); exit; }
    $gid = $row['group_id'];
    $g = $con->prepare("SELECT title_status FROM groups WHERE id = :id");
    $g->execute(['id' => $gid]);
    $gRow = $g->fetch(PDO::FETCH_ASSOC);
    $m = $con->prepare("SELECT proposal_status, final_defense_status, applied_copyright_status, research_presented_status, research_published_status, copyright_approved_status FROM group_milestones WHERE group_id = :id");
    $m->execute(['id' => $gid]);
    $mRow = $m->fetch(PDO::FETCH_ASSOC) ?: [];
    $uf = $con->prepare("SELECT document_type, status FROM urec_documents WHERE group_id = :id ORDER BY uploaded_at DESC");
    $uf->execute(['id' => $gid]);
    $urecRows = $uf->fetchAll(PDO::FETCH_ASSOC);
    $urecMap = [];
    foreach ($urecRows as $ur) if (!isset($urecMap[$ur['document_type']])) $urecMap[$ur['document_type']] = $ur['status'];
    $mapStatus = function($v) { return match($v) { 'completed' => 'approved', 'pending' => 'pending', 'rejected' => 'rejected', default => 'missing' }; };
    $rawTitleStatus = $gRow['title_status'] ?? '';
    $mappedTitleStatus = ($rawTitleStatus === 'approved') ? 'approved' : (($rawTitleStatus === 'pending_approval') ? 'pending_approval' : (($rawTitleStatus === 'rejected') ? 'rejected' : 'missing'));
    $rawCopyright = $mRow['copyright_approved_status'] ?? '';
    $mappedCopyright = $rawCopyright ?: 'missing';
    echo json_encode([
        'title_status' => $mappedTitleStatus,
        'proposal_status' => $mapStatus($mRow['proposal_status'] ?? ''),
        'final_defense_status' => $mapStatus($mRow['final_defense_status'] ?? ''),
        'applied_copyright_status' => $mapStatus($mRow['applied_copyright_status'] ?? ''),
        'research_presented_status' => $mapStatus($mRow['research_presented_status'] ?? ''),
        'research_published_status' => $mapStatus($mRow['research_published_status'] ?? ''),
        'copyright_approved_status' => $mappedCopyright,
        'urec_form_status' => $urecMap['UREC Form'] ?? 'missing',
        'urec_clearance_status' => $urecMap['UREC Clearance'] ?? 'missing',
    ]);
    exit;
}

$school_id = $_SESSION['school_id'];

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
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$studentStmt = $con->prepare("
    SELECT s.group_id, s.is_leader, a.name as adviser_name, g.research_title, g.title_status
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    LEFT JOIN advisor a ON g.adviser_id = a.id
    WHERE s.school_id = :school_id
    LIMIT 1
");
$studentStmt->execute(['school_id' => $school_id]);
$studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);

$adviserName = $studentInfo && !empty($studentInfo['adviser_name']) ? $studentInfo['adviser_name'] : 'Adviser';
$group_id = $studentInfo['group_id'];
$is_leader = $studentInfo['is_leader'];
$groupTitle = $studentInfo['research_title'] ?? '';
$titleStatus = $studentInfo['title_status'] ?? 'missing';

$urecFormStatus = 'missing';
$urecFormFile = null;
$urecClearanceStatus = 'missing';
$urecClearanceFile = null;

if ($group_id) {
    $urecFormStmt = $con->prepare("SELECT status, original_filename, file_path, uploaded_at, comment FROM urec_documents WHERE group_id = :group_id AND document_type = 'UREC Form' ORDER BY uploaded_at DESC LIMIT 1");
    $urecFormStmt->execute(['group_id' => $group_id]);
    $urecFormRow = $urecFormStmt->fetch(PDO::FETCH_ASSOC);
    if ($urecFormRow) { $urecFormStatus = $urecFormRow['status']; $urecFormFile = $urecFormRow; }

    $urecClearanceStmt = $con->prepare("SELECT status, original_filename, file_path, uploaded_at, comment FROM urec_documents WHERE group_id = :group_id AND document_type = 'UREC Clearance' ORDER BY uploaded_at DESC LIMIT 1");
    $urecClearanceStmt->execute(['group_id' => $group_id]);
    $urecClearanceRow = $urecClearanceStmt->fetch(PDO::FETCH_ASSOC);
    if ($urecClearanceRow) { $urecClearanceStatus = $urecClearanceRow['status']; $urecClearanceFile = $urecClearanceRow; }
}

$proposalStatus = 'missing';
$finalDefenseStatus = 'missing';
$copyrightStatus = 'missing';
$appliedCopyrightStatus = 'missing';
$researchPresentedStatus = 'missing';
$researchPublishedStatus = 'missing';
$copyrightApprovedStatus = 'missing';
$milestoneRow = null;

if ($group_id) {
    $milestoneStmt = $con->prepare("SELECT proposal_status, final_defense_status, copyright_status, applied_copyright_status, research_presented_status, research_published_status, copyright_approved_status FROM group_milestones WHERE group_id = :group_id");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestone = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

    if ($milestone) {
        $milestoneRow = $milestone;
        $mapStatus = fn($v) => match($v) { 'completed' => 'approved', 'pending' => 'pending', 'rejected' => 'rejected', default => 'missing' };
        $proposalStatus          = $mapStatus($milestone['proposal_status']);
        $finalDefenseStatus      = $mapStatus($milestone['final_defense_status']);
        $copyrightStatus         = $mapStatus($milestone['copyright_status']);
        $appliedCopyrightStatus  = $mapStatus($milestone['applied_copyright_status']);
        $researchPresentedStatus = $mapStatus($milestone['research_presented_status']);
        $researchPublishedStatus = $mapStatus($milestone['research_published_status']);
        $copyrightApprovedStatus = $milestone['copyright_approved_status'] ?? 'missing';
    }
}

$isManuscriptUnlocked = ($copyrightApprovedStatus === 'completed');

$groupFilesStmt = $con->prepare("
    SELECT proposal_file_path, proposal_original_filename, proposal_uploaded_at,
           final_defense_file_path, final_defense_original_filename, final_defense_uploaded_at,
           applied_copyright_file_path, applied_copyright_original_filename, applied_copyright_uploaded_at,
           research_presented_file_path, research_presented_original_filename, research_presented_uploaded_at,
           research_published_file_path, research_published_original_filename, research_published_uploaded_at,
           copyright_approved_file_path, copyright_approved_original_filename, copyright_approved_uploaded_at
    FROM groups WHERE id = :group_id
");
$groupFilesStmt->execute(['group_id' => $group_id]);
$groupFiles = $groupFilesStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$sdgsStmt = $con->prepare("SELECT us.name FROM un_sdgs us JOIN group_sdgs gs ON us.id = gs.sdg_id WHERE gs.group_id = :group_id ORDER BY us.name");
$sdgsStmt->execute(['group_id' => $group_id]);
$assignedSdgs = $sdgsStmt->fetchAll(PDO::FETCH_COLUMN);

$thrustsStmt = $con->prepare("SELECT rt.name FROM research_thrusts rt JOIN group_thrusts gt ON rt.id = gt.thrust_id WHERE gt.group_id = :group_id ORDER BY rt.name");
$thrustsStmt->execute(['group_id' => $group_id]);
$assignedThrusts = $thrustsStmt->fetchAll(PDO::FETCH_COLUMN);

$enabledStatusesStmt = $con->query("SELECT name FROM research_statuses WHERE is_active = TRUE");
$enabledStatuses = $enabledStatusesStmt->fetchAll(PDO::FETCH_COLUMN);

$uploadsStmt = $con->prepare("SELECT u.task_name, u.status, u.comment, u.file_path, u.original_filename, u.uploaded_at FROM uploads u JOIN student s ON u.school_id = s.school_id WHERE s.group_id = :group_id ORDER BY u.uploaded_at DESC");
$uploadsStmt->execute(['group_id' => $group_id]);
$uploads = $uploadsStmt->fetchAll(PDO::FETCH_ASSOC);

$uploadMap = [];
foreach ($uploads as $upload) {
    if (!isset($uploadMap[$upload['task_name']])) $uploadMap[$upload['task_name']] = $upload;
}

$maxUploadSizeMB = getSettingInt($con, 'max_upload_size', 10);

$milestoneTypes = [
    ['key' => 'proposal',           'label' => 'Proposal',              'icon' => 'ri-file-text-line',     'evidence' => 'Rating sheets (graded)',                                                            'status' => $proposalStatus,          'file_path' => $groupFiles['proposal_file_path'] ?? '',           'filename' => $groupFiles['proposal_original_filename'] ?? '',           'uploaded_at' => $groupFiles['proposal_uploaded_at'] ?? ''],
    ['key' => 'urec_form',          'label' => 'UREC Processing',       'icon' => 'ri-file-shield-line',   'evidence' => 'Received letter of intent',                                                         'status' => $urecFormStatus,          'file_path' => $urecFormFile['file_path'] ?? '',                   'filename' => $urecFormFile['original_filename'] ?? '',                  'uploaded_at' => $urecFormFile['uploaded_at'] ?? '',    'comment' => $urecFormFile['comment'] ?? ''],
    ['key' => 'urec_clearance',     'label' => 'UREC Clearance',        'icon' => 'ri-file-shield-2-line', 'evidence' => 'Copy of the clearance',                                                             'status' => $urecClearanceStatus,     'file_path' => $urecClearanceFile['file_path'] ?? '',              'filename' => $urecClearanceFile['original_filename'] ?? '',             'uploaded_at' => $urecClearanceFile['uploaded_at'] ?? '', 'comment' => $urecClearanceFile['comment'] ?? ''],
    ['key' => 'final_defense',      'label' => 'Final Defense',         'icon' => 'ri-presentation-line',  'evidence' => 'Final defense rating sheet (graded)',                                               'status' => $finalDefenseStatus,      'file_path' => $groupFiles['final_defense_file_path'] ?? '',       'filename' => $groupFiles['final_defense_original_filename'] ?? '',      'uploaded_at' => $groupFiles['final_defense_uploaded_at'] ?? ''],
    ['key' => 'applied_copyright',  'label' => 'Applied for Copyright', 'icon' => 'ri-copyright-line',     'evidence' => 'Certificate of copyright with copyright number',                                    'status' => $appliedCopyrightStatus,  'file_path' => $groupFiles['applied_copyright_file_path'] ?? '',   'filename' => $groupFiles['applied_copyright_original_filename'] ?? '',  'uploaded_at' => $groupFiles['applied_copyright_uploaded_at'] ?? ''],
    ['key' => 'research_presented', 'label' => 'Research Presented',    'icon' => 'ri-slideshow-3-line',   'evidence' => 'Letter of acceptance and certificate of recognition',                               'status' => $researchPresentedStatus, 'file_path' => $groupFiles['research_presented_file_path'] ?? '', 'filename' => $groupFiles['research_presented_original_filename'] ?? '', 'uploaded_at' => $groupFiles['research_presented_uploaded_at'] ?? ''],
    ['key' => 'research_published', 'label' => 'Research Published',    'icon' => 'ri-newspaper-line',     'evidence' => 'Letter of acceptance, copy of published article, and DOI link (if possible)',       'status' => $researchPublishedStatus, 'file_path' => $groupFiles['research_published_file_path'] ?? '', 'filename' => $groupFiles['research_published_original_filename'] ?? '', 'uploaded_at' => $groupFiles['research_published_uploaded_at'] ?? ''],
    ['key' => 'copyright_approved', 'label' => 'Copyright Approved',    'icon' => 'ri-shield-check-line',  'evidence' => 'Certificate',                                                                       'status' => ($copyrightApprovedStatus === 'completed') ? 'approved' : $copyrightApprovedStatus, 'file_path' => $groupFiles['copyright_approved_file_path'] ?? '', 'filename' => $groupFiles['copyright_approved_original_filename'] ?? '', 'uploaded_at' => $groupFiles['copyright_approved_uploaded_at'] ?? ''],
];
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

    <?php if (!empty($assignedSdgs) || !empty($assignedThrusts)): ?>
    <div class="assignments-banner">
        <h2><i class="ri-bookmark-line"></i> Your Research Assignments</h2>
        <div class="assignments-grid">
            <?php if (!empty($assignedSdgs)): ?>
            <div class="assignment-section">
                <h3><i class="ri-global-line"></i> UN SDGs</h3>
                <div class="assignment-tags">
                    <?php foreach ($assignedSdgs as $sdg): ?>
                        <span class="assignment-tag"><i class="ri-checkbox-circle-fill"></i> <?= htmlspecialchars($sdg) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($assignedThrusts)): ?>
            <div class="assignment-section">
                <h3><i class="ri-flashlight-line"></i> Research Thrusts</h3>
                <div class="assignment-tags">
                    <?php foreach ($assignedThrusts as $thrust): ?>
                        <span class="assignment-tag thrust"><i class="ri-focus-3-line"></i> <?= htmlspecialchars($thrust) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <h2 class="milestone-section-title"><i class="ri-flag-line"></i> Research Milestones</h2>

    <?php
    $progressItems = [
        "Title"                  => ['status' => $titleStatus],
        "Proposal"               => ['status' => $proposalStatus],
        "UREC Processing"        => ['status' => $urecFormStatus],
        "UREC Clearance"         => ['status' => $urecClearanceStatus],
        "Final Defense"          => ['status' => $finalDefenseStatus],
        "Applied for Copyright"  => ['status' => $appliedCopyrightStatus],
        "Research Presented"     => ['status' => $researchPresentedStatus],
        "Research Published"     => ['status' => $researchPublishedStatus],
        "Copyright Approved"     => ['status' => ($copyrightApprovedStatus === 'completed') ? 'approved' : $copyrightApprovedStatus],
    ];
    ?>

    <div class="milestone-carousel-wrapper">
        <button class="carousel-arrow carousel-prev" id="milestonePrev" aria-label="Previous"><i class="ri-arrow-left-s-line"></i></button>
        <div class="milestone-track-outer">
            <div class="milestone-track" id="milestoneTrack">
                <?php foreach ($progressItems as $label => $data):
                    $sc = 'status-missing'; $st = 'Missing';
                    if ($data['status'] === 'pending')   { $sc = 'status-pending';  $st = 'Pending'; }
                    elseif ($data['status'] === 'approved') { $sc = 'status-approved'; $st = 'Approved'; }
                    elseif ($data['status'] === 'rejected') { $sc = 'status-rejected'; $st = 'Rejected'; }
                ?>
                    <div class="milestone-chip">
                        <span class="milestone-chip-label"><?= $label ?></span>
                        <span class="status-badge <?= $sc ?>"><?= $st ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="carousel-arrow carousel-next" id="milestoneNext" aria-label="Next"><i class="ri-arrow-right-s-line"></i></button>
    </div>

    <div class="milestone-mobile-scroll">
        <?php foreach ($progressItems as $label => $data):
            $sc = 'status-missing'; $st = 'Missing';
            if ($data['status'] === 'pending')   { $sc = 'status-pending';  $st = 'Pending'; }
            elseif ($data['status'] === 'approved') { $sc = 'status-approved'; $st = 'Approved'; }
            elseif ($data['status'] === 'rejected') { $sc = 'status-rejected'; $st = 'Rejected'; }
        ?>
            <div class="milestone-mobile-card">
                <span class="milestone-mobile-label"><?= $label ?></span>
                <span class="status-badge <?= $sc ?>"><?= $st ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="milestone-section-title" style="margin-top:28px;"><i class="ri-upload-cloud-line"></i> Upload Milestone Documents</h2>

    <?php if ($titleStatus !== 'approved'): ?>
        <div class="milestone-locked-notice">
            <i class="ri-lock-line"></i>
            <h4>Milestone Uploads Locked</h4>
            <p>Your research title must be approved by the coordinator before you can upload milestone documents.</p>
        </div>
    <?php else: ?>
        <div class="milestone-cards-grid">
            <?php foreach ($milestoneTypes as $m):
                $hasFile = !empty($m['file_path']);
                $rawStatus = $m['status'] ?? 'missing';
                $sc = 'status-missing'; $st = 'Not Uploaded';
                if ($hasFile) {
                    if ($rawStatus === 'approved') { $sc = 'status-approved'; $st = 'Approved'; }
                    elseif ($rawStatus === 'rejected') { $sc = 'status-rejected'; $st = 'Rejected'; }
                    elseif ($rawStatus === 'pending') { $sc = 'status-pending'; $st = 'Pending Review'; }
                    else { $sc = 'status-pending'; $st = 'Pending Review'; }
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

                    <?php if ($comment): ?>
                        <div class="mc-comment-box"><i class="ri-chat-3-line"></i><strong><?= htmlspecialchars($adviserName) ?>:</strong> <?= htmlspecialchars($comment) ?></div>
                    <?php endif; ?>

                    <div class="milestone-card-actions">
                        <?php if ($rawStatus !== 'approved'): ?>
                            <button class="mc-upload-btn <?= $hasFile ? 'replace' : '' ?>"
                                onclick="openMilestoneModal('<?= $m['key'] ?>', '<?= htmlspecialchars($m['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['evidence'], ENT_QUOTES) ?>')">
                                <i class="ri-upload-line"></i> <?= $hasFile ? 'Replace' : 'Upload' ?>
                            </button>
                        <?php else: ?>
                            <span class="mc-approved-label"><i class="ri-checkbox-circle-fill"></i> Approved — locked</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="milestone-section-title"><i class="ri-file-text-line"></i> Manuscript Submission</h2>

    <?php if (!$isManuscriptUnlocked): ?>
        <div class="manuscript-locked-notice">
            <i class="ri-lock-line"></i>
            <span>The <strong>Full Manuscript</strong> submission is locked. Your adviser must approve the <strong>Copyright Approved</strong> milestone before you can submit your manuscript.</span>
        </div>
    <?php endif; ?>

    <div class="req-container">
        <?php
        $task = "Full Manuscript (Chapter 1-5)";
        $taskUpload = $uploadMap[$task] ?? null;
        $status = $taskUpload['status'] ?? 'missing';
        $comment = $taskUpload['comment'] ?? '';

        $statusClass = 'status-missing'; $statusText = 'Missing';
        if ($status === 'approved') { $statusClass = 'status-approved'; $statusText = 'Approved'; }
        elseif ($status === 'rejected') { $statusClass = 'status-rejected'; $statusText = 'Rejected'; }
        elseif ($status === 'pending') { $statusClass = 'status-pending'; $statusText = 'Pending'; }

        $taskResearchStatus = "Completed";
        $isTaskEnabled = in_array($taskResearchStatus, $enabledStatuses);
        $isActive = $isManuscriptUnlocked && $isTaskEnabled && ($status !== 'approved');
        $isApproved = ($status === 'approved');
        $isLockedByMilestone = !$isManuscriptUnlocked;
        ?>

        <div class="req-card <?= (!$isActive && !$isApproved) ? 'locked' : '' ?>">
            <div class="req-header">
                <span class="req-title-text"><?= $task ?></span>
                <?php if ($isLockedByMilestone): ?>
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
                    <span class="status-text">Status: <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></span>
                </div>
                <form action="php/upload_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="task_name" value="<?= $task ?>">
                    <input type="hidden" name="school_id" value="<?= $school_id ?>">
                    <label class="choose-file-btn">
                        Choose File
                        <input type="file" name="file_upload" <?= $isActive ? '' : 'disabled' ?> onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <div class="add-comment"
                data-task="<?= htmlspecialchars($task) ?>"
                data-comment="<?= htmlspecialchars($comment) ?>"
                data-filename="<?= $taskUpload ? htmlspecialchars($taskUpload['original_filename']) : '' ?>"
                data-filedate="<?= $taskUpload ? date("M d, Y", strtotime($taskUpload['uploaded_at'])) : '' ?>"
                data-filepath="<?= $taskUpload ? htmlspecialchars($taskUpload['file_path']) : '' ?>"
                data-adviser="<?= htmlspecialchars($adviserName) ?>">
                View Comment
            </div>
        </div>
    </div>

    <p class="note">Note: Full Manuscript submission is the final compiled research (Chapters 1–5)</p>

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
        <div class="upload-modal-actions">
            <button class="upload-modal-cancel" onclick="closeMilestoneModal()">Cancel</button>
            <button class="upload-modal-submit" id="milestoneSubmitBtn" onclick="submitStudentMilestone()" disabled>
                <i class="ri-upload-line"></i> Upload
            </button>
        </div>
    </div>
</div>

<script>
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
        const task = btn.dataset.task;
        const comment = btn.dataset.comment;
        const filename = btn.dataset.filename;
        const filedate = btn.dataset.filedate;
        const filepath = btn.dataset.filepath;
        const adviser = btn.dataset.adviser;
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

function openMilestoneModal(type, label, evidence) {
    activeMilestoneType = type;
    document.getElementById('milestoneModalTitle').innerHTML = '<i class="ri-upload-cloud-2-line"></i> Upload ' + label;
    document.getElementById('milestoneModalEvidence').innerHTML = '<strong>Required evidence:</strong> ' + evidence;
    document.getElementById('milestoneModalFile').value = '';
    document.getElementById('milestoneSelectedInfo').classList.remove('visible');
    document.getElementById('milestoneSubmitBtn').disabled = true;
    document.getElementById('milestoneUploadOverlay').classList.add('open');
}

function closeMilestoneModal() {
    document.getElementById('milestoneUploadOverlay').classList.remove('open');
    activeMilestoneType = null;
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
    const fileInput = document.getElementById('milestoneModalFile');
    const file = fileInput.files[0];
    if (!file) { showToast('Please select a file', 'error'); return; }
    if (file.type !== 'application/pdf') { showToast('Only PDF files are allowed', 'error'); return; }
    if (file.size > <?= $maxUploadSizeMB ?> * 1024 * 1024) { showToast('File size must be less than <?= $maxUploadSizeMB ?>MB', 'error'); return; }

    const formData = new FormData();
    formData.append('action', 'upload_milestone');
    formData.append('milestone_type', activeMilestoneType);
    formData.append('file', file);

    const btn = document.getElementById('milestoneSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite;"></i> Uploading...';

    try {
        const res = await fetch('requirements.php', { method: 'POST', body: formData });
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

(function () {
    const track   = document.getElementById('milestoneTrack');
    const btnPrev = document.getElementById('milestonePrev');
    const btnNext = document.getElementById('milestoneNext');
    if (!track) return;
    let current = 0;
    function isMobile() { return window.innerWidth <= 768; }
    function getStep() { const chip = track.children[0]; return chip ? chip.offsetWidth + 10 : 0; }
    function getMaxPx() {
        const totalWidth = Array.from(track.children).reduce((sum, chip) => sum + chip.offsetWidth + 16, 0) - 10;
        const visibleWidth = track.closest('.milestone-carousel-wrapper').clientWidth - (34 + 8) * 2;
        return Math.max(0, totalWidth - visibleWidth);
    }
    function getMax() { const step = getStep(); return step > 0 ? Math.round(getMaxPx() / step) : 0; }
    function slide() {
        if (isMobile()) { track.style.transform = ''; return; }
        const step = getStep(); const max = getMax(); const maxPx = getMaxPx();
        current = Math.max(0, Math.min(current, max));
        track.style.transform = `translateX(-${Math.min(current * step, maxPx)}px)`;
        btnPrev.disabled = (current === 0);
        btnNext.disabled = (current >= max);
    }
    btnPrev.addEventListener('click', () => { current--; slide(); });
    btnNext.addEventListener('click', () => { current++; slide(); });
    window.addEventListener('resize', () => { current = 0; slide(); });
    requestAnimationFrame(() => requestAnimationFrame(slide));
})();

(function() {
    const snapshot = {
        title_status: '<?= $titleStatus ?>',
        proposal_status: '<?= $proposalStatus ?>',
        final_defense_status: '<?= $finalDefenseStatus ?>',
        applied_copyright_status: '<?= $appliedCopyrightStatus ?>',
        research_presented_status: '<?= $researchPresentedStatus ?>',
        research_published_status: '<?= $researchPublishedStatus ?>',
        copyright_approved_status: '<?= $copyrightApprovedStatus ?>',
        urec_form_status: '<?= $urecFormStatus ?>',
        urec_clearance_status: '<?= $urecClearanceStatus ?>',
    };
    setInterval(async () => {
        try {
            const fd = new FormData();
            fd.append('action', 'poll_status');
            const data = await (await fetch('requirements.php', { method: 'POST', body: fd })).json();
            for (const key of Object.keys(snapshot)) {
                if (data[key] !== undefined && data[key] !== snapshot[key]) {
                    location.reload();
                    return;
                }
            }
        } catch {}
    }, 10000);
})();

const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/notifications.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>