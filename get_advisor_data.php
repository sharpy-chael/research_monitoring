<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include("connect.php");
session_start();

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$advisor_id = $_SESSION['id'];

// Get all groups for this advisor
$groupsStmt = $con->prepare("
    SELECT id as group_id, name as group_name
    FROM groups
    WHERE adviser_id = :adviser_id
    ORDER BY name
");
$groupsStmt->execute(['adviser_id' => $advisor_id]);
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

// LINE CHART: Multiple lines (one per group) showing progress over time
$allDates = [];
$groupProgressData = [];

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
    // Get uploads timeline for this group
    $timelineStmt = $con->prepare("
        SELECT 
            DATE(uploaded_at) as upload_date,
            COUNT(DISTINCT CASE WHEN status = 'approved' THEN task_name END) as approved_count
        FROM uploads
        WHERE school_id IN (
            SELECT school_id FROM student WHERE group_id = :group_id
        )
        GROUP BY DATE(uploaded_at)
        ORDER BY upload_date ASC
    ");
    $timelineStmt->execute(['group_id' => $group_id]);
    $timeline = $timelineStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cumulativeProgress = 0;
    $progressByDate = [];
    
    foreach ($timeline as $row) {
        $cumulativeProgress += $row['approved_count'];
        $progressPercent = round(($cumulativeProgress / 6) * 100);
        $date = $row['upload_date'];
        
        $progressByDate[$date] = $progressPercent;
        if (!in_array($date, $allDates)) {
            $allDates[] = $date;
        }
    }
    
    $groupProgressData[$group_id] = [
        'name' => $group['group_name'],
        'data' => $progressByDate
    ];
}

// Sort dates
sort($allDates);

// Build datasets for Chart.js
$datasets = [];
$colors = [
    ['border' => 'rgb(255, 99, 132)', 'bg' => 'rgba(255, 99, 132, 0.2)'],
    ['border' => 'rgb(54, 162, 235)', 'bg' => 'rgba(54, 162, 235, 0.2)'],
    ['border' => 'rgb(255, 206, 86)', 'bg' => 'rgba(255, 206, 86, 0.2)'],
    ['border' => 'rgb(75, 192, 192)', 'bg' => 'rgba(75, 192, 192, 0.2)'],
    ['border' => 'rgb(153, 102, 255)', 'bg' => 'rgba(153, 102, 255, 0.2)'],
    ['border' => 'rgb(255, 159, 64)', 'bg' => 'rgba(255, 159, 64, 0.2)'],
];

$colorIndex = 0;
foreach ($groupProgressData as $group_id => $groupData) {
    $data = [];
    $lastProgress = 0;
    
    foreach ($allDates as $date) {
        if (isset($groupData['data'][$date])) {
            $lastProgress = $groupData['data'][$date];
        }
        $data[] = $lastProgress;
    }
    
    $color = $colors[$colorIndex % count($colors)];
    
    $datasets[] = [
        'label' => $groupData['name'],
        'data' => $data,
        'borderColor' => $color['border'],
        'backgroundColor' => $color['bg'],
        'fill' => false,
        'tension' => 0.3
    ];
    
    $colorIndex++;
}

// Format dates for display
$formattedDates = array_map(function($date) {
    return date("M d", strtotime($date));
}, $allDates);

// If no data, show empty state
if (empty($formattedDates)) {
    $formattedDates = ['No Data'];
    $datasets = [];
}

// PIE CHART: Aggregate status across all groups
$totalApproved = 0;
$totalPending = 0;
$totalRejected = 0;
$totalMissing = 0;

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
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
    
    $totalMissing += (6 - count($uploadMap));
}

$response = [
    'line' => [
        'labels' => $formattedDates,
        'datasets' => $datasets
    ],
    'pie' => [
        'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
        'data' => [$totalApproved, $totalPending, $totalRejected, $totalMissing]
    ]
];

echo json_encode($response);
?>