<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include(__DIR__ . "/../connect.php");
session_start();

if (!isset($_SESSION['submit'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get ALL groups in the system (coordinator sees everything)
$groupsStmt = $con->query("
    SELECT id as group_id, name as group_name
    FROM groups
    ORDER BY name
");
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($groups)) {
    echo json_encode([
        'line' => [
            'labels' => ['No Data'],
            'datasets' => []
        ],
        'pie' => [
            'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
            'data' => [0, 0, 0, 100]
        ],
        'progress' => 0
    ]);
    exit;
}

// Calculate total possible items (each group has 12 items: 6 chapters + 6 milestones)
$totalPossible = count($groups) * 12;

// LINE CHART: Cumulative progress percentages over time
// Get all uploads sorted by date and time
$timelineStmt = $con->query("
    SELECT 
        DATE(uploaded_at) as upload_date,
        uploaded_at,
        school_id,
        task_name,
        status
    FROM uploads
    ORDER BY uploaded_at ASC
");
$allUploads = $timelineStmt->fetchAll(PDO::FETCH_ASSOC);

// Group uploads by date first
$dateGroups = [];
foreach ($allUploads as $upload) {
    $date = $upload['upload_date'];
    if (!isset($dateGroups[$date])) {
        $dateGroups[$date] = [];
    }
    $dateGroups[$date][] = $upload;
}

// Process each date completely, then save data point
$dates = [];
$approvedData = [];
$rejectedData = [];
$cumulativeApproved = [];
$cumulativeRejected = [];

foreach ($dateGroups as $date => $uploads) {
    // Process ALL uploads for this date
    foreach ($uploads as $upload) {
        $taskKey = $upload['school_id'] . '-' . $upload['task_name'];
        
        if ($upload['status'] === 'approved') {
            $cumulativeApproved[$taskKey] = true;
            unset($cumulativeRejected[$taskKey]);
        } elseif ($upload['status'] === 'rejected') {
            $cumulativeRejected[$taskKey] = true;
            unset($cumulativeApproved[$taskKey]);
        }
        // Pending doesn't affect approved/rejected counts
    }
    
    // AFTER processing all uploads for this date, save the data point
    $dates[] = date("M d", strtotime($date));
    $approvedData[] = round((count($cumulativeApproved) / $totalPossible) * 100, 2);
    $rejectedData[] = round((count($cumulativeRejected) / $totalPossible) * 100, 2);
}

// If no data, show empty state
if (empty($dates)) {
    $dates = ['No Data'];
    $datasets = [];
} else {
    $datasets = [
        [
            'label' => 'Approved',
            'data' => $approvedData,
            'borderColor' => 'rgb(76, 175, 80)',
            'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
            'fill' => false,
            'tension' => 0.3
        ],
        [
            'label' => 'Rejected',
            'data' => $rejectedData,
            'borderColor' => 'rgb(244, 67, 54)',
            'backgroundColor' => 'rgba(244, 67, 54, 0.2)',
            'fill' => false,
            'tension' => 0.3
        ]
    ];
}

// PIE CHART & PROGRESS: Overall status distribution across ALL groups (INCLUDING MILESTONES)
$totalApproved = 0;
$totalPending = 0;
$totalRejected = 0;
$totalMissing = 0;

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
    // COUNT CHAPTERS (6 total)
    $statusStmt = $con->prepare("
        SELECT task_name, status
        FROM uploads
        WHERE school_id IN (
            SELECT school_id FROM student WHERE group_id = :group_id
        )
        ORDER BY uploaded_at DESC
    ");
    $statusStmt->execute(['group_id' => $group_id]);
    $allUploads = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest upload per task
    $uploadMap = [];
    foreach ($allUploads as $upload) {
        if (!isset($uploadMap[$upload['task_name']])) {
            $uploadMap[$upload['task_name']] = $upload;
        }
    }
    
    // Count chapter statuses
    foreach ($uploadMap as $upload) {
        if ($upload['status'] === 'approved') {
            $totalApproved++;
        } elseif ($upload['status'] === 'pending') {
            $totalPending++;
        } elseif ($upload['status'] === 'rejected') {
            $totalRejected++;
        }
    }
    
    // Missing chapters
    $totalMissing += (6 - count($uploadMap));
    
    // COUNT MILESTONES (6 total)
    $milestoneStmt = $con->prepare("
        SELECT 
            g.title_status,
            gm.proposal_status,
            gm.final_defense_status,
            gm.copyright_status
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get UREC documents
    $urecStmt = $con->prepare("
        SELECT document_type, status
        FROM urec_documents
        WHERE group_id = :group_id
        ORDER BY uploaded_at DESC
    ");
    $urecStmt->execute(['group_id' => $group_id]);
    $urecDocs = $urecStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $urecMap = [];
    foreach ($urecDocs as $doc) {
        if (!isset($urecMap[$doc['document_type']])) {
            $urecMap[$doc['document_type']] = $doc;
        }
    }
    
    // Count milestone statuses (6 milestones)
    $milestoneStatuses = [];
    
    // 1. Title
    $milestoneStatuses['Title'] = $milestones['title_status'] ?? 'missing';
    
    // 2. Proposal
    $milestoneStatuses['Proposal'] = 'missing';
    if ($milestones && !empty($milestones['proposal_status'])) {
        $milestoneStatuses['Proposal'] = ($milestones['proposal_status'] === 'completed') ? 'approved' : $milestones['proposal_status'];
    }
    
    // 3. UREC Form
    $milestoneStatuses['UREC Form'] = isset($urecMap['UREC Form']) ? $urecMap['UREC Form']['status'] : 'missing';
    
    // 4. UREC Clearance
    $milestoneStatuses['UREC Clearance'] = isset($urecMap['UREC Clearance']) ? $urecMap['UREC Clearance']['status'] : 'missing';
    
    // 5. Final Defense
    $milestoneStatuses['Final Defense'] = 'missing';
    if ($milestones && !empty($milestones['final_defense_status'])) {
        $milestoneStatuses['Final Defense'] = ($milestones['final_defense_status'] === 'completed') ? 'approved' : $milestones['final_defense_status'];
    }
    
    // 6. Copyright
    $milestoneStatuses['Copyright/IP'] = 'missing';
    if ($milestones && !empty($milestones['copyright_status'])) {
        $milestoneStatuses['Copyright/IP'] = ($milestones['copyright_status'] === 'completed') ? 'approved' : $milestones['copyright_status'];
    }
    
    // Count milestone statuses
    foreach ($milestoneStatuses as $status) {
        if ($status === 'approved') {
            $totalApproved++;
        } elseif ($status === 'pending') {
            $totalPending++;
        } elseif ($status === 'rejected') {
            $totalRejected++;
        } else {
            $totalMissing++;
        }
    }
}

// Calculate overall progress percentage
$overallProgress = $totalPossible > 0 ? round(($totalApproved / $totalPossible) * 100) : 0;

$response = [
    'line' => [
        'labels' => $dates,
        'datasets' => $datasets
    ],
    'pie' => [
        'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
        'data' => [$totalApproved, $totalPending, $totalRejected, $totalMissing]
    ],
    'progress' => $overallProgress
];

echo json_encode($response);
?>