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
        JOIN groups g ON g.adviser_id = a.id
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
                    <label>Groups</label>
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
                            <div class="members-list">

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
                                            <i class="ri-delete-bin-line"
                                               onclick="deleteMember(<?= $member['id'] ?>, this)">
                                            </i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button class="buttoned"
                                        onclick="promptAddMember(<?= $grp['group_id'] ?>)">
                                    + Add Member
                                </button>

                            </div>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card add-student-card" onclick="openAddStudentModal()">
                    <div class="card-head">
                        <strong>Add Student</strong>
                        <i class="ri-add-line"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</main>

<!-- Existing Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div id="modalErrorMsg"></div>
    <div class="modal-content">
        <span class="close" onclick="closeAddStudentModal()">×</span>
        <h3 id="hedss">Add Existing Group / Assign Roles</h3>
        
        <input type="text" id="modalAdviserId" placeholder="Adviser ID">
        <input type="text" id="modalLeaderId" placeholder="Leader School ID">
        <textarea id="modalStudentIds" placeholder="Enter student school IDs (comma-separated)..."></textarea>

        <select id="modalGroupSelect">
            <option value="" disabled selected>Select Group</option>
            <?php foreach($groups as $grp): ?>
            <option value="<?= $grp['group_id'] ?>"><?= htmlspecialchars($grp['group_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" id="modalNewGroup" placeholder="Or add new group...">

        <button id="buttonzz" onclick="submitGroupAssignment()">Save</button>
    </div>
</div>

<!-- New Assignment Modal -->
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
        const res = await fetch('manage_sdg_thrust.php', {
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
        const res = await fetch('manage_sdg_thrust.php', {
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
        const res = await fetch('manage_sdg_thrust.php', {
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
        const res = await fetch('manage_sdg_thrust.php', {
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
        const res = await fetch('manage_sdg_thrust.php', {
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

// =====================
// Existing functions
// =====================
async function submitGroupAssignment() {
    const groupSelect = document.getElementById('modalGroupSelect');
    const newGroupInput = document.getElementById('modalNewGroup');
    const leaderId = document.getElementById('modalLeaderId').value.trim();
    const adviserId = document.getElementById('modalAdviserId').value.trim();
    const studentIds = document.getElementById('modalStudentIds').value.trim();
    const errorMsg = document.getElementById('modalErrorMsg');
    const saveBtn = document.getElementById('buttonzz');

    errorMsg.style.display = 'none';
    errorMsg.textContent = '';

    if (!newGroupInput.value && !groupSelect.value) {
        errorMsg.textContent = 'Please select an existing group or create a new one.';
        errorMsg.style.display = 'block';
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    saveBtn.classList.add('loading');

    const formData = new FormData();
    formData.append('group_id', groupSelect.value);
    formData.append('new_group', newGroupInput.value.trim());
    formData.append('leader_id', leaderId);
    formData.append('adviser_id', adviserId);
    formData.append('students', studentIds);

    try {
        const res = await fetch('assign_group_roles.php', { 
            method: 'POST', 
            body: formData 
        });
        
        const data = await res.json();

        if(data.status === 'success'){
            location.reload();
        } else {
            errorMsg.textContent = data.message || 'An error occurred. Please try again.';
            errorMsg.style.display = 'block';
            errorMsg.style.background = 'linear-gradient(135deg, #fee 0%, #fdd 100%)';
            errorMsg.style.color = '#a00000';
        }
    } catch (error) {
        console.error('Error:', error);
        errorMsg.textContent = 'Network error. Please check your connection and try again.';
        errorMsg.style.display = 'block';
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
        saveBtn.classList.remove('loading');
    }
}

async function deleteGroup(id, elem){
    if(!confirm('Delete this group?')) return;
    
    try {
        const res = await fetch('add_student.php', {
            method: 'POST',
            body: JSON.stringify({delete_group_id: id}),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        
        if(data.success){
            elem.closest('.group-item').remove();
            const option = document.querySelector(`#modalGroupSelect option[value='${id}']`);
            if(option) option.remove();
            showToast('Group deleted successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to delete group.', 'error');
        }
    } catch (error) {
        console.error('Error deleting group:', error);
        showToast('Network error. Please try again.', 'error');
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