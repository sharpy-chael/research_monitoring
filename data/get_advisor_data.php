<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include(__DIR__ . "/../connect.php");
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

// LINE CHART: Show percentage progress over time for each group (CHAPTERS + MILESTONES)
$allDates = [];
$groupProgressData = [];

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
    // Collect all timeline events (chapters + milestones) with approval dates
    $timelineEvents = [];
    
    // 1. GET CHAPTER APPROVALS
    $chapterStmt = $con->prepare("
        SELECT 
            uploaded_at,
            task_name,
            status
        FROM uploads
        WHERE school_id IN (
            SELECT school_id FROM student WHERE group_id = :group_id
        )
        ORDER BY uploaded_at ASC
    ");
    $chapterStmt->execute(['group_id' => $group_id]);
    $chapterUploads = $chapterStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($chapterUploads as $row) {
        if ($row['status'] === 'approved') {
            $timelineEvents[] = [
                'date' => $row['uploaded_at'],
                'name' => $row['task_name'],
                'type' => 'chapter'
            ];
        }
    }
    
    // 2. GET MILESTONE APPROVALS
    $milestoneStmt = $con->prepare("
        SELECT 
            g.research_title,
            g.title_status,
            g.proposal_uploaded_at,
            g.final_defense_uploaded_at,
            g.copyright_uploaded_at,
            gm.proposal_status,
            gm.final_defense_status,
            gm.copyright_status,
            gm.created_at
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get UREC documents
    $urecStmt = $con->prepare("
        SELECT document_type, status, uploaded_at
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
    
    // Add approved milestones to timeline
    if ($milestones) {
        // Title
        if (!empty($milestones['research_title']) && $milestones['title_status'] === 'approved') {
            $timelineEvents[] = [
                'date' => $milestones['created_at'] ?? date('Y-m-d H:i:s'),
                'name' => 'Title',
                'type' => 'milestone'
            ];
        }
        
        // Proposal
        if ($milestones['proposal_uploaded_at'] && 
            (!empty($milestones['proposal_status']) && $milestones['proposal_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['proposal_uploaded_at'],
                'name' => 'Proposal',
                'type' => 'milestone'
            ];
        }
        
        // Final Defense
        if ($milestones['final_defense_uploaded_at'] && 
            (!empty($milestones['final_defense_status']) && $milestones['final_defense_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['final_defense_uploaded_at'],
                'name' => 'Final Defense',
                'type' => 'milestone'
            ];
        }
        
        // Copyright
        if ($milestones['copyright_uploaded_at'] && 
            (!empty($milestones['copyright_status']) && $milestones['copyright_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['copyright_uploaded_at'],
                'name' => 'Copyright/IP',
                'type' => 'milestone'
            ];
        }
    }
    
    // UREC Form
    if (isset($urecMap['UREC Form']) && $urecMap['UREC Form']['status'] === 'approved') {
        $timelineEvents[] = [
            'date' => $urecMap['UREC Form']['uploaded_at'],
            'name' => 'UREC Form',
            'type' => 'milestone'
        ];
    }
    
    // UREC Clearance
    if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved') {
        $timelineEvents[] = [
            'date' => $urecMap['UREC Clearance']['uploaded_at'],
            'name' => 'UREC Clearance',
            'type' => 'milestone'
        ];
    }
    
    // Sort timeline by date
    usort($timelineEvents, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    // Build progress data
    $progressByDate = [];
    $completedItems = [];
    $totalItems = 12; // 6 chapters + 6 milestones
    
    foreach ($timelineEvents as $event) {
        $itemKey = $event['name'];
        
        if (!isset($completedItems[$itemKey])) {
            $completedItems[$itemKey] = true;
            
            $completedCount = count($completedItems);
            $progressPercent = round(($completedCount / $totalItems) * 100, 1);
            
            $date = date("Y-m-d", strtotime($event['date']));
            $progressByDate[$date] = $progressPercent;
            
            if (!in_array($date, $allDates)) {
                $allDates[] = $date;
            }
        }
    }
    
    $groupProgressData[$group_id] = [
        'name' => $group['group_name'],
        'data' => $progressByDate
    ];
}

// Sort dates chronologically
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
    ['border' => 'rgb(201, 203, 207)', 'bg' => 'rgba(201, 203, 207, 0.2)'],
];

$colorIndex = 0;
foreach ($groupProgressData as $group_id => $groupData) {
    $data = [];
    $lastProgress = 0;
    
    // Fill in data points, maintaining last known progress
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

// PIE CHART: Aggregate status across all groups (CHAPTERS + MILESTONES)
$totalApproved = 0;
$totalPending = 0;
$totalRejected = 0;
$totalMissing = 0;

foreach ($groups as $group) {
    $group_id = $group['group_id'];
    
    // COUNT CHAPTERS
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
    
    // COUNT MILESTONES
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
    
    // Count milestone statuses
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