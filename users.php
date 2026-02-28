<?php
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");


if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}

$students = $con->query("
    SELECT s.id, s.lastname, s.firstname, s.middlename, s.school_id, s.program, s.is_active, s.group_id, g.name as group_name
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    ORDER BY s.lastname, s.firstname
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

$groups = $con->query("SELECT id, name FROM groups ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$activePrograms = [];
try {
    $activePrograms = $con->query("SELECT code, name FROM programs WHERE is_active = TRUE ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activePrograms = [
        ['code' => 'DIT',  'name' => 'Diploma in Information Technology'],
        ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology']
    ];
}

foreach ($students as &$s) {
    $fn = trim($s['firstname'] ?? '');
    $mn = trim($s['middlename'] ?? '');
    $ln = trim($s['lastname'] ?? '');
    $s['display_name'] = trim($fn . ($mn ? ' ' . $mn : '') . ($ln ? ' ' . $ln : ''));
}
unset($s);
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
            <div class="section-header-compact">
                <h2>Student Accounts</h2>
                <div class="header-actions-compact">
                    <button class="btn-import" onclick="openCsvModal()">
                        <i class="ri-upload-2-line"></i> Import CSV
                    </button>
                    <button class="btn-add" onclick="openModal('student')">
                        <i class="ri-user-add-line"></i> Add Student
                    </button>
                </div>
            </div>

            <div class="filter-search-bar">
                <div class="search-wrapper-below">
                    <i class="ri-search-line"></i>
                    <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="searchTable('student')">
                    <button class="clear-search" id="clearStudentSearch" onclick="clearSearch('student')">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <div class="filter-group">
                    <div class="filter-item">
                        <label>Name</label>
                        <select id="filterStudentName" onchange="applyStudentFilters()">
                            <option value="">Default</option>
                            <option value="az">A → Z</option>
                            <option value="za">Z → A</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>ID</label>
                        <select id="filterStudentId" onchange="applyStudentFilters()">
                            <option value="">Default</option>
                            <option value="az">A → Z</option>
                            <option value="za">Z → A</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>Program</label>
                        <select id="filterStudentProgram" onchange="applyStudentFilters()">
                            <option value="">All</option>
                            <?php foreach ($activePrograms as $prog): ?>
                                <option value="<?= htmlspecialchars($prog['code']) ?>"><?= htmlspecialchars($prog['code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>Group</label>
                        <select id="filterStudentGroup" onchange="applyStudentFilters()">
                            <option value="">All</option>
                            <option value="unassigned">Unassigned</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= htmlspecialchars($group['name']) ?>"><?= htmlspecialchars($group['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-clear-filters" onclick="clearStudentFilters()">
                        <i class="ri-filter-off-line"></i> Clear
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="users-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Program</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr class="<?= $student['is_active'] ? '' : 'inactive-row' ?>"
                                data-name="<?= htmlspecialchars(strtolower($student['display_name'])) ?>"
                                data-id="<?= htmlspecialchars(strtolower($student['school_id'])) ?>"
                                data-program="<?= htmlspecialchars($student['program']) ?>"
                                data-group="<?= htmlspecialchars(strtolower($student['group_name'] ?? 'unassigned')) ?>">
                                <td><?= htmlspecialchars($student['display_name']) ?></td>
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
                                    <button class="btn-toggle" onclick='openConfirmModal("student", <?= $student['id'] ?>, <?= $student['is_active'] ? "false" : "true" ?>, "<?= htmlspecialchars($student['display_name']) ?>")'>
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
            <div class="section-header-compact">
                <h2>Advisor Accounts</h2>
                <button class="btn-add" onclick="openModal('advisor')">
                    <i class="ri-user-add-line"></i> Add Advisor
                </button>
            </div>

            <div class="filter-search-bar">
                <div class="search-wrapper-below">
                    <i class="ri-search-line"></i>
                    <input type="text" id="advisorSearch" placeholder="Search advisors..." onkeyup="searchTable('advisor')">
                    <button class="clear-search" id="clearAdvisorSearch" onclick="clearSearch('advisor')">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <div class="filter-group">
                    <div class="filter-item">
                        <label>Name</label>
                        <select id="filterAdvisorName" onchange="applyAdvisorFilters()">
                            <option value="">Default</option>
                            <option value="az">A → Z</option>
                            <option value="za">Z → A</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>Advisor ID</label>
                        <select id="filterAdvisorId" onchange="applyAdvisorFilters()">
                            <option value="">Default</option>
                            <option value="az">A → Z</option>
                            <option value="za">Z → A</option>
                        </select>
                    </div>
                    <button class="btn-clear-filters" onclick="clearAdvisorFilters()">
                        <i class="ri-filter-off-line"></i> Clear
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
                            <tr class="<?= $advisor['is_active'] ? '' : 'inactive-row' ?>"
                                data-name="<?= htmlspecialchars(strtolower($advisor['name'])) ?>"
                                data-advisor-id="<?= htmlspecialchars(strtolower($advisor['advisor_id'] ?? '')) ?>">
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
            <div class="section-header-compact">
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
                <input type="hidden" id="userId"     name="user_id">
                <input type="hidden" id="userType"   name="user_type">
                <input type="hidden" id="formAction" name="action">

                <div class="form-group" id="nameGroup">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" id="userName" name="name" required>
                </div>

                <div class="form-group" id="lastnameGroup" style="display:none;">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" id="userLastname" name="lastname">
                </div>

                <div class="form-group" id="firstnameGroup" style="display:none;">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" id="userFirstname" name="firstname">
                </div>

                <div class="form-group" id="middlenameGroup" style="display:none;">
                    <label>Middle Name</label>
                    <input type="text" id="userMiddlename" name="middlename">
                </div>

                <div class="form-group" id="advisorIdGroup">
                    <label>Advisor ID <span class="required">*</span></label>
                    <input type="text" id="advisorId" name="advisor_id">
                </div>

                <div class="form-group" id="schoolIdGroup">
                    <label>Student ID <span class="required">*</span></label>
                    <input type="text" id="schoolId" name="school_id">
                </div>

                <div class="form-group" id="programGroup">
                    <label>Program <span class="required">*</span></label>
                    <select id="program" name="program">
                        <option value="" disabled selected>Select a program</option>
                        <?php foreach ($activePrograms as $prog): ?>
                            <option value="<?= htmlspecialchars($prog['code']) ?>">
                                <?= htmlspecialchars($prog['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

    <!-- CSV Import Modal -->
    <div class="modal" id="csvModal">
        <div class="modal-overlay" onclick="closeCsvModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeCsvModal()">&times;</button>
            <h3><i class="ri-upload-2-line"></i> Import Students via CSV</h3>

            <div class="csv-format-info">
                <p><strong>Required CSV format:</strong></p>
                <code>lastname, firstname, middlename, school_id, program, password</code>
                <ul>
                    <li>First row must be the header: <strong>lastname, firstname, middlename, school_id, program, password</strong></li>
                    <li>middlename can be left empty</li>
                    <li>Program must match an active program code (e.g., <strong><?= implode(', ', array_column($activePrograms, 'code')) ?></strong>)</li>
                    <li>Password must be at least 8 characters</li>
                    <li>Duplicate Student IDs will be skipped</li>
                </ul>
                <a class="csv-download-sample" href="#" onclick="downloadSample(); return false;">
                    <i class="ri-download-line"></i> Download Sample CSV
                </a>
            </div>

            <form id="csvForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select CSV File <span class="required">*</span></label>
                    <input type="file" id="csvFile" name="csv_file" accept=".csv" required>
                </div>

                <div id="csvResults" class="csv-results" style="display:none;"></div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeCsvModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="csvSubmitBtn">
                        <i class="ri-upload-2-line"></i> Import
                    </button>
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
        toast.innerHTML = `<i class="${icon}"></i><span>${message}</span>`;
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

    function showStudentNameFields(show) {
        document.getElementById('nameGroup').style.display       = show ? 'none'  : 'block';
        document.getElementById('lastnameGroup').style.display   = show ? 'block' : 'none';
        document.getElementById('firstnameGroup').style.display  = show ? 'block' : 'none';
        document.getElementById('middlenameGroup').style.display = show ? 'block' : 'none';
        document.getElementById('userLastname').required  = show;
        document.getElementById('userFirstname').required = show;
        document.getElementById('userName').required      = !show;
    }

    function openModal(type) {
        document.getElementById('userModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Add ' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('userForm').reset();
        document.getElementById('userId').value     = '';
        document.getElementById('userType').value   = type;
        document.getElementById('formAction').value = 'create';

        showStudentNameFields(type === 'student');

        document.getElementById('advisorIdGroup').style.display = type === 'advisor' ? 'block' : 'none';
        document.getElementById('schoolIdGroup').style.display  = type === 'student' ? 'block' : 'none';
        document.getElementById('programGroup').style.display   = type === 'student' ? 'block' : 'none';
        document.getElementById('groupGroup').style.display     = type === 'student' ? 'block' : 'none';
        document.getElementById('passwordHint').style.display   = 'none';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('password').required = true;

        if (type === 'advisor') document.getElementById('advisorId').required = true;
        document.getElementById('program').value = '';
    }

    function editUser(type, user) {
        document.getElementById('userModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('userId').value     = user.id;
        document.getElementById('userType').value   = type;
        document.getElementById('formAction').value = 'update';

        showStudentNameFields(type === 'student');

        if (type === 'student') {
            document.getElementById('userLastname').value   = user.lastname   || '';
            document.getElementById('userFirstname').value  = user.firstname  || '';
            document.getElementById('userMiddlename').value = user.middlename || '';
            document.getElementById('schoolId').value = user.school_id || '';
            document.getElementById('program').value  = user.program   || '';
            document.getElementById('groupId').value  = user.group_id  || '';
            document.getElementById('schoolIdGroup').style.display  = 'block';
            document.getElementById('programGroup').style.display   = 'block';
            document.getElementById('groupGroup').style.display     = 'block';
            document.getElementById('advisorIdGroup').style.display = 'none';
        } else if (type === 'advisor') {
            document.getElementById('userName').value  = user.name       || '';
            document.getElementById('advisorId').value = user.advisor_id || '';
            document.getElementById('advisorIdGroup').style.display = 'block';
            document.getElementById('schoolIdGroup').style.display  = 'none';
            document.getElementById('programGroup').style.display   = 'none';
            document.getElementById('groupGroup').style.display     = 'none';
            document.getElementById('advisorId').required = false;
        } else {
            document.getElementById('userName').value = user.name || '';
            document.getElementById('advisorIdGroup').style.display = 'none';
            document.getElementById('schoolIdGroup').style.display  = 'none';
            document.getElementById('programGroup').style.display   = 'none';
            document.getElementById('groupGroup').style.display     = 'none';
        }

        document.getElementById('passwordHint').style.display     = 'block';
        document.getElementById('passwordRequired').style.display = 'none';
        document.getElementById('password').required = false;
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('show');
    }

    function openCsvModal() {
        document.getElementById('csvModal').classList.add('show');
        document.getElementById('csvForm').reset();
        document.getElementById('csvResults').style.display = 'none';
        document.getElementById('csvResults').innerHTML     = '';
        document.getElementById('csvSubmitBtn').disabled    = false;
    }

    function closeCsvModal() {
        document.getElementById('csvModal').classList.remove('show');
    }

    function downloadSample() {
        const csv = 'lastname,firstname,middlename,school_id,program,password\nDela Cruz,Juan,M.,2025-00001-UQ-0,BSIT,password123\nSantos,Maria,,2025-00002-UQ-0,DIT,password123';
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'students_sample.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    document.getElementById('csvForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('csvFile');
        if (!fileInput.files[0]) { showToast('Please select a CSV file', 'error'); return; }
        const submitBtn = document.getElementById('csvSubmitBtn');
        submitBtn.disabled  = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line"></i> Importing...';
        const formData = new FormData();
        formData.append('action',   'import_csv');
        formData.append('csv_file', fileInput.files[0]);
        try {
            const response = await fetch('php/manage_user.php', { method: 'POST', body: formData });
            const data     = await response.json();
            const resultsDiv = document.getElementById('csvResults');
            resultsDiv.style.display = 'block';
            if (data.success) {
                let html = `<div class="csv-summary success"><i class="ri-checkbox-circle-line"></i><strong>${data.imported} student(s) imported successfully.</strong>${data.skipped > 0 ? `<span>${data.skipped} row(s) skipped.</span>` : ''}</div>`;
                if (data.errors && data.errors.length > 0) {
                    html += `<div class="csv-errors"><p><strong>Skipped rows:</strong></p><ul>`;
                    data.errors.forEach(err => { html += `<li>${err}</li>`; });
                    html += `</ul></div>`;
                }
                resultsDiv.innerHTML = html;
                showToast(`${data.imported} student(s) imported!`, 'success');
                if (data.imported > 0) setTimeout(() => location.reload(), 2000);
            } else {
                resultsDiv.innerHTML = `<div class="csv-summary error"><i class="ri-error-warning-line"></i><strong>${data.message}</strong></div>`;
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        } finally {
            submitBtn.disabled  = false;
            submitBtn.innerHTML = '<i class="ri-upload-2-line"></i> Import';
        }
    });

    function openConfirmModal(type, id, newStatus, userName) {
        const action      = newStatus === 'true' ? 'activate' : 'deactivate';
        const actionColor = newStatus === 'true' ? 'green'    : 'red';
        document.getElementById('confirmTitle').textContent = action.charAt(0).toUpperCase() + action.slice(1) + ' User';
        document.getElementById('confirmMessage').innerHTML = `Are you sure you want to <strong style="color:${actionColor}">${action}</strong> <strong>${userName}</strong>?`;
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
        formData.append('action',    'toggle_status');
        formData.append('user_type', type);
        formData.append('user_id',   id);
        formData.append('is_active', newStatus);
        try {
            const response = await fetch('php/manage_user.php', { method: 'POST', body: formData });
            const data     = await response.json();
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
            const response = await fetch('php/manage_user.php', { method: 'POST', body: formData });
            const data     = await response.json();
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

    function searchTable(type) {
        const input     = document.getElementById(type + 'Search');
        const filter    = input.value.toLowerCase();
        const clearBtn  = document.getElementById('clear' + type.charAt(0).toUpperCase() + type.slice(1) + 'Search');
        clearBtn.classList.toggle('show', filter.length > 0);
        if (type === 'student') applyStudentFilters();
        else if (type === 'advisor') applyAdvisorFilters();
    }

    function clearSearch(type) {
        document.getElementById(type + 'Search').value = '';
        searchTable(type);
        document.getElementById(type + 'Search').focus();
    }

    function extractIdNumber(id) {
        const matches = id.match(/\d+/g);
        return matches && matches.length >= 2 ? parseInt(matches[1], 10) : (matches ? parseInt(matches[0], 10) : 0);
    }

    function applyStudentFilters() {
        const search  = document.getElementById('studentSearch').value.toLowerCase();
        const nameDir = document.getElementById('filterStudentName').value;
        const idDir   = document.getElementById('filterStudentId').value;
        const program = document.getElementById('filterStudentProgram').value.toLowerCase();
        const group   = document.getElementById('filterStudentGroup').value.toLowerCase();

        const tbody = document.querySelector('#studentTable tbody');
        let rows    = Array.from(tbody.querySelectorAll('tr'));

        rows.forEach(row => {
            const rowName    = row.dataset.name    || '';
            const rowId      = row.dataset.id      || '';
            const rowProgram = (row.dataset.program || '').toLowerCase();
            const rowGroup   = (row.dataset.group  || '').toLowerCase();

            const matchSearch  = !search  || rowName.includes(search) || rowId.includes(search) || rowProgram.includes(search) || rowGroup.includes(search);
            const matchProgram = !program || rowProgram === program;
            const matchGroup   = !group   || rowGroup === group || (group === 'unassigned' && rowGroup === 'unassigned');

            row.classList.toggle('hidden', !(matchSearch && matchProgram && matchGroup));
        });

        const activeSort = nameDir || idDir;
        if (activeSort) {
            const visibleRows = rows.filter(r => !r.classList.contains('hidden'));
            visibleRows.sort((a, b) => {
                if (nameDir) {
                    const va = a.dataset.name || '';
                    const vb = b.dataset.name || '';
                    return nameDir === 'az' ? va.localeCompare(vb) : vb.localeCompare(va);
                }
                if (idDir) {
                    const va = extractIdNumber(a.dataset.id || '');
                    const vb = extractIdNumber(b.dataset.id || '');
                    return idDir === 'az' ? va - vb : vb - va;
                }
                return 0;
            });
            const hiddenRows = rows.filter(r => r.classList.contains('hidden'));
            visibleRows.forEach(r => tbody.appendChild(r));
            hiddenRows.forEach(r => tbody.appendChild(r));
        }

        const visibleCount = rows.filter(r => !r.classList.contains('hidden')).length;
        document.getElementById('studentNoResults').classList.toggle('show', visibleCount === 0);
        document.getElementById('clearStudentSearch').classList.toggle('show', search.length > 0);
    }

    function clearStudentFilters() {
        document.getElementById('studentSearch').value        = '';
        document.getElementById('filterStudentName').value    = '';
        document.getElementById('filterStudentId').value      = '';
        document.getElementById('filterStudentProgram').value = '';
        document.getElementById('filterStudentGroup').value   = '';
        applyStudentFilters();
    }

    function applyAdvisorFilters() {
        const search  = document.getElementById('advisorSearch').value.toLowerCase();
        const nameDir = document.getElementById('filterAdvisorName').value;
        const idDir   = document.getElementById('filterAdvisorId').value;

        const tbody = document.querySelector('#advisorTable tbody');
        let rows    = Array.from(tbody.querySelectorAll('tr'));

        rows.forEach(row => {
            const rowName = row.dataset.name       || '';
            const rowId   = row.dataset.advisorId  || '';
            const matchSearch = !search || rowName.includes(search) || rowId.includes(search);
            row.classList.toggle('hidden', !matchSearch);
        });

        const activeSort = nameDir || idDir;
        if (activeSort) {
            const visibleRows = rows.filter(r => !r.classList.contains('hidden'));
            visibleRows.sort((a, b) => {
                if (nameDir) {
                    const va = a.dataset.name || '';
                    const vb = b.dataset.name || '';
                    return nameDir === 'az' ? va.localeCompare(vb) : vb.localeCompare(va);
                }
                if (idDir) {
                    const va = a.dataset.advisorId || '';
                    const vb = b.dataset.advisorId || '';
                    return idDir === 'az' ? va.localeCompare(vb) : vb.localeCompare(va);
                }
                return 0;
            });
            const hiddenRows = rows.filter(r => r.classList.contains('hidden'));
            visibleRows.forEach(r => tbody.appendChild(r));
            hiddenRows.forEach(r => tbody.appendChild(r));
        }

        const visibleCount = rows.filter(r => !r.classList.contains('hidden')).length;
        document.getElementById('advisorNoResults').classList.toggle('show', visibleCount === 0);
        document.getElementById('clearAdvisorSearch').classList.toggle('show', search.length > 0);
    }

    function clearAdvisorFilters() {
        document.getElementById('advisorSearch').value       = '';
        document.getElementById('filterAdvisorName').value   = '';
        document.getElementById('filterAdvisorId').value     = '';
        applyAdvisorFilters();
    }

    const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
    </script>
    <script src="js/session_monitor.js"></script>
</body>
</html>