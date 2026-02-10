<?php
session_start();
include('connect.php');

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

// Fetch summary statistics
$statsQuery = $con->query("
    SELECT 
        COUNT(DISTINCT g.id) as total_groups,
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT a.id) as total_advisors
    FROM groups g
    LEFT JOIN student s ON g.id = s.group_id
    LEFT JOIN advisor a ON g.adviser_id = a.id
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Get status breakdown
$statusQuery = $con->query("
    SELECT 
        SUM(CASE WHEN u.status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN u.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM uploads u
");
$statusData = $statusQuery->fetch(PDO::FETCH_ASSOC);

// Get SDG distribution
$sdgQuery = $con->query("
    SELECT 
        COALESCE(sd.id, 0) as sdg_id,
        COALESCE(sd.name, 'Unassigned') as sdg_name,
        COUNT(g.id) as group_count
    FROM groups g
    LEFT JOIN un_sdgs sd ON g.sdg_id = sd.id
    GROUP BY sd.id, sd.name
    ORDER BY sd.id
");
$sdgData = $sdgQuery->fetchAll(PDO::FETCH_ASSOC);

// Get Research Thrust distribution
$thrustQuery = $con->query("
    SELECT 
        COALESCE(rt.name, 'Unassigned') as thrust_name,
        COUNT(g.id) as group_count
    FROM groups g
    LEFT JOIN research_thrusts rt ON g.thrust_id = rt.id
    GROUP BY rt.name
    ORDER BY group_count DESC
");
$thrustData = $thrustQuery->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/setup.css">
    <title>Monitoring Reports</title>
    <style>
    .toast {
        position: fixed;
        top: 30px;
        right: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        color: white;
        box-shadow: 0 6px 20px rgba(0,0,0,0.18);
        z-index: 99999;
        opacity: 0;
        transform: translateY(-16px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        pointer-events: none;
        max-width: 320px;
    }
    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }
    .toast.success {
        background: linear-gradient(135deg, #38a169 0%, #276749 100%);
    }
    .toast.error {
        background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
    }
    .toast i {
        font-size: 18px;
        flex-shrink: 0;
    }
    @media (max-width: 768px) {
        .toast {
            top: 16px;
            left: 16px;
            right: 16px;
            max-width: none;
        }
    }
    </style>
</head>
<body>
    <?php include("templates/aside_coordinator.html"); ?>

    <div id="exportToast" class="toast" role="alert" aria-live="polite"></div>
    
    <main class="main-content">
        <h1 id="head"><i class="ri-file-chart-line"></i> Monitoring Reports</h1>
        
        <!-- Summary Dashboard -->
        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-team-line" style="color: #ffffff;"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_groups'] ?></div>
                    <div class="stat-label">Total Groups</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line" style="color: #ffffff;"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_students'] ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line" style="color: #ffffff;"></i></div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['total_advisors'] ?></div>
                    <div class="stat-label">Total Advisors</div>
                </div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="report-section">
            <h2><i class="ri-pie-chart-line"></i> Submission Status Overview</h2>
            <div class="status-grid">
                <div class="status-item approved">
                    <i class="ri-checkbox-circle-line"></i>
                    <span class="status-count"><?= $statusData['approved'] ?></span>
                    <span class="status-label">Approved</span>
                </div>
                <div class="status-item pending">
                    <i class="ri-time-line"></i>
                    <span class="status-count"><?= $statusData['pending'] ?></span>
                    <span class="status-label">Pending</span>
                </div>
                <div class="status-item rejected">
                    <i class="ri-close-circle-line"></i>
                    <span class="status-count"><?= $statusData['rejected'] ?></span>
                    <span class="status-label">Rejected</span>
                </div>
            </div>
        </div>

        <!-- SDG Distribution -->
        <div class="report-section">
            <h2><i class="ri-global-line"></i> UN SDG Distribution</h2>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SDG Name</th>
                            <th>Groups Aligned</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalGroups = $stats['total_groups'];
                        foreach ($sdgData as $sdg): 
                            $percentage = $totalGroups > 0 ? round(($sdg['group_count'] / $totalGroups) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($sdg['sdg_name']) ?></td>
                                <td><strong><?= $sdg['group_count'] ?></strong></td>
                                <td><?= $percentage ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Research Thrust Distribution -->
        <div class="report-section">
            <h2><i class="ri-compass-3-line"></i> Research Thrust Distribution</h2>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Research Thrust</th>
                            <th>Groups Aligned</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($thrustData as $thrust): 
                            $percentage = $totalGroups > 0 ? round(($thrust['group_count'] / $totalGroups) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($thrust['thrust_name']) ?></td>
                                <td><strong><?= $thrust['group_count'] ?></strong></td>
                                <td><?= $percentage ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h2><i class="ri-download-line"></i> Export Reports</h2>
            <div class="export-buttons">
                <button class="btn-export" onclick="exportReport('status')">
                    <i class="ri-file-text-line"></i> Status Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('sdg')">
                    <i class="ri-global-line"></i> SDG Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('thrust')">
                    <i class="ri-compass-3-line"></i> Thrust Report (CSV)
                </button>
                <button class="btn-export" onclick="exportReport('full')">
                    <i class="ri-file-download-line"></i> Full Report (CSV)
                </button>
            </div>
        </div>
        <div class="space"></div>
    </main>

    <script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('exportToast');
        const icon = type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line';
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="${icon}"></i><span>${message}</span>`;
        toast.classList.add('show');
        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    async function exportReport(type) {
        try {
            const response = await fetch('php/export_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=${type}`
            });
            
            if (!response.ok) throw new Error('Server returned ' + response.status);
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}_report_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            const labels = { status: 'Status', sdg: 'SDG', thrust: 'Thrust', full: 'Full' };
            showToast(`${labels[type] ?? 'Report'} report downloaded successfully!`, 'success');
        } catch (error) {
            showToast('Export failed: ' + error.message, 'error');
        }
    }
    </script>
</body>
</html>