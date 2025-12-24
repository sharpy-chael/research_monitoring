<?php
session_start();
include('connect.php');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}

// Fetch all settings data
$programs = $con->query("SELECT * FROM programs ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
$academicYears = $con->query("SELECT * FROM academic_years ORDER BY year_start DESC, semester")->fetchAll(PDO::FETCH_ASSOC);
$researchStatuses = $con->query("SELECT * FROM research_statuses ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalStudents = $con->query("SELECT COUNT(*) FROM student")->fetchColumn();
$totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
$totalAdvisors = $con->query("SELECT COUNT(*) FROM advisor")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/settings.css">
    <title>Academic Settings</title>
</head>
<body>
    <?php include("templates/aside_admin.html"); ?>
    
    <main class="main-content">
        <h1><i class="ri-settings-3-line"></i> Academic Settings</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e3f2fd;">
                    <i class="ri-graduation-cap-line" style="color: #2196f3;"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Students</span>
                    <span class="stat-value"><?= $totalStudents ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f3e5f5;">
                    <i class="ri-group-line" style="color: #9c27b0;"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Groups</span>
                    <span class="stat-value"><?= $totalGroups ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f5e9;">
                    <i class="ri-user-star-line" style="color: #4caf50;"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Advisors</span>
                    <span class="stat-value"><?= $totalAdvisors ?></span>
                </div>
            </div>
        </div>

        <!-- Settings Tabs -->
        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('programs')">
                <i class="ri-book-line"></i> Programs
            </button>
            <button class="tab-btn" onclick="switchTab('academic-years')">
                <i class="ri-calendar-line"></i> Academic Years
            </button>
            <button class="tab-btn" onclick="switchTab('research-statuses')">
                <i class="ri-flag-line"></i> Research Statuses
            </button>
        </div>

        <!-- PROGRAMS TAB -->
        <div id="programs-tab" class="tab-content active">
            <div class="section-header">
                <h2>Program Management</h2>
                <button class="btn-add" onclick="openModal('program')">
                    <i class="ri-add-line"></i> Add Program
                </button>
            </div>
            
            <div class="table-wrapper">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Program Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $program): ?>
                            <tr class="<?= $program['is_active'] ? '' : 'inactive-row' ?>">
                                <td><strong><?= htmlspecialchars($program['code']) ?></strong></td>
                                <td><?= htmlspecialchars($program['name']) ?></td>
                                <td><?= htmlspecialchars($program['description'] ?? '') ?></td>
                                <td>
                                    <span class="status-badge <?= $program['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $program['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editItem("program", <?= json_encode($program) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='toggleStatus("program", <?= $program['id'] ?>, <?= $program['is_active'] ? "false" : "true" ?>)'>
                                        <i class="ri-toggle-<?= $program['is_active'] ? 'fill' : 'line' ?>"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ACADEMIC YEARS TAB -->
        <div id="academic-years-tab" class="tab-content">
            <div class="section-header">
                <h2>Academic Year Management</h2>
                <button class="btn-add" onclick="openModal('academic-year')">
                    <i class="ri-add-line"></i> Add Academic Year
                </button>
            </div>
            
            <div class="table-wrapper">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academicYears as $ay): ?>
                            <?php 
                                $semesterText = ['1' => 'First Semester', '2' => 'Second Semester', '3' => 'Summer'][strval($ay['semester'])];
                            ?>
                            <tr class="<?= $ay['is_active'] ? 'active-ay' : '' ?>">
                                <td><strong><?= $ay['year_start'] ?> - <?= $ay['year_end'] ?></strong></td>
                                <td><?= $semesterText ?></td>
                                <td>
                                    <span class="status-badge <?= $ay['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $ay['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editItem("academic-year", <?= json_encode($ay) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='setActiveAY(<?= $ay['id'] ?>, <?= $ay['is_active'] ? "false" : "true" ?>)'>
                                        <i class="ri-checkbox-<?= $ay['is_active'] ? 'fill' : 'blank' ?>-circle-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RESEARCH STATUSES TAB -->
        <div id="research-statuses-tab" class="tab-content">
            <div class="section-header">
                <h2>Research Status Management</h2>
                <button class="btn-add" onclick="openModal('research-status')">
                    <i class="ri-add-line"></i> Add Status
                </button>
            </div>
            
            <div class="table-wrapper">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Status Name</th>
                            <th>Description</th>
                            <th>Color</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($researchStatuses as $status): ?>
                            <tr class="<?= $status['is_active'] ? '' : 'inactive-row' ?>">
                                <td><strong><?= $status['display_order'] ?></strong></td>
                                <td><?= htmlspecialchars($status['name']) ?></td>
                                <td><?= htmlspecialchars($status['description'] ?? '') ?></td>
                                <td>
                                    <span class="color-preview" style="background: <?= $status['color'] ?>"></span>
                                    <?= $status['color'] ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $status['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-edit" onclick='editItem("research-status", <?= json_encode($status) ?>)'>
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn-toggle" onclick='toggleStatus("research-status", <?= $status['id'] ?>, <?= $status['is_active'] ? "false" : "true" ?>)'>
                                        <i class="ri-toggle-<?= $status['is_active'] ? 'fill' : 'line' ?>"></i>
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

    <!-- Universal Modal -->
    <div class="modal" id="settingsModal">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h3 id="modalTitle">Add Item</h3>
            
            <form id="settingsForm">
                <input type="hidden" id="itemId" name="item_id">
                <input type="hidden" id="itemType" name="item_type">
                <input type="hidden" id="formAction" name="action">
                
                <!-- Program Fields -->
                <div class="form-group" id="programCodeGroup">
                    <label>Program Code <span class="required">*</span></label>
                    <input type="text" id="programCode" name="code" placeholder="e.g., BSIT">
                </div>
                
                <div class="form-group" id="programNameGroup">
                    <label>Program Name <span class="required">*</span></label>
                    <input type="text" id="programName" name="name" placeholder="e.g., Bachelor of Science in Information Technology">
                </div>
                
                <div class="form-group" id="programDescGroup">
                    <label>Description</label>
                    <textarea id="programDesc" name="description" rows="3"></textarea>
                </div>
                
                <!-- Academic Year Fields -->
                <div class="form-group" id="yearStartGroup">
                    <label>Start Year <span class="required">*</span></label>
                    <input type="number" id="yearStart" name="year_start" placeholder="2024">
                </div>
                
                <div class="form-group" id="yearEndGroup">
                    <label>End Year <span class="required">*</span></label>
                    <input type="number" id="yearEnd" name="year_end" placeholder="2025">
                </div>
                
                <div class="form-group" id="semesterGroup">
                    <label>Semester <span class="required">*</span></label>
                    <select id="semester" name="semester">
                        <option value="1">First Semester</option>
                        <option value="2">Second Semester</option>
                        <option value="3">Summer</option>
                    </select>
                </div>
                
                <!-- Research Status Fields -->
                <div class="form-group" id="statusNameGroup">
                    <label>Status Name <span class="required">*</span></label>
                    <input type="text" id="statusName" name="status_name" placeholder="e.g., Proposal">
                </div>
                
                <div class="form-group" id="statusDescGroup">
                    <label>Description</label>
                    <textarea id="statusDesc" name="status_description" rows="3"></textarea>
                </div>
                
                <div class="form-group" id="statusColorGroup">
                    <label>Color</label>
                    <input type="color" id="statusColor" name="color" value="#007bff">
                </div>
                
                <div class="form-group" id="displayOrderGroup">
                    <label>Display Order</label>
                    <input type="number" id="displayOrder" name="display_order" value="0">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/timeout.js"></script>
    <script src="js/settings.js"></script>
</body>
</html>