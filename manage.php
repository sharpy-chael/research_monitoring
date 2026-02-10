<?php
include("connect.php");
session_start();
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$groups = [];
$groupStmt = $con->query("SELECT * FROM groups ORDER BY name");
$allGroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allGroups as $g) {
    $groupId = $g['id'];
    $groupName = $g['name'];
    $researchTitle = $g['research_title'] ?? null;
    $titleStatus = $g['title_status'] ?? 'missing';

    
    $memberStmt = $con->prepare("
        SELECT id, name 
        FROM student 
        WHERE group_id = :group_id 
        AND (is_leader IS NULL OR is_leader = FALSE)
        ORDER BY name
    ");
    $memberStmt->execute(['group_id' => $groupId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

    
    $leaderStmt = $con->prepare("
        SELECT name 
        FROM student 
        WHERE group_id = :group_id AND is_leader = TRUE
        LIMIT 1
    ");
    $leaderStmt->execute(['group_id' => $groupId]);
    $leader = $leaderStmt->fetchColumn();

    
    $adviserStmt = $con->prepare("
        SELECT a.name 
        FROM advisor a
        JOIN groups g ON a.advisor_id = CAST(g.advisor_id AS VARCHAR)
        WHERE g.id = :group_id
    ");
    $adviserStmt->execute(['group_id' => $groupId]);
    $adviser = $adviserStmt->fetchColumn();

    
    $programStmt = $con->prepare("
        SELECT program FROM student WHERE group_id = :group_id LIMIT 1
    ");
    $programStmt->execute(['group_id' => $groupId]);
    $program = $programStmt->fetchColumn() ?: '';

    
    $sdgStmt = $con->prepare("
        SELECT us.id, us.name 
        FROM un_sdgs us
        JOIN group_sdgs gs ON us.id = gs.sdg_id
        WHERE gs.group_id = :group_id
        ORDER BY us.name
    ");
    $sdgStmt->execute(['group_id' => $groupId]);
    $assignedSdgs = $sdgStmt->fetchAll(PDO::FETCH_ASSOC);

    
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
                View groups, SDG alignments, and research thrusts across all student teams.
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

            
            <div class="sdg-thrust-card">
                <div class="sdg-thrust-card-header">
                    <div class="header-icon sdg-icon"><i class="ri-global-line"></i></div>
                    <div>
                        <h3>UN Sustainable Development Goals</h3>
                        <p><?= count($allSdgs) ?> SDG<?= count($allSdgs) !== 1 ? 's' : '' ?> &nbsp;·&nbsp; Managed by Advisors</p>
                    </div>
                </div>
                <div class="sdg-thrust-card-body">
                    <?php if (empty($allSdgs)): ?>
                        <div class="sdg-empty">
                            <i class="ri-global-line"></i>
                            No SDGs added yet. Advisors can create them.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allSdgs as $i => $sdg): ?>
                        <div class="sdg-item-row">
                            <span class="item-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="sdg-dot"></span>
                            <span><?= htmlspecialchars($sdg['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="sdg-thrust-card">
                <div class="sdg-thrust-card-header">
                    <div class="header-icon thrust-icon"><i class="ri-flashlight-line"></i></div>
                    <div>
                        <h3>Research Thrusts</h3>
                        <p><?= count($allThrusts) ?> Thrust<?= count($allThrusts) !== 1 ? 's' : '' ?> &nbsp;·&nbsp; Managed by Advisors</p>
                    </div>
                </div>
                <div class="sdg-thrust-card-body">
                    <?php if (empty($allThrusts)): ?>
                        <div class="sdg-empty">
                            <i class="ri-flashlight-line"></i>
                            No Research Thrusts added yet. Advisors can create them.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allThrusts as $i => $thrust): ?>
                        <div class="sdg-item-row">
                            <span class="item-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="thrust-dot"></span>
                            <span><?= htmlspecialchars($thrust['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                <div class="filter-chip active" data-sort="az">
                    <i class="ri-sort-asc"></i> A–Z
                </div>
                <div class="filter-chip" data-sort="za">
                    <i class="ri-sort-desc"></i> Z–A
                </div>
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
                                <span class="group-program-badge <?= $programClass ?>">
                                    <?= htmlspecialchars($grp['program']) ?>
                                </span>
                                <?php endif; ?>

                                
                                <div class="group-name">
                                    <?= htmlspecialchars($grp['group_name']) ?>
                                    <i class="ri-more-2-fill group-menu" onclick="toggleMembers(this)"></i>
                                    <i class="ri-delete-bin-line group-delete"
                                       onclick="deleteGroup(<?= $grp['group_id'] ?>, this)"
                                       title="Delete group"></i>
                                </div>

                                
                                <div class="members-list" style="display:none;">

                                    <hr class="group-divider">

                                    <?php if (!empty($grp['research_title'])): ?>
                                        <div class="group-meta research-title">
                                            <div style="flex:1;">
                                                <b>Research Title:</b> <?= htmlspecialchars($grp['research_title']) ?>
                                            </div>
                                            <?php
                                            $statusClass = 'status-missing';
                                            $statusText  = 'Missing';
                                            if ($grp['title_status'] === 'pending')  { $statusClass = 'status-pending';  $statusText = 'Pending'; }
                                            elseif ($grp['title_status'] === 'approved') { $statusClass = 'status-approved'; $statusText = 'Approved'; }
                                            elseif ($grp['title_status'] === 'rejected') { $statusClass = 'status-rejected'; $statusText = 'Rejected'; }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($grp['adviser'])): ?>
                                        <div class="group-meta adviser">
                                            <b>Adviser:</b> <?= htmlspecialchars($grp['adviser']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($grp['leader'])): ?>
                                        <div class="group-meta leader">
                                            <b>Leader:</b> <?= htmlspecialchars($grp['leader']) ?>
                                        </div>
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

<script src="js/timeout.js"></script>
<script src="js/edit.js"></script>

<script>

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
    const search   = (groupSearch  ? groupSearch.value.toLowerCase().trim()  : '');
    const adviser  = (adviserFilter ? adviserFilter.value : '');
    const program  = (programFilter ? programFilter.value : '');
    const status   = (statusFilter  ? statusFilter.value  : '');

    const items = Array.from(groupsGrid.querySelectorAll('.group-item'));
    let visible = 0;

    
    items.forEach(item => {
        const name    = item.dataset.name    || '';
        const adv     = item.dataset.adviser || '';
        const prog    = item.dataset.program || '';
        const stat    = item.dataset.status  || '';

        const matchSearch  = !search  || name.includes(search);
        const matchAdviser = !adviser || adv === adviser;
        const matchProgram = !program || prog === program;
        const matchStatus  = !status  || stat === status;

        if (matchSearch && matchAdviser && matchProgram && matchStatus) {
            item.style.display = '';
            visible++;
        } else {
            item.style.display = 'none';
        }
    });

    
    const visibleItems = items.filter(i => i.style.display !== 'none');
    visibleItems.sort((a, b) => {
        const nameA = a.dataset.name;
        const nameB = b.dataset.name;
        return currentSort === 'az' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
    });
    visibleItems.forEach(item => groupsGrid.appendChild(item));

    
    noGroupsMsg.style.display = visible === 0 ? '' : 'none';
    if (visibleCount) visibleCount.textContent = visible;
}

if (groupSearch)   groupSearch.addEventListener('input', applyFilters);
if (adviserFilter) adviserFilter.addEventListener('change', applyFilters);
if (programFilter) programFilter.addEventListener('change', applyFilters);
if (statusFilter)  statusFilter.addEventListener('change', applyFilters);

applyFilters();

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

async function deleteGroup(id, elem) {
    if (!confirm('Delete this group? This will remove all students from this group.')) return;
    try {
        const res = await fetch('php/add_student.php', {
            method: 'POST',
            body: JSON.stringify({ delete_group_id: id }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            elem.closest('.group-item').remove();
            applyFilters();
            showToast('Group deleted successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to delete group.', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

function toggleMembers(icon) {
    const groupItem  = icon.closest('.group-item');
    const membersList = groupItem.querySelector('.members-list');
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