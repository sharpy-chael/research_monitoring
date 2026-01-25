<?php
include("connect.php");
session_start();
include('php/get_setting.php');
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
$notificationsStmt->execute([
    'user_id' => $_SESSION['id']
]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_milestone') {
    header('Content-Type: application/json');
    
    $milestone_type = $_POST['milestone_type'] ?? '';
    $group_id = $_POST['group_id'] ?? null;
    
    $valid_types = ['title', 'urec_form', 'urec_clearance', 'proposal', 'final_defense', 'copyright'];
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
            
            $stmt = $con->prepare("UPDATE groups SET research_title = :title, title_status = 'approved' WHERE id = :group_id");
            $stmt->execute(['title' => $title, 'group_id' => $group_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            exit;
        }
        
        $file = $_FILES['file'];
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX']);
            exit;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
            exit;
        }
        
        $originalFilename = basename($file['name']);
        $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uniqueFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFilename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit;
        }
        
        if ($milestone_type === 'urec_form' || $milestone_type === 'urec_clearance') {
            $docType = ($milestone_type === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
            
            $stmt = $con->prepare("
                INSERT INTO urec_documents (group_id, adviser_id, document_type, file_path, original_filename, status, uploaded_at)
                VALUES (:group_id, :adviser_id, :document_type, :file_path, :original_filename, 'approved', NOW())
            ");
            $stmt->execute([
                'group_id' => $group_id,
                'adviser_id' => $sessionUserId,
                'document_type' => $docType,
                'file_path' => $filePath,
                'original_filename' => $originalFilename
            ]);
        }
        else {
            // proposal, final_defense, copyright - save to groups table
            $stmt = $con->prepare("
                UPDATE groups 
                SET {$milestone_type}_file_path = :file_path,
                    {$milestone_type}_original_filename = :original_filename,
                    {$milestone_type}_uploaded_at = NOW()
                WHERE id = :group_id
            ");
            $stmt->execute([
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'group_id' => $group_id
            ]);
            
            // Update milestone status to completed
            $milestoneColumn = $milestone_type . '_status';
            $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
            $checkStmt->execute(['group_id' => $group_id]);
            
            if ($checkStmt->rowCount() > 0) {
                $stmt = $con->prepare("UPDATE group_milestones SET {$milestoneColumn} = 'completed' WHERE group_id = :group_id");
            } else {
                $stmt = $con->prepare("INSERT INTO group_milestones (group_id, {$milestoneColumn}) VALUES (:group_id, 'completed')");
            }
            $stmt->execute(['group_id' => $group_id]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch all groups assigned to this advisor
$groupsStmt = $con->prepare("
    SELECT g.id as group_id, g.name as group_name
    FROM groups g
    WHERE g.advisor_id = :advisor_id
    ORDER BY g.name
");
$groupsStmt->execute(['advisor_id' => $advisorId]);
$assignedGroups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch details for each group
$groups = [];
foreach ($assignedGroups as $g) {
    $groupId = $g['group_id'];
    
    // Get leader
    $leaderStmt = $con->prepare("
        SELECT s.id, s.name, s.school_id, g.research_title, g.title_status
        FROM student s
        LEFT JOIN groups g ON s.group_id = g.id
        WHERE s.group_id = :group_id AND s.is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['group_id' => $groupId]);
    $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);

    $memberStmt = $con->prepare("
        SELECT id, name 
        FROM student 
        WHERE group_id = :group_id 
        AND (is_leader IS NULL OR is_leader = FALSE)
        ORDER BY name
    ");
    $memberStmt->execute(['group_id' => $groupId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get UREC documents
    $urecDocsStmt = $con->prepare("
        SELECT id, document_type, file_path, original_filename, status, comment, uploaded_at
        FROM urec_documents
        WHERE group_id = :group_id
        ORDER BY 
            CASE document_type
                WHEN 'UREC Form' THEN 1
                WHEN 'UREC Clearance' THEN 2
            END,
            uploaded_at DESC
    ");
    $urecDocsStmt->execute(['group_id' => $groupId]);
    $allUrecDocs = $urecDocsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get only latest document per type
    $urecDocsMap = [];
    foreach ($allUrecDocs as $doc) {
        if (!isset($urecDocsMap[$doc['document_type']])) {
            $urecDocsMap[$doc['document_type']] = $doc;
        }
    }
    $urecDocs = array_values($urecDocsMap);

    // Get proposal, final defense, copyright files from groups table
    $groupFilesStmt = $con->prepare("
        SELECT proposal_file_path, proposal_original_filename, proposal_uploaded_at,
               final_defense_file_path, final_defense_original_filename, final_defense_uploaded_at,
               copyright_file_path, copyright_original_filename, copyright_uploaded_at
        FROM groups
        WHERE id = :group_id
    ");
    $groupFilesStmt->execute(['group_id' => $groupId]);
    $groupFiles = $groupFilesStmt->fetch(PDO::FETCH_ASSOC);

    $uploads = [];
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
            if (!isset($uploadMap[$upload['task_name']])) {
                $uploadMap[$upload['task_name']] = $upload;
            }
        }
        
        $taskOrder = [
            'Chapter 1' => 1,
            'Chapter 2' => 2,
            'Chapter 3' => 3,
            'Chapter 4' => 4,
            'Chapter 5' => 5,
            'Final Research Output' => 6
        ];
        
        $uploads = array_values($uploadMap);
        usort($uploads, function($a, $b) use ($taskOrder) {
            $orderA = $taskOrder[$a['task_name']] ?? 999;
            $orderB = $taskOrder[$b['task_name']] ?? 999;
            return $orderA - $orderB;
        });
        
        // Count approved chapters
        $approvedTasks = [];
        foreach ($uploads as $upload) {
            if ($upload['status'] === 'approved') {
                $approvedTasks[$upload['task_name']] = true;
            }
        }
        $approvedCount = count($approvedTasks);

        // Count approved milestones for this group
        $milestoneCountStmt = $con->prepare("
            SELECT 
                g.title_status,
                gm.proposal_status,
                gm.final_defense_status,
                gm.copyright_status
            FROM groups g
            LEFT JOIN group_milestones gm ON g.id = gm.group_id
            WHERE g.id = :group_id
        ");
        $milestoneCountStmt->execute(['group_id' => $groupId]);
        $groupMilestones = $milestoneCountStmt->fetch(PDO::FETCH_ASSOC);

        if ($groupMilestones) {
            if ($groupMilestones['title_status'] === 'approved') $approvedCount++;
            if ($groupMilestones['proposal_status'] === 'completed') $approvedCount++;
            if ($groupMilestones['final_defense_status'] === 'completed') $approvedCount++;
            if ($groupMilestones['copyright_status'] === 'completed') $approvedCount++;
        }

        // Count UREC documents for this group (already fetched and deduplicated)
        foreach ($urecDocs as $doc) {
            if ($doc['status'] === 'approved') {
                $approvedCount++;
            }
        }

        // Calculate progress based on 12 total requirements (6 chapters + 6 milestones)
        $progress = round(($approvedCount / 12) * 100);
    }
    
    $groups[] = [
        'group_id' => $groupId,
        'group_name' => $g['group_name'],
        'leader' => $leader,
        'members' => $members,
        'uploads' => $uploads,
        'urec_docs' => $urecDocs,
        'group_files' => $groupFiles,
        'progress' => $progress
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    
    <link rel="stylesheet" href="css/advisor.css">
    <link rel="stylesheet" href="css/notifications.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Advisees</title>
</head>
<body>
<?php include("templates/aside_advisor.html"); ?>
<main class="main-content">
    <h2 id="head">My Advisees</h2>
    
    <!-- My Assigned Groups Container -->
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
                Create New Group
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
                                        <circle class="progress-circle" 
                                                cx="25" cy="25" r="20"
                                                stroke-dasharray="<?= 2 * 3.14159 * 20 ?>"
                                                stroke-dashoffset="<?= 2 * 3.14159 * 20 * (1 - $grp['progress'] / 100) ?>">
                                        </circle>
                                    </svg>
                                    <div class="progress-text"><?= $grp['progress'] ?>%</div>
                                </div>
                            </div>
                        </div>
                        <i class="ri-arrow-right-s-line expand-icon" onclick="toggleGroupDetails(this)"></i>
                    </div>

                        <!-- Research Title Section - Advisor can set/edit -->
                        <div class="title-section" style="margin-bottom:10px; padding:12px; border:1px solid #ddd; border-radius:6px; background-color:#f8f9fa;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><i class="ri-book-mark-line"></i> Research Title:</strong>
                                    <?php if ($grp['leader'] && !empty($grp['leader']['research_title'])): ?>
                                        <p style="margin:5px 0 0 0; font-size:14px;"><?= htmlspecialchars($grp['leader']['research_title']) ?></p>
                                        <span class="status-badge status-approved">Set</span>
                                    <?php else: ?>
                                        <span style="color:#999; font-size:14px;">Not set yet</span>
                                        <span class="status-badge status-missing">Missing</span>
                                    <?php endif; ?>
                                </div>
                                <button class="upload-milestone-btn" style="margin:0;" onclick="openTitleModal(<?= $grp['group_id'] ?>, '<?= htmlspecialchars($grp['leader']['research_title'] ?? '', ENT_QUOTES) ?>')">
                                    <i class="ri-edit-line"></i> <?= ($grp['leader'] && !empty($grp['leader']['research_title'])) ? 'Edit' : 'Set' ?> Title
                                </button>
                            </div>
                        </div>

                        <div class="group-details">
                            <?php if ($grp['leader']): ?>
                                <div class="leader-section">
                                    <strong>
                                        <i class="ri-star-fill"></i>
                                        Group Leader:
                                    </strong>
                                    <span class="leader-name"><?= htmlspecialchars($grp['leader']['name']) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="leader-section" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-bottom: 15px;">
                                    <i class="ri-alert-line" style="color: #856404;"></i>
                                    <span style="color: #856404;">No leader assigned yet</span>
                                    <button onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" style="margin-left: 10px; padding: 5px 10px; background: #ffc107; color: #000; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                        <i class="ri-user-add-line"></i> Add Members & Set Leader
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="members-section">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h4>
                                        <i class="ri-group-line"></i>
                                        Members
                                        <span class="member-count"><?= count($grp['members']) ?></span>
                                    </h4>
                                    <button onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" style="padding: 10px 13px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: bold; margin-bottom: 8px">
                                        <i class="ri-user-add-line"></i> Add Members
                                    </button>
                                </div>
                                
                                <?php if (!empty($grp['members'])): ?>
                                    <div class="members-grid">
                                        <?php foreach ($grp['members'] as $member): ?>
                                            <div class="member-item">
                                                <i class="ri-user-3-line"></i>
                                                <?= htmlspecialchars($member['name']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-members-msg">
                                        No members added yet
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Milestone Documents Upload Section -->
                            <div class="uploads-section" style="margin-top: 20px;">
                                <h4>
                                    <i class="ri-folder-upload-line"></i>
                                    Research Milestone Documents
                                </h4>
                                
                                <?php
                                $milestoneTypes = [
                                    ['key' => 'urec_form', 'label' => 'UREC Form', 'icon' => 'ri-file-shield-line'],
                                    ['key' => 'urec_clearance', 'label' => 'UREC Clearance', 'icon' => 'ri-file-shield-2-line'],
                                    ['key' => 'proposal', 'label' => 'Proposal', 'icon' => 'ri-file-text-line'],
                                    ['key' => 'final_defense', 'label' => 'Final Defense', 'icon' => 'ri-presentation-line'],
                                    ['key' => 'copyright', 'label' => 'Copyright/IP', 'icon' => 'ri-copyright-line']
                                ];
                                
                                foreach ($milestoneTypes as $m):
                                    // Check if file exists
                                    $hasFile = false;
                                    $fileName = '';
                                    $fileDate = '';
                                    $filePath = '';
                                    
                                    if ($m['key'] === 'urec_form' || $m['key'] === 'urec_clearance') {
                                        // Check in urec_docs
                                        $docType = ($m['key'] === 'urec_form') ? 'UREC Form' : 'UREC Clearance';
                                        foreach ($grp['urec_docs'] as $doc) {
                                            if ($doc['document_type'] === $docType) {
                                                $hasFile = true;
                                                $fileName = $doc['original_filename'];
                                                $fileDate = date("M d, Y • h:i A", strtotime($doc['uploaded_at']));
                                                $filePath = $doc['file_path'];
                                                break;
                                            }
                                        }
                                    } else {
                                        // Check in group_files
                                        $filePathKey = $m['key'] . '_file_path';
                                        $filenameKey = $m['key'] . '_original_filename';
                                        $dateKey = $m['key'] . '_uploaded_at';
                                        
                                        if (!empty($grp['group_files'][$filePathKey])) {
                                            $hasFile = true;
                                            $fileName = $grp['group_files'][$filenameKey];
                                            $fileDate = date("M d, Y • h:i A", strtotime($grp['group_files'][$dateKey]));
                                            $filePath = $grp['group_files'][$filePathKey];
                                        }
                                    }
                                ?>
                                    <div class="milestone-upload-card">
                                    <!-- Header: Title, Status, and Upload Button on same line -->
                                    <div class="milestone-header">
                                        <div class="milestone-title-status">
                                            <strong>
                                                <i class="<?= $m['icon'] ?>"></i>
                                                <?= $m['label'] ?>
                                            </strong>
                                            <?php if ($hasFile): ?>
                                                <span class="status-badge status-approved">Uploaded</span>
                                            <?php else: ?>
                                                <span class="status-badge status-missing">Not Uploaded</span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="milestone-upload-btn" onclick="openMilestoneUploadModal('<?= $m['key'] ?>', '<?= $m['label'] ?>', <?= $grp['group_id'] ?>)">
                                            <i class="ri-upload-line"></i>
                                            <?= $hasFile ? 'Replace' : 'Upload' ?>
                                        </button>
                                    </div>
                                    
                                    <!-- File details (shown only if file exists) -->
                                    <?php if ($hasFile): ?>
                                        <div class="milestone-file-details">
                                            <p><i class="ri-file-line"></i> <?= htmlspecialchars($fileName) ?></p>
                                            <p><?= $fileDate ?></p>
                                            <a href="<?= htmlspecialchars($filePath) ?>" download>
                                                <i class="ri-download-line"></i> Download
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="uploads-section" style="margin-top: 20px;">
                                <h4>
                                    <i class="ri-file-upload-line"></i>
                                    Uploaded Research Documents
                                    <span style="font-size: 0.8em; color: #999; font-weight: normal;">(Latest uploads only)</span>
                                </h4>
                                
                                <?php if (!empty($grp['uploads'])): ?>
                                    <?php foreach ($grp['uploads'] as $upload): 
                                        $statusClass = '';
                                        $statusText = 'Pending';
                                        if ($upload['status'] === 'approved') {
                                            $statusClass = 'approved';
                                            $statusText = 'Approved';
                                        } elseif ($upload['status'] === 'rejected') {
                                            $statusClass = 'rejected';
                                            $statusText = 'Rejected';
                                        }
                                    ?>
                                        <div class="upload-card <?= $statusClass ?>" data-upload-id="<?= $upload['upload_id'] ?>">
                                            <div class="upload-card-header">
                                                <div class="upload-header-left">
                                                    <i class="ri-file-3-line file-icon"></i>
                                                    <span class="file-title">
                                                        <?= htmlspecialchars($upload['task_name']) ?>
                                                        <span class="status-badge status-<?= strtolower($statusText) ?>">
                                                            <?= $statusText ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                
                                                <div class="menu-wrapper">
                                                    <i class="ri-more-2-fill menu-toggle" onclick="toggleMenu(event, this)"></i>
                                                    <div class="menu-dropdown">
                                                        <button class="approve-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'approved')">
                                                            <i class="ri-check-line"></i> Approve
                                                        </button>
                                                        <button class="reject-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'rejected')">
                                                            <i class="ri-close-line"></i> Reject
                                                        </button>
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
                                                <p class="file-filename">
                                                    <?= htmlspecialchars($upload['original_filename']) ?>
                                                </p>
                                                <p class="file-date">
                                                    <?= date("M d, Y • h:i A", strtotime($upload['uploaded_at'])) ?>
                                                </p>
                                                
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
                                        <i class="ri-folder-open-line"></i>
                                        No uploaded files yet
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
    <div style="height: 50px;" class="space"></div>
</main>

<!-- Create Group Modal -->
<div class="comment-modal" id="createGroupModal">
    <div class="comment-modal-content">
        <h3>Create New Group</h3>
        <input type="text" id="newGroupName" placeholder="Enter group name (e.g., Group5-BSIT)" style="width: 100%; padding: 10px; margin: 15px 0; border: 1px solid #ddd; border-radius: 4px;">
        <div class="comment-modal-buttons">
            <button class="btn-cancel" onclick="closeCreateGroupModal()">Cancel</button>
            <button class="btn-submit" onclick="submitCreateGroup()">Create Group</button>
        </div>
    </div>
</div>

<!-- Add Members Modal -->
<div class="comment-modal" id="addMembersModal">
    <div class="comment-modal-content">
        <h3 id="addMembersTitle">Add Members to Group</h3>
        <input type="text" id="leaderSchoolId" placeholder="Leader School ID (optional)" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
        <textarea id="memberSchoolIds" placeholder="Enter student school IDs (comma-separated)&#10;Example: 2021-001, 2021-002, 2021-003" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;"></textarea>
        <p style="font-size: 12px; color: #666; margin: 5px 0;">Note: If you specify a leader, they will be automatically added to the group.</p>
        <div class="comment-modal-buttons">
            <button class="btn-cancel" onclick="closeAddMembersModal()">Cancel</button>
            <button class="btn-submit" onclick="submitAddMembers()">Add Members</button>
        </div>
    </div>
</div>

<!-- Comment Modal -->
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

<!-- Milestone Upload Modal -->
<div class="comment-modal" id="milestoneUploadModal">
    <div class="comment-modal-content">
        <h3 id="milestoneUploadTitle">Upload Document</h3>
        <input type="file" id="milestoneFileInput" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="margin: 15px 0; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        <p style="font-size: 12px; color: #999; margin: 5px 0;">Allowed formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</p>
        <div class="comment-modal-buttons">
            <button class="btn-cancel" onclick="closeMilestoneUploadModal()">Cancel</button>
            <button class="btn-submit" onclick="submitMilestoneUpload()">Upload</button>
        </div>
    </div>
</div>

<!-- Title Modal -->
<div class="comment-modal" id="advisorTitleModal">
    <div class="comment-modal-content">
        <h3>Set Research Title</h3>
        <input type="text" id="advisorTitleInput" placeholder="Enter research title" style="width: 100%; padding: 10px; margin: 15px 0; border: 1px solid #ddd; border-radius: 4px;">
        <div class="comment-modal-buttons">
            <button class="btn-cancel" onclick="closeAdvisorTitleModal()">Cancel</button>
            <button class="btn-submit" onclick="submitAdvisorTitle()">Save Title</button>
        </div>
    </div>
</div>

<script src="js/timeout.js"></script>
<script>
function toggleGroupDetails(icon) {
    const card = icon.closest('.group-card');
    const details = card.querySelector('.group-details');
    icon.classList.toggle('expanded');
    details.classList.toggle('expanded');
}

function toggleMenu(event, element) {
    event.stopPropagation();
    const menu = element.nextElementSibling;
    document.querySelectorAll('.menu-dropdown').forEach(m => { if (m !== menu) m.classList.remove('show'); });
    menu.classList.toggle('show');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.menu-dropdown').forEach(menu => { menu.classList.remove('show'); });
});

// Create Group Modal Functions
function openCreateGroupModal() {
    document.getElementById('newGroupName').value = '';
    document.getElementById('createGroupModal').classList.add('show');
}

function closeCreateGroupModal() {
    document.getElementById('createGroupModal').classList.remove('show');
}

async function submitCreateGroup() {
    const groupName = document.getElementById('newGroupName').value.trim();
    if (!groupName) {
        alert('Please enter a group name');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_group');
    formData.append('group_name', groupName);

    try {
        const response = await fetch('php/assign_group_roles.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert('Group created successfully!');
            location.reload();
        } else {
            alert(data.message || 'Failed to create group');
        }
    } catch (error) {
        alert('Error creating group: ' + error.message);
    }
}

// Add Members Modal Functions
let currentGroupIdForMembers = null;

function openAddMembersModal(groupId) {
    currentGroupIdForMembers = groupId;
    document.getElementById('leaderSchoolId').value = '';
    document.getElementById('memberSchoolIds').value = '';
    document.getElementById('addMembersModal').classList.add('show');
}

function closeAddMembersModal() {
    document.getElementById('addMembersModal').classList.remove('show');
    currentGroupIdForMembers = null;
}

async function submitAddMembers() {
    const leaderId = document.getElementById('leaderSchoolId').value.trim();
    const memberIds = document.getElementById('memberSchoolIds').value.trim();

    if (!leaderId && !memberIds) {
        alert('Please enter at least a leader or member school IDs');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_members');
    formData.append('group_id', currentGroupIdForMembers);
    formData.append('leader_id', leaderId);
    formData.append('member_ids', memberIds);

    try {
        const response = await fetch('php/assign_group_roles.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message || 'Members added successfully!');
            location.reload();
        } else {
            alert(data.message || 'Failed to add members');
        }
    } catch (error) {
        alert('Error adding members: ' + error.message);
    }
}

async function updateStatus(uploadId, status) {
    if (!confirm(`Are you sure you want to ${status} this file?`)) return;
    const formData = new FormData();
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    try {
        const response = await fetch('php/update_upload_status.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { alert(`File ${status} successfully!`); location.reload(); }
        else { alert(data.message || 'Failed to update status'); }
    } catch (error) { alert('Error updating status: ' + error.message); }
}

let currentUploadId = null;
function openCommentModal(uploadId, taskName, currentComment) {
    currentUploadId = uploadId;
    document.getElementById('commentModalTitle').textContent = `Comment on ${taskName}`;
    document.getElementById('commentText').value = currentComment || '';
    document.getElementById('commentModal').classList.add('show');
}

function closeCommentModal() {
    document.getElementById('commentModal').classList.remove('show');
    currentUploadId = null;
}

async function submitComment() {
    const comment = document.getElementById('commentText').value.trim();
    if (!comment) { alert('Please enter a comment'); return; }
    const formData = new FormData();
    formData.append('upload_id', currentUploadId);
    formData.append('comment', comment);
    try {
        const response = await fetch('php/update_upload_comment.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { alert('Comment added successfully!'); closeCommentModal(); location.reload(); }
        else { alert(data.message || 'Failed to add comment'); }
    } catch (error) { alert('Error adding comment: ' + error.message); }
}

let currentMilestoneType = null;
let currentMilestoneGroupId = null;
function openMilestoneUploadModal(type, label, groupId) {
    currentMilestoneType = type;
    currentMilestoneGroupId = groupId;
    document.getElementById('milestoneUploadTitle').textContent = `Upload ${label}`;
    document.getElementById('milestoneFileInput').value = '';
    document.getElementById('milestoneUploadModal').classList.add('show');
}

function closeMilestoneUploadModal() {
    document.getElementById('milestoneUploadModal').classList.remove('show');
    currentMilestoneType = null;
    currentMilestoneGroupId = null;
}

async function submitMilestoneUpload() {
    const fileInput = document.getElementById('milestoneFileInput');
    if (!fileInput.files[0]) { alert('Please select a file'); return; }
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('milestone_type', currentMilestoneType);
    formData.append('group_id', currentMilestoneGroupId);
    formData.append('action', 'upload_milestone');
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { alert('File uploaded successfully!'); location.reload(); }
        else { alert(data.message || 'Failed to upload file'); }
    } catch (error) { alert('Error uploading file: ' + error.message); }
}

let currentTitleGroupId = null;
function openTitleModal(groupId, currentTitle) {
    currentTitleGroupId = groupId;
    document.getElementById('advisorTitleInput').value = currentTitle || '';
    document.getElementById('advisorTitleModal').classList.add('show');
}

function closeAdvisorTitleModal() {
    document.getElementById('advisorTitleModal').classList.remove('show');
    currentTitleGroupId = null;
}

async function submitAdvisorTitle() {
    const title = document.getElementById('advisorTitleInput').value.trim();
    if (!title) { alert('Please enter a title'); return; }
    const formData = new FormData();
    formData.append('title', title);
    formData.append('milestone_type', 'title');
    formData.append('group_id', currentTitleGroupId);
    formData.append('action', 'upload_milestone');
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { alert('Title saved successfully!'); location.reload(); }
        else { alert(data.message || 'Failed to save title'); }
    } catch (error) { alert('Error saving title: ' + error.message); }
}
</script>
<script src="js/notifications.js"></script>
</body>
</html>