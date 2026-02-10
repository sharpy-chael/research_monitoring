<?php
include("connect.php");
session_start();
include('php/get_setting.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_milestone') {
    header('Content-Type: application/json');
    
    $sessionUserId = $_SESSION['id'];
    $advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
    $advisorStmt->execute(['id' => $sessionUserId]);
    $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
    $advisorId = $advisorData['advisor_id'] ?? $sessionUserId;
    
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
    
    $urecDocsMap = [];
    foreach ($allUrecDocs as $doc) {
        if (!isset($urecDocsMap[$doc['document_type']])) {
            $urecDocsMap[$doc['document_type']] = $doc;
        }
    }
    $urecDocs = array_values($urecDocsMap);

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
            // 'Final Research Output' => 6
        ];
        
        $uploads = array_values($uploadMap);
        usort($uploads, function($a, $b) use ($taskOrder) {
            $orderA = $taskOrder[$a['task_name']] ?? 999;
            $orderB = $taskOrder[$b['task_name']] ?? 999;
            return $orderA - $orderB;
        });
        
        $approvedTasks = [];
        foreach ($uploads as $upload) {
            if ($upload['status'] === 'approved') {
                $approvedTasks[$upload['task_name']] = true;
            }
        }
        $approvedCount = count($approvedTasks);

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

        foreach ($urecDocs as $doc) {
            if ($doc['status'] === 'approved') {
                $approvedCount++;
            }
        }

        $progress = round(($approvedCount / 11) * 100);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/advisor.css">
    <link rel="stylesheet" href="css/notifications.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Advisees</title>
   <style>
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
    font-size: 14px;
}

.toast-notification.success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.toast-notification.error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.toast-notification i {
    font-size: 20px;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast-notification.removing {
    animation: slideOut 0.3s ease-in;
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}
   </style>
</head>
<body>
<?php include("templates/aside_advisor.html"); ?>
<main class="main-content">
    <h2 id="head">My Advisees</h2>
<div class="manage-section" style="margin: 30px 3px;">
    <h2><i class="ri-global-line"></i> Manage UN SDGs</h2>
    <div class="items-grid" id="sdgGrid">
        <?php
        $sdgStmt = $con->prepare("SELECT * FROM un_sdgs WHERE advisor_id = :advisor_id ORDER BY name");
        $sdgStmt->execute(['advisor_id' => $advisorId]);
        $mySDGs = $sdgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($mySDGs as $sdg):
        ?>
        <div class="item-card" data-id="<?= $sdg['id'] ?>">
            <span class="item-name"><?= htmlspecialchars($sdg['name']) ?></span>
            <i class="ri-delete-bin-line delete-icon" onclick="deleteItem('sdg', <?= $sdg['id'] ?>, this)"></i>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="add-item-form">
        <input type="text" id="newSdgName" placeholder="Enter new UN SDG name...">
        <button onclick="addItem('sdg')"><i class="ri-add-line"></i> Add SDG</button>
    </div>
</div>

<div class="manage-section" style="margin: 30px 3px;">
    <h2><i class="ri-flashlight-line"></i> Manage Research Thrusts</h2>
    <div class="items-grid" id="thrustGrid">
        <?php
        // Fetch Research Thrusts created by this advisor
        $thrustStmt = $con->prepare("SELECT * FROM research_thrusts WHERE advisor_id = :advisor_id ORDER BY name");
        $thrustStmt->execute(['advisor_id' => $advisorId]);
        $myThrusts = $thrustStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($myThrusts as $thrust):
        ?>
        <div class="item-card" data-id="<?= $thrust['id'] ?>">
            <span class="item-name"><?= htmlspecialchars($thrust['name']) ?></span>
            <i class="ri-delete-bin-line delete-icon" onclick="deleteItem('thrust', <?= $thrust['id'] ?>, this)"></i>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="add-item-form">
        <input type="text" id="newThrustName" placeholder="Enter new Research Thrust name...">
        <button onclick="addItem('thrust')"><i class="ri-add-line"></i> Add Thrust</button>
    </div>
</div>
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
                                    <button onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" style="padding: 5px 10px; background: #ffc107; color: #000; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                        <i class="ri-user-add-line"></i> Add Members & Set Leader
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="members-sdg-grid">
                                <!-- Members Section -->
                                <div class="members-column">
                                    <div class="column-header">
                                        <h4>
                                            <i class="ri-group-line"></i>
                                            Members
                                            <span class="member-count"><?= count($grp['members']) ?></span>
                                        </h4>
                                        <button id="adds" onclick="openAddMembersModal(<?= $grp['group_id'] ?>)" class="add-member-btn">
                                            <i class="ri-user-add-line"></i>
                                        </button>
                                    </div>
                                    
                                    <?php if (!empty($grp['members'])): ?>
                                        <div class="members-list-vertical">
                                            <?php foreach ($grp['members'] as $member): ?>
                                                <div class="member-item-compact">
                                                    <i class="ri-user-3-line"></i>
                                                    <?= htmlspecialchars($member['name']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-items-msg">
                                            No members added yet
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="sdg-column">
                                    <?php
                                    // Fetch assigned SDGs for this group
                                    $groupSdgStmt = $con->prepare("
                                        SELECT us.id, us.name 
                                        FROM un_sdgs us
                                        JOIN group_sdgs gs ON us.id = gs.sdg_id
                                        WHERE gs.group_id = :group_id
                                        ORDER BY us.name
                                    ");
                                    $groupSdgStmt->execute(['group_id' => $grp['group_id']]);
                                    $assignedSdgs = $groupSdgStmt->fetchAll(PDO::FETCH_ASSOC);

                                    $groupThrustStmt = $con->prepare("
                                        SELECT rt.id, rt.name 
                                        FROM research_thrusts rt
                                        JOIN group_thrusts gt ON rt.id = gt.thrust_id
                                        WHERE gt.group_id = :group_id
                                        ORDER BY rt.name
                                    ");
                                    $groupThrustStmt->execute(['group_id' => $grp['group_id']]);
                                    $assignedThrusts = $groupThrustStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <div class="column-header">
                                        <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                        <button id="add" class="assign-btn-compact" onclick="openAssignModal('sdg', <?= $grp['group_id'] ?>)">
                                            <i class="ri-add-line"></i>
                                        </button>
                                    </div>
                                    
                                    <?php if (!empty($assignedSdgs)): ?>
                                        <div class="tags-list-vertical">
                                            <?php foreach($assignedSdgs as $sdg): ?>
                                            <span class="tag-compact">
                                                <?= htmlspecialchars($sdg['name']) ?>
                                                <i class="ri-close-line" onclick="removeAssignment('sdg', <?= $grp['group_id'] ?>, <?= $sdg['id'] ?>, this)"></i>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-items-msg">No SDGs assigned</div>
                                    <?php endif; ?>

                                    <div class="column-header" style="margin-top: 20px;">
                                        <h4><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                        <button id="added" class="assign-btn-compact" onclick="openAssignModal('thrust', <?= $grp['group_id'] ?>)">
                                            <i class="ri-add-line"></i>
                                        </button>
                                    </div>
                                    
                                    <?php if (!empty($assignedThrusts)): ?>
                                        <div class="tags-list-vertical">
                                            <?php foreach($assignedThrusts as $thrust): ?>
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
                                            <button class="preview-btn" onclick="openPreview('<?= htmlspecialchars($filePath, ENT_QUOTES) ?>', '<?= htmlspecialchars($fileName, ENT_QUOTES) ?>')">
                                                <i class="ri-eye-line"></i> Preview
                                            </button>
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
                                                        <?php if ($upload['status'] !== 'approved'): ?>
                                                            <!-- SHOW APPROVE/REJECT BUTTONS ONLY IF NOT APPROVED -->
                                                            <button class="approve-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'approved')">
                                                                <i class="ri-check-line"></i> Approve
                                                            </button>
                                                            <button class="reject-btn" onclick="updateStatus(<?= $upload['upload_id'] ?>, 'rejected')">
                                                                <i class="ri-close-line"></i> Reject
                                                            </button>
                                                        <?php else: ?>
                                                            <!-- SHOW LOCKED INDICATOR IF ALREADY APPROVED -->
                                                            <div style="padding: 8px 12px; color: #28a745; font-size: 13px; border-bottom: 1px solid #e9ecef;">
                                                                <i class="ri-lock-line"></i> Approved - Locked
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- THESE BUTTONS REMAIN VISIBLE FOR ALL STATUSES -->
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
<!-- Document Preview Modal -->
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
                <i class="ri-loader-4-line" style="font-size: 48px; animation: spin 1s linear infinite;"></i>
                <p>Loading preview...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

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
<!-- Assignment Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAssignModal()">×</span>
        <h3 id="assignModalTitle">Assign</h3>
        
        <div id="assignCheckboxes" style="max-height: 300px; overflow-y: auto; padding: 10px;">
            <!-- Checkboxes will be populated here -->
        </div>

        <button onclick="submitAssignment()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Assign Selected
        </button>
    </div>
</div>

<script src="js/timeout.js"></script>
<script src="js/advisees.js"></script>
<script src="js/notifications.js"></script>
</body>
</html>