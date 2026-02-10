<?php
include("connect.php");
session_start();
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

// Fetch all groups with their members
$groups = [];
$groupStmt = $con->query("SELECT * FROM groups ORDER BY name");
$allGroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allGroups as $g) {
    $groupId = $g['id'];
    $groupName = $g['name'];
    $researchTitle = $g['research_title'] ?? null;
    $titleStatus = $g['title_status'] ?? 'missing';

    // MEMBERS
    $memberStmt = $con->prepare("
        SELECT id, name 
        FROM student 
        WHERE group_id = :group_id 
        AND (is_leader IS NULL OR is_leader = FALSE)
        ORDER BY name
    ");
    $memberStmt->execute(['group_id' => $groupId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

    // LEADER
    $leaderStmt = $con->prepare("
        SELECT name 
        FROM student 
        WHERE group_id = :group_id AND is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['group_id' => $groupId]);
    $leader = $leaderStmt->fetchColumn();

    // ADVISER
    $adviserStmt = $con->prepare("
        SELECT a.name 
        FROM advisor a
        JOIN groups g ON a.advisor_id = CAST(g.advisor_id AS VARCHAR)
        WHERE g.id = :group_id
    ");
    $adviserStmt->execute(['group_id' => $groupId]);
    $adviser = $adviserStmt->fetchColumn();

    // ASSIGNED SDGs
    $sdgStmt = $con->prepare("
        SELECT us.id, us.name 
        FROM un_sdgs us
        JOIN group_sdgs gs ON us.id = gs.sdg_id
        WHERE gs.group_id = :group_id
        ORDER BY us.name
    ");
    $sdgStmt->execute(['group_id' => $groupId]);
    $assignedSdgs = $sdgStmt->fetchAll(PDO::FETCH_ASSOC);

    // ASSIGNED THRUSTS
    $thrustStmt = $con->prepare("
        SELECT rt.id, rt.name 
        FROM research_thrusts rt
        JOIN group_thrusts gt ON rt.id = gt.thrust_id
        WHERE gt.group_id = :group_id
        ORDER BY rt.name
    ");
    $thrustStmt->execute(['group_id' => $groupId]);
    $assignedThrusts = $thrustStmt->fetchAll(PDO::FETCH_ASSOC);

    $groups[] = [
        'group_id'   => $groupId,
        'group_name' => $groupName,
        'research_title' => $researchTitle,
        'title_status' => $titleStatus,
        'adviser'    => $adviser,
        'leader'     => $leader,
        'members'    => $members,
        'sdgs'       => $assignedSdgs,
        'thrusts'    => $assignedThrusts
    ];
}

// Fetch all available SDGs and Thrusts
$allSdgs = $con->query("SELECT * FROM un_sdgs ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allThrusts = $con->query("SELECT * FROM research_thrusts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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
$notificationsStmt->execute([
    'user_id' => $_SESSION['id']
]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Research Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
<link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/manage.css">
</head>
<body>
<?php include("templates/aside_coordinator.html"); ?>

<main class="main-content">

<!-- SDG and Thrust Viewing Section (Read-Only for Coordinators) -->
<div class="manage-section">
    <h2><i class="ri-global-line"></i> All UN SDGs</h2>
    <p style="color: #666; font-size: 14px; margin-bottom: 15px; margin-top: 5px;">
        <i class="ri-information-line"></i> Advisors manage SDGs and Research Thrusts. You can view all entries here.
    </p>
    <div class="items-grid" id="sdgGrid">
        <?php if (empty($allSdgs)): ?>
            <p style="color: #999; font-style: italic; padding: 20px;">No SDGs created yet. Advisors can create them.</p>
        <?php else: ?>
            <?php foreach($allSdgs as $sdg): ?>
            <div class="item-card" data-id="<?= $sdg['id'] ?>">
                <span class="item-name"><?= htmlspecialchars($sdg['name']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="manage-section">
    <h2><i class="ri-flashlight-line"></i> All Research Thrusts</h2>
    <p style="color: #666; font-size: 14px; margin-bottom: 15px; margin-top: 5px;">
        <i class="ri-information-line"></i> Advisors manage SDGs and Research Thrusts. You can view all entries here.
    </p>
    <div class="items-grid" id="thrustGrid">
        <?php if (empty($allThrusts)): ?>
            <p style="color: #999; font-style: italic; padding: 20px;">No Research Thrusts created yet. Advisors can create them.</p>
        <?php else: ?>
            <?php foreach($allThrusts as $thrust): ?>
            <div class="item-card" data-id="<?= $thrust['id'] ?>">
                <span class="item-name"><?= htmlspecialchars($thrust['name']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="left-col">
        <div class="group-row">
            <div class="group-list-container-mod">
                <div class="card student-card">
                    <label>All Groups</label>
                    <div class="student-dropdown-wrap">
                        <?php foreach($groups as $grp): ?>
                        <div class="group-item" data-group-id="<?= $grp['group_id'] ?>">

                            <!-- GROUP HEADER -->
                            <div class="group-name">
                                <?= htmlspecialchars($grp['group_name']) ?>
                                <i class="ri-more-2-fill group-menu" onclick="toggleMembers(this)"></i>
                                <i class="ri-delete-bin-line group-delete"
                                   onclick="deleteGroup(<?= $grp['group_id'] ?>, this)"
                                   title="Delete group"></i>
                            </div>

                            <!-- EXPANDABLE CONTENT -->
                            <div class="members-list" style="display: none;">

                                <hr class="group-divider">

                                <?php if (!empty($grp['research_title'])): ?>
                                    <div class="group-meta research-title">
                                        <div style="flex: 1;">
                                            <b>Research Title:</b> <?= htmlspecialchars($grp['research_title']) ?>
                                        </div>
                                        <?php
                                        $statusClass = 'status-missing';
                                        $statusText = 'Missing';
                                        if ($grp['title_status'] === 'pending') { 
                                            $statusClass = 'status-pending'; 
                                            $statusText = 'Pending'; 
                                        } elseif ($grp['title_status'] === 'approved') { 
                                            $statusClass = 'status-approved'; 
                                            $statusText = 'Approved'; 
                                        } elseif ($grp['title_status'] === 'rejected') { 
                                            $statusClass = 'status-rejected'; 
                                            $statusText = 'Rejected'; 
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($grp['adviser'])): ?>
                                    <div class="group-meta adviser">
                                        <b>Adviser:</b>  <?= htmlspecialchars($grp['adviser']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($grp['leader'])): ?>
                                    <div class="group-meta leader">
                                       <b>Leader:</b>  <?= htmlspecialchars($grp['leader']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- SDGs and Thrusts Section (READ-ONLY FOR COORDINATORS) -->
                                <div class="sdg-thrust-section">
                                    <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                    <div class="tags-container">
                                        <?php if (empty($grp['sdgs'])): ?>
                                            <span style="color: #999; font-size: 13px; font-style: italic;">No SDGs assigned yet</span>
                                        <?php else: ?>
                                            <?php foreach($grp['sdgs'] as $sdg): ?>
                                            <span class="tag" style="padding-right: 12px;">
                                                <?= htmlspecialchars($sdg['name']) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <h4 style="margin-top: 15px;"><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                    <div class="tags-container">
                                        <?php if (empty($grp['thrusts'])): ?>
                                            <span style="color: #999; font-size: 13px; font-style: italic;">No Research Thrusts assigned yet</span>
                                        <?php else: ?>
                                            <?php foreach($grp['thrusts'] as $thrust): ?>
                                            <span class="tag thrust" style="padding-right: 12px;">
                                                <?= htmlspecialchars($thrust['name']) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lists"><p><i class="ri-group-line"></i> Members</p></div>
                                <div class="members">
                                    <?php foreach($grp['members'] as $member): ?>
                                        <div class="member-item">
                                            <?= htmlspecialchars($member['name']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            </div>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</main>

<script src="js/timeout.js"></script>
<script src="js/edit.js"></script>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    const icon = type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line';
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Delete group
async function deleteGroup(id, elem){
    if(!confirm('Delete this group? This will remove all students from this group.')) return;
    
    try {
        const res = await fetch('php/add_student.php', {
            method: 'POST',
            body: JSON.stringify({delete_group_id: id}),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        
        if(data.success){
            elem.closest('.group-item').remove();
            showToast('Group deleted successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to delete group.', 'error');
        }
    } catch (error) {
        console.error('Error deleting group:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Toggle members visibility
function toggleMembers(icon) {
    const groupItem = icon.closest('.group-item');
    const membersList = groupItem.querySelector('.members-list');
    
    // Toggle the expanded class
    if (membersList.style.display === 'none' || membersList.style.display === '') {
        membersList.style.display = 'block';
        icon.classList.add('rotated');
    } else {
        membersList.style.display = 'none';
        icon.classList.remove('rotated');
    }
}
</script>

</body>
</html>