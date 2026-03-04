<?php
include("connect.php");
include('php/get_setting.php');
include("check_session.php");
session_start();
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$groups = [];
$groupStmt = $con->query("SELECT * FROM groups ORDER BY name");
$allGroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allGroups as $g) {
    $groupId     = $g['id'];
    $groupName   = $g['name'];
    $researchTitle = $g['research_title'] ?? null;
    $titleStatus   = $g['title_status'] ?? 'missing';

    $memberStmt = $con->prepare("
        SELECT id, COALESCE(NULLIF(TRIM(full_name), ''), TRIM(firstname || ' ' || middlename || ' ' || lastname)) AS name
        FROM student
        WHERE group_id = :group_id AND (is_leader IS NULL OR is_leader = FALSE)
        ORDER BY full_name
    ");
    $memberStmt->execute(['group_id' => $groupId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

    $leaderStmt = $con->prepare("
        SELECT COALESCE(NULLIF(TRIM(full_name), ''), TRIM(firstname || ' ' || middlename || ' ' || lastname))
        FROM student
        WHERE group_id = :group_id AND is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['group_id' => $groupId]);
    $leader = $leaderStmt->fetchColumn();

    $adviserStmt = $con->prepare("SELECT a.name FROM advisor a JOIN groups g ON a.advisor_id = CAST(g.advisor_id AS VARCHAR) WHERE g.id = :group_id");
    $adviserStmt->execute(['group_id' => $groupId]);
    $adviser = $adviserStmt->fetchColumn();

    $programStmt = $con->prepare("SELECT program FROM student WHERE group_id = :group_id LIMIT 1");
    $programStmt->execute(['group_id' => $groupId]);
    $program = $programStmt->fetchColumn() ?: '';

    $sdgStmt = $con->prepare("SELECT us.id, us.name FROM un_sdgs us JOIN group_sdgs gs ON us.id = gs.sdg_id WHERE gs.group_id = :group_id ORDER BY us.name");
    $sdgStmt->execute(['group_id' => $groupId]);
    $assignedSdgs = $sdgStmt->fetchAll(PDO::FETCH_ASSOC);

    $thrustStmt = $con->prepare("SELECT rt.id, rt.name FROM research_thrusts rt JOIN group_thrusts gt ON rt.id = gt.thrust_id WHERE gt.group_id = :group_id ORDER BY rt.name");
    $thrustStmt->execute(['group_id' => $groupId]);
    $assignedThrusts = $thrustStmt->fetchAll(PDO::FETCH_ASSOC);

    $groups[] = [
        'group_id'       => $groupId,
        'group_name'     => $groupName,
        'research_title' => $researchTitle,
        'title_status'   => $titleStatus,
        'adviser'        => $adviser,
        'leader'         => $leader,
        'members'        => $members,
        'sdgs'           => $assignedSdgs,
        'thrusts'        => $assignedThrusts,
        'program'        => $program,
    ];
}

$allSdgs    = $con->query("SELECT * FROM un_sdgs ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allThrusts = $con->query("SELECT * FROM research_thrusts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$adviserNames = array_unique(array_filter(array_column($groups, 'adviser')));
sort($adviserNames);
$programs = array_unique(array_filter(array_column($groups, 'program')));
sort($programs);

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status FROM system_notifications
    WHERE (recipient_type = 'all' OR recipient_type = 'coordinators' OR (recipient_type = 'specific' AND recipient_id = :user_id))
    AND status != 'deleted' ORDER BY created_at DESC LIMIT 10
");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
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
<style>
.toast-notification{position:fixed;top:20px;right:20px;padding:15px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:10000;display:flex;align-items:center;gap:10px;min-width:300px;animation:slideIn .3s ease-out;font-size:14px}
.toast-notification.success{background:#d4edda;color:#155724;border-left:4px solid #28a745}
.toast-notification.error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
.toast-notification i{font-size:20px}
@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
.toast-notification.removing{animation:slideOut .3s ease-in}
@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}

.manage-section {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}

.manage-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.manage-section-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.manage-section-icon.sdg-icon {
    background: #e3f2fd;
    color: #1a56db;
}

.manage-section-icon.thrust-icon {
    background: #fff3e0;
    color: #d97706;
}

.manage-section-title h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px;
    color: #1f2937;
}

.manage-section-title p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.items-list {
    margin-bottom: 20px;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.item-row:last-child {
    border-bottom: none;
}

.item-number {
    font-size: 12px;
    font-weight: 600;
    color: #9ca3af;
    min-width: 24px;
}

.item-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.item-dot.sdg-dot {
    background: #1a56db;
}

.item-dot.thrust-dot {
    background: #d97706;
}

.item-name {
    flex: 1;
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
}

.item-delete {
    cursor: pointer;
    color: #dc3545;
    font-size: 16px;
    opacity: 0;
    transition: opacity .2s, color .2s;
}

.item-row:hover .item-delete {
    opacity: 1;
}

.item-delete:hover {
    color: #a71d2a;
}

.add-item-form {
    display: flex;
    gap: 8px;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}

.add-item-form input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color .2s;
}

.add-item-form input:focus {
    outline: none;
    border-color: #1a56db;
}

.add-item-form button {
    padding: 10px 20px;
    background: #1a56db;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: background .2s;
}

.add-item-form button:hover {
    background: #1648c0;
}

.add-item-form button.thrust-btn {
    background: #d97706;
}

.add-item-form button.thrust-btn:hover {
    background: #b45309;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

.sdg-thrust-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
}

.confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.confirm-modal.active {
    display: flex;
}

.confirm-modal-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.confirm-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.confirm-modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #fee;
    color: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.confirm-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.confirm-modal-message {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 24px;
    line-height: 1.5;
}

.confirm-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.confirm-modal-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.confirm-modal-btn.cancel {
    background: #f3f4f6;
    color: #374151;
}

.confirm-modal-btn.cancel:hover {
    background: #e5e7eb;
}

.confirm-modal-btn.confirm {
    background: #dc3545;
    color: white;
}

.confirm-modal-btn.confirm:hover {
    background: #c82333;
}
</style>
</head>
<body>
<?php include("templates/aside_coordinator.html"); ?>

<main class="main-content">

    <div class="rm-header">
        <div>
            <div class="rm-header-title">
                <span class="title-accent"></span>
                Research Management
            </div>
            <div class="rm-header-subtitle">
                Manage UN SDGs &amp; Research Thrusts, view groups and alignments.
            </div>
        </div>
    </div>

    <div class="rm-tabs-wrapper">
        <ul class="rm-tabs" id="rmTabs">
            <li class="rm-tab active" data-tab="sdg-thrusts">
                <i class="ri-global-line"></i>
                UN SDGs &amp; Thrusts
                <span class="tab-badge"><?= count($allSdgs) + count($allThrusts) ?></span>
            </li>
            <li class="rm-tab" data-tab="groups">
                <i class="ri-group-line"></i>
                Groups
                <span class="tab-badge"><?= count($groups) ?></span>
            </li>
        </ul>
    </div>

    <div class="rm-panel active" id="panel-sdg-thrusts">
        <div class="sdg-thrust-grid">

            <div class="manage-section">
                <div class="manage-section-header">
                    <div class="manage-section-icon sdg-icon">
                        <i class="ri-global-line"></i>
                    </div>
                    <div class="manage-section-title">
                        <h3>UN Sustainable Development Goals</h3>
                        <p><?= count($allSdgs) ?> SDG<?= count($allSdgs) !== 1 ? 's' : '' ?> · Managed by Advisors</p>
                    </div>
                </div>

                <div class="items-list" id="sdgList">
                    <?php if (empty($allSdgs)): ?>
                        <div class="empty-state">
                            <i class="ri-global-line"></i>
                            <p>No SDGs added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allSdgs as $i => $sdg): ?>
                        <div class="item-row" data-id="<?= $sdg['id'] ?>">
                            <span class="item-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="item-dot sdg-dot"></span>
                            <span class="item-name"><?= htmlspecialchars($sdg['name']) ?></span>
                            <i class="ri-delete-bin-line item-delete" onclick="deleteItem('sdg', <?= $sdg['id'] ?>, this)"></i>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="add-item-form">
                    <input type="text" id="newSdgName" placeholder="Enter new UN SDG name..." onkeydown="if(event.key==='Enter') addItem('sdg')">
                    <button onclick="addItem('sdg')"><i class="ri-add-line"></i> Add SDG</button>
                </div>
            </div>

            <div class="manage-section">
                <div class="manage-section-header">
                    <div class="manage-section-icon thrust-icon">
                        <i class="ri-flashlight-line"></i>
                    </div>
                    <div class="manage-section-title">
                        <h3>Research Thrusts</h3>
                        <p><?= count($allThrusts) ?> Thrust<?= count($allThrusts) !== 1 ? 's' : '' ?> · Managed by Advisors</p>
                    </div>
                </div>

                <div class="items-list" id="thrustList">
                    <?php if (empty($allThrusts)): ?>
                        <div class="empty-state">
                            <i class="ri-flashlight-line"></i>
                            <p>No Research Thrusts added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allThrusts as $i => $thrust): ?>
                        <div class="item-row" data-id="<?= $thrust['id'] ?>">
                            <span class="item-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="item-dot thrust-dot"></span>
                            <span class="item-name"><?= htmlspecialchars($thrust['name']) ?></span>
                            <i class="ri-delete-bin-line item-delete" onclick="deleteItem('thrust', <?= $thrust['id'] ?>, this)"></i>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="add-item-form">
                    <input type="text" id="newThrustName" placeholder="Enter new Research Thrust name..." onkeydown="if(event.key==='Enter') addItem('thrust')">
                    <button class="thrust-btn" onclick="addItem('thrust')"><i class="ri-add-line"></i> Add Thrust</button>
                </div>
            </div>

        </div>
    </div>

    <div class="rm-panel" id="panel-groups">

        <div class="groups-toolbar">
            <div class="groups-search">
                <i class="ri-search-line"></i>
                <input type="text" id="groupSearch" placeholder="Search groups...">
            </div>
            <div class="filter-label">Sort:</div>
            <div class="filter-chip-group" id="sortChips">
                <div class="filter-chip active" data-sort="az"><i class="ri-sort-asc"></i> A–Z</div>
                <div class="filter-chip" data-sort="za"><i class="ri-sort-desc"></i> Z–A</div>
            </div>
            <?php if (!empty($adviserNames)): ?>
            <div class="filter-label">Adviser:</div>
            <select class="filter-select" id="adviserFilter">
                <option value="">All Advisers</option>
                <?php foreach ($adviserNames as $adv): ?>
                    <option value="<?= htmlspecialchars($adv) ?>"><?= htmlspecialchars($adv) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (!empty($programs)): ?>
            <div class="filter-label">Program:</div>
            <select class="filter-select" id="programFilter">
                <option value="">All Programs</option>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <div class="filter-label">Status:</div>
            <select class="filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
                <option value="missing">Missing</option>
            </select>
            <div class="groups-count" id="groupsCount">
                <span id="visibleCount"><?= count($groups) ?></span> of <?= count($groups) ?> groups
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="group-row">
                <div class="group-list-container-mod">
                    <div class="card student-card">
                        <label>All Groups</label>
                        <div class="student-dropdown-wrap" id="groupsGrid">
                            <?php foreach ($groups as $grp):
                                $programClass = in_array($grp['program'], ['DIT','BSIT','CS']) ? $grp['program'] : 'other';
                            ?>
                            <div class="group-item"
                                 data-group-id="<?= $grp['group_id'] ?>"
                                 data-name="<?= strtolower(htmlspecialchars($grp['group_name'])) ?>"
                                 data-adviser="<?= htmlspecialchars($grp['adviser'] ?? '') ?>"
                                 data-program="<?= htmlspecialchars($grp['program'] ?? '') ?>"
                                 data-status="<?= htmlspecialchars($grp['title_status']) ?>">

                                <?php if (!empty($grp['program'])): ?>
                                <span class="group-program-badge <?= $programClass ?>"><?= htmlspecialchars($grp['program']) ?></span>
                                <?php endif; ?>

                                <div class="group-name">
                                    <?= htmlspecialchars($grp['group_name']) ?>
                                    <i class="ri-more-2-fill group-menu" onclick="toggleMembers(this)"></i>
                                </div>

                                <div class="members-list" style="display:none;">
                                    <hr class="group-divider">
                                    <?php if (!empty($grp['research_title'])): ?>
                                        <div class="group-meta research-title">
                                            <div style="flex:1;"><b>Research Title:</b> <?= htmlspecialchars($grp['research_title']) ?></div>
                                            <?php
                                            $sc = 'status-missing'; $st = 'Missing';
                                            if ($grp['title_status'] === 'pending')   { $sc = 'status-pending';  $st = 'Pending'; }
                                            elseif ($grp['title_status'] === 'approved') { $sc = 'status-approved'; $st = 'Approved'; }
                                            elseif ($grp['title_status'] === 'rejected') { $sc = 'status-rejected'; $st = 'Rejected'; }
                                            ?>
                                            <span class="status-badge <?= $sc ?>"><?= $st ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($grp['adviser'])): ?>
                                        <div class="group-meta adviser"><b>Adviser:</b> <?= htmlspecialchars($grp['adviser']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($grp['leader'])): ?>
                                        <div class="group-meta leader"><b>Leader:</b> <?= htmlspecialchars($grp['leader']) ?></div>
                                    <?php endif; ?>

                                    <div class="sdg-thrust-section">
                                        <h4><i class="ri-global-line"></i> UN SDGs</h4>
                                        <div class="tags-container">
                                            <?php if (empty($grp['sdgs'])): ?>
                                                <span style="color:#999;font-size:12px;font-style:italic;">No SDGs assigned yet</span>
                                            <?php else: ?>
                                                <?php foreach ($grp['sdgs'] as $sdg): ?>
                                                <span class="tag" style="padding-right:10px;"><?= htmlspecialchars($sdg['name']) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <h4 style="margin-top:12px;"><i class="ri-flashlight-line"></i> Research Thrusts</h4>
                                        <div class="tags-container">
                                            <?php if (empty($grp['thrusts'])): ?>
                                                <span style="color:#999;font-size:12px;font-style:italic;">No Research Thrusts assigned yet</span>
                                            <?php else: ?>
                                                <?php foreach ($grp['thrusts'] as $thrust): ?>
                                                <span class="tag thrust" style="padding-right:10px;"><?= htmlspecialchars($thrust['name']) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="lists"><p><i class="ri-group-line"></i> Members</p></div>
                                    <div class="members">
                                        <?php foreach ($grp['members'] as $member): ?>
                                            <div class="member-item"><?= htmlspecialchars($member['name']) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="no-groups-msg" id="noGroupsMsg" style="display:none;">
                                <i class="ri-search-line"></i>
                                <p>No groups match your filters.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="space"></div>
</main>

<div class="confirm-modal" id="confirmModal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-header">
            <div class="confirm-modal-icon">
                <i class="ri-error-warning-line"></i>
            </div>
            <h3 class="confirm-modal-title">Confirm Deletion</h3>
        </div>
        <p class="confirm-modal-message" id="confirmMessage"></p>
        <div class="confirm-modal-actions">
            <button class="confirm-modal-btn cancel" onclick="closeConfirmModal()">Cancel</button>
            <button class="confirm-modal-btn confirm" id="confirmBtn">Delete</button>
        </div>
    </div>
</div>

<script src="js/timeout.js"></script>
<script src="js/edit.js"></script>
<script>
    const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/session_monitor.js"></script>
<script>
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

document.querySelectorAll('.rm-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.rm-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.rm-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });
});

const groupSearch   = document.getElementById('groupSearch');
const adviserFilter = document.getElementById('adviserFilter');
const programFilter = document.getElementById('programFilter');
const statusFilter  = document.getElementById('statusFilter');
const groupsGrid    = document.getElementById('groupsGrid');
const noGroupsMsg   = document.getElementById('noGroupsMsg');
const visibleCount  = document.getElementById('visibleCount');
let currentSort = 'az';

document.querySelectorAll('#sortChips .filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('#sortChips .filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentSort = chip.dataset.sort;
        applyFilters();
    });
});

function applyFilters() {
    const search  = groupSearch  ? groupSearch.value.toLowerCase().trim() : '';
    const adviser = adviserFilter ? adviserFilter.value : '';
    const program = programFilter ? programFilter.value : '';
    const status  = statusFilter  ? statusFilter.value  : '';
    const items = Array.from(groupsGrid.querySelectorAll('.group-item'));
    let visible = 0;
    items.forEach(item => {
        const match = (!search  || (item.dataset.name||'').includes(search))
                   && (!adviser || item.dataset.adviser === adviser)
                   && (!program || item.dataset.program === program)
                   && (!status  || item.dataset.status  === status);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const vis = items.filter(i => i.style.display !== 'none');
    vis.sort((a,b) => currentSort === 'az' ? a.dataset.name.localeCompare(b.dataset.name) : b.dataset.name.localeCompare(a.dataset.name));
    vis.forEach(i => groupsGrid.appendChild(i));
    noGroupsMsg.style.display = visible === 0 ? '' : 'none';
    if (visibleCount) visibleCount.textContent = visible;
}

if (groupSearch)   groupSearch.addEventListener('input', applyFilters);
if (adviserFilter) adviserFilter.addEventListener('change', applyFilters);
if (programFilter) programFilter.addEventListener('change', applyFilters);
if (statusFilter)  statusFilter.addEventListener('change', applyFilters);
applyFilters();

let confirmCallback = null;

function showConfirmModal(message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = onConfirm;
    modal.classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    confirmCallback = null;
}

document.getElementById('confirmModal').addEventListener('click', (e) => {
    if (e.target.id === 'confirmModal') closeConfirmModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('confirmModal').classList.contains('active')) closeConfirmModal();
});

document.getElementById('confirmBtn').addEventListener('click', async () => {
    if (confirmCallback) {
        const callback = confirmCallback;
        closeConfirmModal();
        await callback();
    }
});

function toggleMembers(icon) {
    const groupItem   = icon.closest('.group-item');
    const membersList = groupItem.querySelector('.members-list');
    const show = membersList.style.display === 'none' || membersList.style.display === '';
    membersList.style.display = show ? 'block' : 'none';
    icon.classList.toggle('rotated', show);
}

async function addItem(type) {
    const inputId = type === 'sdg' ? 'newSdgName' : 'newThrustName';
    const listId  = type === 'sdg' ? 'sdgList'   : 'thrustList';
    const name = document.getElementById(inputId).value.trim();
    if (!name) { showToast('Name cannot be empty.', 'error'); return; }
    try {
        const res = await fetch('php/manage_coordinator.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: `add_${type}`, name })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById(inputId).value = '';
            const list = document.getElementById(listId);
            const empty = list.querySelector('.empty-state');
            if (empty) empty.remove();
            const currentCount = list.querySelectorAll('.item-row').length;
            const row = document.createElement('div');
            row.className = 'item-row';
            row.dataset.id = data.id;
            const dotClass = type === 'sdg' ? 'sdg-dot' : 'thrust-dot';
            row.innerHTML = `
                <span class="item-number">${String(currentCount + 1).padStart(2, '0')}</span>
                <span class="item-dot ${dotClass}"></span>
                <span class="item-name">${name}</span>
                <i class="ri-delete-bin-line item-delete" onclick="deleteItem('${type}', ${data.id}, this)"></i>
            `;
            list.appendChild(row);
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} added successfully!`, 'success');
            updateTabBadge();
            updateCounts(type);
        } else {
            showToast(data.message || 'Failed to add item.', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteItem(type, id, iconEl) {
    const row = iconEl.closest('.item-row');
    const name = row.querySelector('.item-name').textContent;
    showConfirmModal(
        `Delete "${name}"? This will remove it from all groups.`,
        async () => {
            try {
                const res = await fetch('php/manage_coordinator.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: `delete_${type}`, id })
                });
                const data = await res.json();
                if (data.success) {
                    row.remove();
                    showToast('Deleted successfully!', 'success');
                    updateTabBadge();
                    updateCounts(type);
                    renumberItems(type);
                    const listId = type === 'sdg' ? 'sdgList' : 'thrustList';
                    const list = document.getElementById(listId);
                    if (!list.querySelector('.item-row')) {
                        const empty = document.createElement('div');
                        empty.className = 'empty-state';
                        const icon = type === 'sdg' ? 'ri-global-line' : 'ri-flashlight-line';
                        empty.innerHTML = `<i class="${icon}"></i><p>No ${type === 'sdg' ? 'SDGs' : 'Research Thrusts'} added yet.</p>`;
                        list.appendChild(empty);
                    }
                } else {
                    showToast(data.message || 'Failed to delete.', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        }
    );
}

function renumberItems(type) {
    const listId = type === 'sdg' ? 'sdgList' : 'thrustList';
    const list = document.getElementById(listId);
    list.querySelectorAll('.item-row').forEach((row, index) => {
        const numberSpan = row.querySelector('.item-number');
        if (numberSpan) numberSpan.textContent = String(index + 1).padStart(2, '0');
    });
}

function updateCounts(type) {
    const listId = type === 'sdg' ? 'sdgList' : 'thrustList';
    const list = document.getElementById(listId);
    const count = list.querySelectorAll('.item-row').length;
    const section = list.closest('.manage-section');
    const countText = section.querySelector('.manage-section-title p');
    if (countText) {
        const label = type === 'sdg' ? 'SDG' : 'Thrust';
        countText.textContent = `${count} ${label}${count !== 1 ? 's' : ''} · Managed by Advisors`;
    }
}

function updateTabBadge() {
    const sdgCount    = document.getElementById('sdgList').querySelectorAll('.item-row').length;
    const thrustCount = document.getElementById('thrustList').querySelectorAll('.item-row').length;
    const badge = document.querySelector('[data-tab="sdg-thrusts"] .tab-badge');
    if (badge) badge.textContent = sdgCount + thrustCount;
}
</script>

</body>
</html>