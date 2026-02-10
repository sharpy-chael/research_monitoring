<?php
session_start();
include('connect.php');

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

// Fetch all users with their status - FIXED: Added s.group_id to query
$students = $con->query("
    SELECT s.id, s.name, s.school_id, s.program, s.is_active, s.group_id, g.name as group_name
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    ORDER BY s.name
")->fetchAll(PDO::FETCH_ASSOC);

$advisors = $con->query("
    SELECT a.id, a.name, a.advisor_id, a.is_active, COUNT(g.id) as group_count
    FROM advisor a
    LEFT JOIN groups g ON CAST(a.advisor_id AS VARCHAR) = CAST(g.advisor_id AS VARCHAR)
    GROUP BY a.id, a.name, a.advisor_id, a.is_active
    ORDER BY a.name
")->fetchAll(PDO::FETCH_ASSOC);

$coordinators = $con->query("
    SELECT id, name, is_active
    FROM coordinator
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Get groups for dropdown
$groups = $con->query("SELECT id, name FROM groups ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/users.css">
    <title>User Management</title>
</head>
<body>
    <?php include("templates/aside_admin.html"); ?>
    
    <main class="main-content">
        <h1><i class="ri-user-settings-line"></i> User Management</h1>
        
        <!-- User Type Tabs -->
        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('students')">
                <i class="ri-graduation-cap-line"></i> Students
            </button>
            <button class="tab-btn" onclick="switchTab('advisors')">
                <i class="ri-user-star-line"></i> Advisors
            </button>
            <button class="tab-btn" onclick="switchTab('coordinators')">
                <i class="ri-admin-line"></i> Coordinators
            </button>
        </div>

        <!-- STUDENTS TAB -->
        <div id="students-tab" class="tab-content active">
            <div class="section-header">
                <h2>Student Accounts</h2>
                <div class="header-actions">
                    <div class="search-wrapper">
                        <i class="ri-search-line"></i>
                        <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="searchTable('student')">
                        <button class="clear-search" id="clearStudentSearch" onclick="clearSearch('student')">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <button class="btn-add" onclick="openModal('student')">
                        <i class="ri-user-add-line"></i> Add Student
                    </button>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="users-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>School ID</th>
                            <th>Program</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr class="<?= $student['is_active'] ? '' : 'inactive-row' ?>">
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['school_id']) ?></td>
                                <td><?= htmlspecialchars($student['program']) ?></td>
                                <td><?= htmlspecialchars($student['group_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <span class="status-badge <?= $student['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editUser("student", <?= json_encode($student) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='openConfirmModal("student", <?= $student['id'] ?>, <?= $student['is_active'] ? "false" : "true" ?>, "<?= htmlspecialchars($student['name']) ?>")'>
                                        <i class="ri-shield-<?= $student['is_active'] ? 'cross' : 'check' ?>-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="no-results" id="studentNoResults">
                    <i class="ri-search-line"></i>
                    <p>No students found</p>
                </div>
            </div>
        </div>

        <!-- ADVISORS TAB -->
        <div id="advisors-tab" class="tab-content">
            <div class="section-header">
                <h2>Advisor Accounts</h2>
                <div class="header-actions">
                    <div class="search-wrapper">
                        <i class="ri-search-line"></i>
                        <input type="text" id="advisorSearch" placeholder="Search advisors..." onkeyup="searchTable('advisor')">
                        <button class="clear-search" id="clearAdvisorSearch" onclick="clearSearch('advisor')">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <button class="btn-add" onclick="openModal('advisor')">
                        <i class="ri-user-add-line"></i> Add Advisor
                    </button>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="users-table" id="advisorTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Advisor ID</th>
                            <th>Assigned Groups</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advisors as $advisor): ?>
                            <tr class="<?= $advisor['is_active'] ? '' : 'inactive-row' ?>">
                                <td><?= htmlspecialchars($advisor['name']) ?></td>
                                <td><?= htmlspecialchars($advisor['advisor_id'] ?? 'N/A') ?></td>
                                <td><?= $advisor['group_count'] ?> groups</td>
                                <td>
                                    <span class="status-badge <?= $advisor['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $advisor['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editUser("advisor", <?= json_encode($advisor) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='openConfirmModal("advisor", <?= $advisor['id'] ?>, <?= $advisor['is_active'] ? "false" : "true" ?>, "<?= htmlspecialchars($advisor['name']) ?>")'>
                                        <i class="ri-shield-<?= $advisor['is_active'] ? 'cross' : 'check' ?>-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="no-results" id="advisorNoResults">
                    <i class="ri-search-line"></i>
                    <p>No advisors found</p>
                </div>
            </div>
        </div>

        <!-- COORDINATORS TAB -->
        <div id="coordinators-tab" class="tab-content">
            <div class="section-header">
                <h2>Coordinator Accounts</h2>
                <button class="btn-add" onclick="openModal('coordinator')">
                    <i class="ri-user-add-line"></i> Add Coordinator
                </button>
            </div>
            
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coordinators as $coordinator): ?>
                            <tr class="<?= $coordinator['is_active'] ? '' : 'inactive-row' ?>">
                                <td><?= htmlspecialchars($coordinator['name']) ?></td>
                                <td>
                                    <span class="status-badge <?= $coordinator['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $coordinator['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editUser("coordinator", <?= json_encode($coordinator) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='openConfirmModal("coordinator", <?= $coordinator['id'] ?>, <?= $coordinator['is_active'] ? "false" : "true" ?>, "<?= htmlspecialchars($coordinator['name']) ?>")'>
                                        <i class="ri-shield-<?= $coordinator['is_active'] ? 'cross' : 'check' ?>-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space"></div>
    </main>

    <!-- User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h3 id="modalTitle">Add User</h3>
            
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="userType" name="user_type">
                <input type="hidden" id="formAction" name="action">
                
                <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" id="userName" name="name" required>
                </div>
                
                <div class="form-group" id="advisorIdGroup">
                    <label>Advisor ID <span class="required">*</span></label>
                    <input type="text" id="advisorId" name="advisor_id">
                </div>
                
                <div class="form-group" id="schoolIdGroup">
                    <label>School ID <span class="required">*</span></label>
                    <input type="text" id="schoolId" name="school_id">
                </div>
                
                <div class="form-group" id="programGroup">
                    <label>Program <span class="required">*</span></label>
                    <input type="text" id="program" name="program">
                </div>
                
                <div class="form-group" id="groupGroup">
                    <label>Assign to Group</label>
                    <select id="groupId" name="group_id">
                        <option value="">No Group</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label>Password <span class="required" id="passwordRequired">*</span></label>
                    <input type="password" id="password" name="password">
                    <small id="passwordHint">Leave blank to keep current password (Password must have 8 characters)</small>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal confirm-modal" id="confirmModal">
        <div class="modal-overlay" onclick="closeConfirmModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            <h3 id="confirmTitle">Confirm Action</h3>
            <p class="confirm-message" id="confirmMessage"></p>
            <div class="confirm-buttons">
                <button class="btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-confirm" id="confirmBtn" onclick="confirmToggleStatus()">Confirm</button>
            </div>
        </div>
    </div>
    <script src="js/timeout.js"></script>
    <script>
    let pendingToggle = null;

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

    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        event.target.closest('.tab-btn').classList.add('active');
        document.getElementById(tabName + '-tab').classList.add('active');
    }

    function openModal(type) {
        document.getElementById('userModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Add ' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('userType').value = type;
        document.getElementById('formAction').value = 'create';
        
        // Show/hide fields based on user type
        document.getElementById('advisorIdGroup').style.display = type === 'advisor' ? 'block' : 'none';
        document.getElementById('schoolIdGroup').style.display = type === 'student' ? 'block' : 'none';
        document.getElementById('programGroup').style.display = type === 'student' ? 'block' : 'none';
        document.getElementById('groupGroup').style.display = type === 'student' ? 'block' : 'none';
        document.getElementById('passwordHint').style.display = 'none';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('password').required = true;
        
        // Set required for advisor_id when adding advisor
        if (type === 'advisor') {
            document.getElementById('advisorId').required = true;
        }
    }

    function editUser(type, user) {
        document.getElementById('userModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('userId').value = user.id;
        document.getElementById('userType').value = type;
        document.getElementById('formAction').value = 'update';
        document.getElementById('userName').value = user.name;
        
        if (type === 'student') {
            document.getElementById('schoolId').value = user.school_id || '';
            document.getElementById('program').value = user.program || '';
            
            // FIXED: Set the group_id dropdown value
            const groupSelect = document.getElementById('groupId');
            groupSelect.value = user.group_id || '';
            
            document.getElementById('schoolIdGroup').style.display = 'block';
            document.getElementById('programGroup').style.display = 'block';
            document.getElementById('groupGroup').style.display = 'block';
            document.getElementById('advisorIdGroup').style.display = 'none';
        } else if (type === 'advisor') {
            document.getElementById('advisorId').value = user.advisor_id || '';
            document.getElementById('advisorIdGroup').style.display = 'block';
            document.getElementById('schoolIdGroup').style.display = 'none';
            document.getElementById('programGroup').style.display = 'none';
            document.getElementById('groupGroup').style.display = 'none';
            document.getElementById('advisorId').required = false;
        } else {
            document.getElementById('advisorIdGroup').style.display = 'none';
            document.getElementById('schoolIdGroup').style.display = 'none';
            document.getElementById('programGroup').style.display = 'none';
            document.getElementById('groupGroup').style.display = 'none';
        }
        
        document.getElementById('passwordHint').style.display = 'block';
        document.getElementById('passwordRequired').style.display = 'none';
        document.getElementById('password').required = false;
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('show');
    }

    function openConfirmModal(type, id, newStatus, userName) {
        const action = newStatus === 'true' ? 'activate' : 'deactivate';
        const actionColor = newStatus === 'true' ? 'green' : 'red';
        
        document.getElementById('confirmTitle').textContent = action.charAt(0).toUpperCase() + action.slice(1) + ' User';
        document.getElementById('confirmMessage').innerHTML = `Are you sure you want to <strong style="color: ${actionColor}">${action}</strong> <strong>${userName}</strong>?`;
        document.getElementById('confirmModal').classList.add('show');
        
        pendingToggle = { type, id, newStatus };
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
        pendingToggle = null;
    }

    async function confirmToggleStatus() {
        if (!pendingToggle) return;
        
        const { type, id, newStatus } = pendingToggle;
        
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('user_type', type);
        formData.append('user_id', id);
        formData.append('is_active', newStatus);
        
        try {
            const response = await fetch('php/manage_user.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeConfirmModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Operation failed', 'error');
                closeConfirmModal();
            }
        } catch (error) {
            showToast('Network error: ' + error.message, 'error');
            closeConfirmModal();
        }
    }

    document.getElementById('userForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('php/manage_user.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Operation failed', 'error');
            }
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        }
    });

    // Search functionality
    function searchTable(type) {
        const input = document.getElementById(type + 'Search');
        const filter = input.value.toLowerCase();
        const table = document.getElementById(type + 'Table');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        const noResults = document.getElementById(type + 'NoResults');
        const clearBtn = document.getElementById('clear' + type.charAt(0).toUpperCase() + type.slice(1) + 'Search');
        
        let visibleCount = 0;
        
        // Show/hide clear button
        if (filter.length > 0) {
            clearBtn.classList.add('show');
        } else {
            clearBtn.classList.remove('show');
        }
        
        // Loop through table rows
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            // Search through all cells
            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell) {
                    const text = cell.textContent || cell.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            if (found) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        }
        
        // Show/hide no results message
        if (visibleCount === 0 && filter.length > 0) {
            noResults.classList.add('show');
        } else {
            noResults.classList.remove('show');
        }
    }

    function clearSearch(type) {
        const input = document.getElementById(type + 'Search');
        input.value = '';
        searchTable(type);
        input.focus();
    }
    </script>
</body>
</html>