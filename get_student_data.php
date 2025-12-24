<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include("connect.php");
session_start();

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$school_id = $_SESSION['school_id'];

// Get student's group
$groupStmt = $con->prepare("
    SELECT group_id 
    FROM student 
    WHERE school_id = :school_id
");
$groupStmt->execute(['school_id' => $school_id]);
$student = $groupStmt->fetch(PDO::FETCH_ASSOC);

if (!$student || !$student['group_id']) {
    echo json_encode([
        'line' => [
            'labels' => ['No Data'],
            'data' => [0]
        ],
        'pie' => [
            'labels' => ['Completed', 'Pending', 'Missing'],
            'data' => [0, 0, 100]
        ]
    ]);
    exit;
}

$group_id = $student['group_id'];

// LINE CHART: Progress timeline for this group
$lineStmt = $con->prepare("
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
$lineStmt->execute(['group_id' => $group_id]);
$lineResults = $lineStmt->fetchAll(PDO::FETCH_ASSOC);

$lineData = ['labels' => [], 'data' => []];
$cumulativeProgress = 0;

foreach ($lineResults as $row) {
    $cumulativeProgress += $row['approved_count'];
    $progressPercent = round(($cumulativeProgress / 6) * 100); // 6 total tasks
    
    $lineData['labels'][] = date("M d", strtotime($row['upload_date']));
    $lineData['data'][] = $progressPercent;
}

// If no data, show empty state
if (empty($lineData['labels'])) {
    $lineData = [
        'labels' => ['No Data'],
        'data' => [0]
    ];
}

// PIE CHART: Current status breakdown
$pieStmt = $con->prepare("
    SELECT task_name, status
    FROM uploads
    WHERE school_id IN (
        SELECT school_id FROM student WHERE group_id = :group_id
    )
    ORDER BY uploaded_at DESC
");
$pieStmt->execute(['group_id' => $group_id]);
$allUploads = $pieStmt->fetchAll(PDO::FETCH_ASSOC);

// Get latest upload per task
$uploadMap = [];
foreach ($allUploads as $upload) {
    if (!isset($uploadMap[$upload['task_name']])) {
        $uploadMap[$upload['task_name']] = $upload;
    }
}

// Count statuses
$approved = 0;
$pending = 0;
$rejected = 0;

foreach ($uploadMap as $upload) {
    if ($upload['status'] === 'approved') {
        $approved++;
    } elseif ($upload['status'] === 'pending') {
        $pending++;
    } elseif ($upload['status'] === 'rejected') {
        $rejected++;
    }
}

$missing = 6 - count($uploadMap); // Total 6 tasks

$response = [
    'line' => $lineData,
    'pie' => [
        'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
        'data' => [$approved, $pending, $rejected, $missing]
    ]
];

echo json_encode($response);
?>