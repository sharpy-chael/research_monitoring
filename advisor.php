<?php
include("connect.php");
session_start();
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

// Get advisor ID from session
$advisorId = $_SESSION['id'];

// Fetch all groups assigned to this advisor
$groupsStmt = $con->prepare("
    SELECT g.id as group_id, g.name as group_name
    FROM groups g
    WHERE g.adviser_id = :adviser_id
    ORDER BY g.name
");
$groupsStmt->execute(['adviser_id' => $advisorId]);
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
        
        $approvedTasks = [];
        foreach ($uploads as $upload) {
            if ($upload['status'] === 'approved') {
                $approvedTasks[$upload['task_name']] = true;
            }
        }
        $progress = round((count($approvedTasks) / 6) * 100);
    }
    
    $groups[] = [
        'group_id' => $groupId,
        'group_name' => $g['group_name'],
        'leader' => $leader,
        'members' => $members,
        'uploads' => $uploads,
        'urec_docs' => $urecDocs,
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
    <title>Advisor Dashboard</title>
</head>
<body>
<?php include("templates/aside_advisor.html"); ?>
<main class="main-content">
    <h1>Dashboard</h1>
    
    <div class="welcome-section">
        <p class="welcome-text">
            <strong>Welcome,</strong> <?php echo htmlspecialchars($_SESSION['name']); ?>.
        </p>
        <?php if (!empty($groups)): ?>
            <div class="stats-summary clean-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="ri-team-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= count($groups) ?></div>
            <div class="stat-label">Assigned Groups</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="ri-user-star-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number">
                <?= count(array_filter($groups, fn($g) => $g['leader'])) ?>
            </div>
            <div class="stat-label">Active Leaders</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="ri-file-list-3-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number">
                <?= array_sum(array_map(fn($g) => count($g['uploads']), $groups)) ?>
            </div>
            <div class="stat-label">Total Submissions</div>
        </div>
    </div>
</div>

        <?php endif; ?>
    </div>

    <div class="chart-wrapper">
        <div id="root" style="margin-top: 40px;"></div>
        <script type="module" src="./react-app/dist/assets/advisor-NDcY9ARP.js" defer></script>
    </div>
    
    <!-- My Assigned Groups Container -->
    <div class="my-groups-container">
        <h2>
            <i class="ri-team-line"></i>
            My Assigned Groups
            <?php if (!empty($groups)): ?>
                <span class="groups-count"><?= count($groups) ?></span>
            <?php endif; ?>
        </h2>
        
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
                        <?php if ($grp['leader']): ?>
                            <div class="title-section" style="margin-bottom:10px; padding:8px 12px; border:1px solid #ccc; border-radius:6px; background-color:#fff3f3;">
                                <strong><i class="ri-book-mark-line"></i> Proposed Research Title:</strong>
                                <span><?= htmlspecialchars($grp['leader']['research_title'] ?? 'No title yet') ?></span>
                                <span class="status-badge status-<?= $grp['leader']['title_status'] ?? 'missing' ?>" data-group-id="<?= $grp['group_id'] ?>">
                                    <?= ucfirst($grp['leader']['title_status'] ?? 'Missing') ?>
                                </span>

                                <?php if (($grp['leader']['title_status'] ?? '') === 'pending'): ?>
                                    <div class="stat_button" style="margin-top:5px;">
                                        <button id="approve" onclick="updateTitleStatus(<?= $grp['group_id'] ?>, 'approved')">Approve</button>
                                        <button id="reject" onclick="updateTitleStatus(<?= $grp['group_id'] ?>, 'rejected')">Reject</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="group-details">
                            <?php if ($grp['leader']): ?>
                                <div class="leader-section">
                                    <strong>
                                        <i class="ri-star-fill"></i>
                                        Group Leader:
                                    </strong>
                                    <span class="leader-name"><?= htmlspecialchars($grp['leader']['name']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="members-section">
                                <h4>
                                    <i class="ri-group-line"></i>
                                    Members
                                    <span class="member-count"><?= count($grp['members']) ?></span>
                                </h4>
                                
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
                                        No members assigned yet
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- UREC Documents Section -->
                            <?php if (!empty($grp['urec_docs'])): ?>
                                <div class="uploads-section" style="margin-top: 20px;">
                                    <h4>
                                        <i class="ri-file-shield-line"></i>
                                        UREC Documents
                                        <span style="font-size: 0.8em; color: #999; font-weight: normal;">(Latest uploads only)</span>
                                    </h4>
                                    
                                    <?php foreach ($grp['urec_docs'] as $doc): 
                                        $statusClass = '';
                                        $statusText = 'Pending';
                                        if ($doc['status'] === 'approved') {
                                            $statusClass = 'approved';
                                            $statusText = 'Approved';
                                        } elseif ($doc['status'] === 'rejected') {
                                            $statusClass = 'rejected';
                                            $statusText = 'Rejected';
                                        }
                                    ?>
                                        <div class="upload-card <?= $statusClass ?>" data-document-id="<?= $doc['id'] ?>">
                                            <div class="upload-card-header">
                                                <div class="upload-header-left">
                                                    <i class="ri-file-shield-2-line file-icon"></i>
                                                    <span class="file-title">
                                                        <?= htmlspecialchars($doc['document_type']) ?>
                                                        <span class="status-badge status-<?= strtolower($statusText) ?>">
                                                            <?= $statusText ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                
                                                <div class="menu-wrapper">
                                                    <i class="ri-more-2-fill menu-toggle" onclick="toggleMenu(event, this)"></i>
                                                    <div class="menu-dropdown">
                                                        <button class="approve-btn" onclick="updateUrecStatus(<?= $doc['id'] ?>, 'approved')">
                                                            <i class="ri-check-line"></i> Approve
                                                        </button>
                                                        <button class="reject-btn" onclick="updateUrecStatus(<?= $doc['id'] ?>, 'rejected')">
                                                            <i class="ri-close-line"></i> Reject
                                                        </button>
                                                        <button class="comment-btn" onclick="openUrecCommentModal(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['document_type'], ENT_QUOTES) ?>', '<?= htmlspecialchars($doc['comment'] ?? '', ENT_QUOTES) ?>')">
                                                            <i class="ri-chat-3-line"></i> <?= !empty($doc['comment']) ? 'Edit Comment' : 'Add Comment' ?>
                                                        </button>
                                                        <a href="<?= htmlspecialchars($doc['file_path']) ?>" download>
                                                            <i class="ri-download-line"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-card-body">
                                                <p class="file-filename">
                                                    <?= htmlspecialchars($doc['original_filename']) ?>
                                                </p>
                                                <p class="file-date">
                                                    <?= date("M d, Y • h:i A", strtotime($doc['uploaded_at'])) ?>
                                                </p>
                                                
                                                <?php if (!empty($doc['comment'])): ?>
                                                    <div class="file-comment">
                                                        <strong>Your Comment:</strong><br>
                                                        <?= htmlspecialchars($doc['comment']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

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
                <h3>No Groups Assigned</h3>
                <p>You have not been assigned to any groups yet.</p>
                <p style="font-size: 0.9em; color: #bbb;">Please contact the coordinator for group assignment.</p>
            </div>
        <?php endif; ?>
    </div>
    <div style="height: 50px;" class="space"></div>
</main>

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

<!-- UREC Comment Modal -->
<div class="comment-modal" id="urecCommentModal">
    <div class="comment-modal-content">
        <h3 id="urecCommentModalTitle">Add Comment</h3>
        <textarea id="urecCommentText" placeholder="Enter your comment here..."></textarea>
        <div class="comment-modal-buttons">
            <button class="btn-cancel" onclick="closeUrecCommentModal()">Cancel</button>
            <button class="btn-submit" onclick="submitUrecComment()">Submit</button>
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
    
    document.querySelectorAll('.menu-dropdown').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    
    menu.classList.toggle('show');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.menu-dropdown').forEach(menu => {
        menu.classList.remove('show');
    });
});

async function updateStatus(uploadId, status) {
    if (!confirm(`Are you sure you want to ${status} this file?`)) return;
    
    const formData = new FormData();
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    
    try {
        const response = await fetch('update_upload_status.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`File ${status} successfully!`);
            location.reload();
        } else {
            alert(data.message || 'Failed to update status');
        }
    } catch (error) {
        alert('Error updating status: ' + error.message);
    }
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
    
    if (!comment) {
        alert('Please enter a comment');
        return;
    }
    
    const formData = new FormData();
    formData.append('upload_id', currentUploadId);
    formData.append('comment', comment);
    
    try {
        const response = await fetch('update_upload_comment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Comment added successfully!');
            closeCommentModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to add comment');
        }
    } catch (error) {
        alert('Error adding comment: ' + error.message);
    }
}

function updateTitleStatus(groupId, status) {
    if(!confirm(`Are you sure you want to ${status} this title?`)) return;

    const formData = new FormData();
    formData.append('group_id', groupId);
    formData.append('status', status);

    fetch('update_title_status.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const badge = document.querySelector(`.status-badge[data-group-id='${groupId}']`);
                if (badge) {
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    badge.classList.remove('status-pending', 'status-missing', 'status-rejected', 'status-approved');
                    badge.classList.add('status-' + status);
                }
                const titleSection = badge.closest('.title-section');
                if (titleSection) {
                    const buttonContainer = titleSection.querySelector('div[style*="margin-top:5px"]');
                    if (buttonContainer) {
                        buttonContainer.style.display = 'none';
                    }
                }
                
                alert(`Title ${status} successfully!`);
            } else {
                alert(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            alert('Error updating status: ' + error.message);
        });
}

// UREC Documents functions
async function updateUrecStatus(documentId, status) {
    if (!confirm(`Are you sure you want to ${status} this document?`)) return;
    
    const formData = new FormData();
    formData.append('document_id', documentId);
    formData.append('status', status);
    
    try {
        const response = await fetch('update_upload_status.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Document ${status} successfully!`);
            location.reload();
        } else {
            alert(data.message || 'Failed to update status');
        }
    } catch (error) {
        alert('Error updating status: ' + error.message);
    }
}

let currentDocumentId = null;

function openUrecCommentModal(documentId, docType, currentComment) {
    currentDocumentId = documentId;
    document.getElementById('urecCommentModalTitle').textContent = `Comment on ${docType}`;
    document.getElementById('urecCommentText').value = currentComment || '';
    document.getElementById('urecCommentModal').classList.add('show');
}

function closeUrecCommentModal() {
    document.getElementById('urecCommentModal').classList.remove('show');
    currentDocumentId = null;
}

async function submitUrecComment() {
    const comment = document.getElementById('urecCommentText').value.trim();
    
    if (!comment) {
        alert('Please enter a comment');
        return;
    }
    
    const formData = new FormData();
    formData.append('document_id', currentDocumentId);
    formData.append('comment', comment);
    
    try {
        const response = await fetch('update_upload_comment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Comment added successfully!');
            closeUrecCommentModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to add comment');
        }
    } catch (error) {
        alert('Error adding comment: ' + error.message);
    }
}
</script>
</body>
</html>