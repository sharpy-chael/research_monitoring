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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
<link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/manage.css">
</head>
<body>
<?php include("templates/aside_coordinator.html"); ?>

<main class="main-content">

<!-- SDG and Thrust Management Section -->
<div class="manage-section">
    <h2><i class="ri-global-line"></i> Manage UN SDGs</h2>
    <div class="items-grid" id="sdgGrid">
        <?php foreach($allSdgs as $sdg): ?>
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

<div class="manage-section">
    <h2><i class="ri-flashlight-line"></i> Manage Research Thrusts</h2>
    <div class="items-grid" id="thrustGrid">
        <?php foreach($allThrusts as $thrust): ?>
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

                                <!-- SDGs and Thrusts Section -->
                                <div class="sdg-thrust-section">
                                    <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                    <div class="tags-container">
                                        <?php foreach($grp['sdgs'] as $sdg): ?>
                                        <span class="tag">
                                            <?= htmlspecialchars($sdg['name']) ?>
                                            <i class="ri-close-line" onclick="removeAssignment('sdg', <?= $grp['group_id'] ?>, <?= $sdg['id'] ?>, this)"></i>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="assign-btn" onclick="openAssignModal('sdg', <?= $grp['group_id'] ?>)">
                                        <i class="ri-add-line"></i> Assign SDG
                                    </button>

                                    <h4 style="margin-top: 15px;"><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                    <div class="tags-container">
                                        <?php foreach($grp['thrusts'] as $thrust): ?>
                                        <span class="tag thrust">
                                            <?= htmlspecialchars($thrust['name']) ?>
                                            <i class="ri-close-line" onclick="removeAssignment('thrust', <?= $grp['group_id'] ?>, <?= $thrust['id'] ?>, this)"></i>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="assign-btn" onclick="openAssignModal('thrust', <?= $grp['group_id'] ?>)">
                                        <i class="ri-add-line"></i> Assign Thrust
                                    </button>
                                </div>

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
<script src="js/edit.js"></script>

<script>
// Store current assignment context
let currentAssignContext = { type: null, groupId: null };

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

// Add SDG or Thrust
async function addItem(type) {
    const inputId = type === 'sdg' ? 'newSdgName' : 'newThrustName';
    const name = document.getElementById(inputId).value.trim();
    
    if (!name) {
        showToast('Please enter a name', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('type', type);
    formData.append('name', name);

    try {
        const res = await fetch('php/manage_sdg_thrust.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            const grid = document.getElementById(type === 'sdg' ? 'sdgGrid' : 'thrustGrid');
            const card = document.createElement('div');
            card.className = 'item-card';
            card.dataset.id = data.id;
            card.innerHTML = `
                <span class="item-name">${name}</span>
                <i class="ri-delete-bin-line delete-icon" onclick="deleteItem('${type}', ${data.id}, this)"></i>
            `;
            grid.appendChild(card);
            document.getElementById(inputId).value = '';
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} added successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to add item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Delete SDG or Thrust
async function deleteItem(type, id, elem) {
    if (!confirm(`Delete this ${type}?`)) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('type', type);
    formData.append('id', id);

    try {
        const res = await fetch('php/manage_sdg_thrust.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            elem.closest('.item-card').remove();
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} deleted successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to delete item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Open assignment modal
async function openAssignModal(type, groupId) {
    currentAssignContext = { type, groupId };
    
    const modal = document.getElementById('assignModal');
    const title = document.getElementById('assignModalTitle');
    const checkboxContainer = document.getElementById('assignCheckboxes');
    
    title.textContent = `Assign ${type === 'sdg' ? 'UN SDG' : 'Research Thrust'}`;
    
    // Fetch available items
    const formData = new FormData();
    formData.append('action', 'get_available');
    formData.append('type', type);
    formData.append('group_id', groupId);

    try {
        const res = await fetch('php/manage_sdg_thrust.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            checkboxContainer.innerHTML = '';
            data.items.forEach(item => {
                const label = document.createElement('label');
                label.style.display = 'block';
                label.style.padding = '8px';
                label.style.cursor = 'pointer';
                label.innerHTML = `
                    <input type="checkbox" value="${item.id}" style="margin-right: 8px;">
                    ${item.name}
                `;
                checkboxContainer.appendChild(label);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }

    modal.classList.add('show');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('show');
    currentAssignContext = { type: null, groupId: null };
}

// Submit assignment
async function submitAssignment() {
    const checkboxes = document.querySelectorAll('#assignCheckboxes input[type="checkbox"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        showToast('Please select at least one item', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'assign');
    formData.append('type', currentAssignContext.type);
    formData.append('group_id', currentAssignContext.groupId);
    formData.append('ids', JSON.stringify(ids));

    try {
        const res = await fetch('php/manage_sdg_thrust.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast('Assignment successful! Refreshing...', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to assign', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Remove assignment
async function removeAssignment(type, groupId, itemId, elem) {
    if (!confirm(`Remove this ${type} assignment?`)) return;

    const formData = new FormData();
    formData.append('action', 'remove_assignment');
    formData.append('type', type);
    formData.append('group_id', groupId);
    formData.append('item_id', itemId);

    try {
        const res = await fetch('php/manage_sdg_thrust.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            elem.closest('.tag').remove();
            showToast('Assignment removed successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to remove assignment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
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

// Close modal when clicking outside
window.onclick = function(event) {
    const assignModal = document.getElementById('assignModal');
    if (event.target === assignModal) {
        closeAssignModal();
    }
}
</script>

</body>
</html>