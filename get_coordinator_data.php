<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include("connect.php");
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
        ]
    ]);
    exit;
}

// LINE CHART: Aggregate submissions over time (all groups combined)
// Shows total approved tasks per date across all groups
$timelineStmt = $con->query("
    SELECT 
        DATE(uploaded_at) as upload_date,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
    FROM uploads
    GROUP BY DATE(uploaded_at)
    ORDER BY upload_date ASC
");
$timeline = $timelineStmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$approvedData = [];
$pendingData = [];
$rejectedData = [];

foreach ($timeline as $row) {
    $dates[] = date("M d", strtotime($row['upload_date']));
    $approvedData[] = $row['approved_count'];
    $pendingData[] = $row['pending_count'];
    $rejectedData[] = $row['rejected_count'];
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
            'label' => 'Pending',
            'data' => $pendingData,
            'borderColor' => 'rgb(255, 193, 7)',
            'backgroundColor' => 'rgba(255, 193, 7, 0.2)',
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

// PIE CHART: Overall status distribution across ALL groups
$totalApproved = 0;
$totalPending = 0;
$totalRejected = 0;
$totalMissing = 0;

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
    // Get latest uploads per task for this group
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
    
    // Count statuses for this group
    foreach ($uploadMap as $upload) {
        if ($upload['status'] === 'approved') {
            $totalApproved++;
        } elseif ($upload['status'] === 'pending') {
            $totalPending++;
        } elseif ($upload['status'] === 'rejected') {
            $totalRejected++;
        }
    }
    
    // Each group has 6 tasks total
    $totalMissing += (6 - count($uploadMap));
}

$response = [
    'line' => [
        'labels' => $dates,
        'datasets' => $datasets
    ],
    'pie' => [
        'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
        'data' => [$totalApproved, $totalPending, $totalRejected, $totalMissing]
    ]
];

echo json_encode($response);
?>