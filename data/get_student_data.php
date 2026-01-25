<?php
// Prevent any output before JSON
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Error handling - catch errors and return as JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

try {
    include(__DIR__ . "/../connect.php");
    session_start();

    if (!isset($_SESSION['school_id'])) {
        throw new Exception('Not authenticated');
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
        ob_end_clean();
        echo json_encode([
            'line' => [
                'labels' => ['No Data'],
                'data' => [0]
            ],
            'pie' => [
                'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
                'data' => [0, 0, 0, 12]
            ]
        ]);
        exit;
    }

    $group_id = $student['group_id'];

    // Get current milestone statuses
    // Get current milestone statuses
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

    // Get UREC documents status
    $urecStmt = $con->prepare("
        SELECT document_type, status, uploaded_at
        FROM urec_documents
        WHERE group_id = :group_id
        ORDER BY uploaded_at DESC
    ");
    $urecStmt->execute(['group_id' => $group_id]);
    $urecDocs = $urecStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get latest UREC docs per type
    $urecMap = [];
    foreach ($urecDocs as $doc) {
        if (!isset($urecMap[$doc['document_type']])) {
            $urecMap[$doc['document_type']] = $doc;
        }
    }

    // LINE CHART: Collect all events (chapters + milestones) with dates
    $timelineEvents = [];

    // Add chapter uploads
    $lineStmt = $con->prepare("
        SELECT 
            DATE(uploaded_at) as upload_date,
            uploaded_at,
            task_name,
            status
        FROM uploads
        WHERE school_id IN (
            SELECT school_id FROM student WHERE group_id = :group_id
        )
        ORDER BY uploaded_at ASC
    ");
    $lineStmt->execute(['group_id' => $group_id]);
    $chapterUploads = $lineStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($chapterUploads as $row) {
        if ($row['status'] === 'approved') {
            $timelineEvents[] = [
                'date' => $row['uploaded_at'],
                'type' => 'chapter',
                'name' => $row['task_name']
            ];
        }
    }

    // Add milestone events
    if ($milestones) {
        // Title
        if (!empty($milestones['research_title']) && $milestones['title_status'] === 'approved') {
            $timelineEvents[] = [
                'date' => $milestones['created_at'] ?? date('Y-m-d H:i:s'),
                'type' => 'milestone',
                'name' => 'Title'
            ];
        }
        
        // Proposal
        if ($milestones['proposal_uploaded_at'] && 
            (!empty($milestones['proposal_status']) && $milestones['proposal_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['proposal_uploaded_at'],
                'type' => 'milestone',
                'name' => 'Proposal'
            ];
        }
        
        // Final Defense
        if ($milestones['final_defense_uploaded_at'] && 
            (!empty($milestones['final_defense_status']) && $milestones['final_defense_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['final_defense_uploaded_at'],
                'type' => 'milestone',
                'name' => 'Final Defense'
            ];
        }
        
        // Copyright
        if ($milestones['copyright_uploaded_at'] && 
            (!empty($milestones['copyright_status']) && $milestones['copyright_status'] === 'completed')) {
            $timelineEvents[] = [
                'date' => $milestones['copyright_uploaded_at'],
                'type' => 'milestone',
                'name' => 'Copyright/IP'
            ];
        }
    }

    // Add UREC documents
    if (isset($urecMap['UREC Form']) && $urecMap['UREC Form']['status'] === 'approved') {
        $timelineEvents[] = [
            'date' => $urecMap['UREC Form']['uploaded_at'],
            'type' => 'milestone',
            'name' => 'UREC Form'
        ];
    }

    if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved') {
        $timelineEvents[] = [
            'date' => $urecMap['UREC Clearance']['uploaded_at'],
            'type' => 'milestone',
            'name' => 'UREC Clearance'
        ];
    }

    // Sort timeline by date
    usort($timelineEvents, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    // Build line chart data
    $lineData = ['labels' => [], 'data' => []];
    $completedItems = [];
    $totalItems = 12; // 6 chapters + 6 milestones

    foreach ($timelineEvents as $event) {
        $itemKey = $event['name'];
        
        if (!isset($completedItems[$itemKey])) {
            $completedItems[$itemKey] = true;
            
            $completedCount = count($completedItems);
            $progressPercent = round(($completedCount / $totalItems) * 100, 1);
            
            $lineData['labels'][] = date("M d", strtotime($event['date']));
            $lineData['data'][] = $progressPercent;
        }
    }

    // If no data, show empty state
    if (empty($lineData['labels'])) {
        $lineData = [
            'labels' => ['Start'],
            'data' => [0]
        ];
    }

    // PIE CHART: Current status breakdown
    // Count chapters
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

    // Get latest upload per chapter
    $uploadMap = [];
    foreach ($allUploads as $upload) {
        if (!isset($uploadMap[$upload['task_name']])) {
            $uploadMap[$upload['task_name']] = $upload;
        }
    }

    // Count chapter statuses
    $approved = 0;
    $pending = 0;
    $rejected = 0;
    $missing = 0;

    foreach ($uploadMap as $upload) {
        if ($upload['status'] === 'approved') {
            $approved++;
        } elseif ($upload['status'] === 'pending') {
            $pending++;
        } elseif ($upload['status'] === 'rejected') {
            $rejected++;
        }
    }
    $missing += (6 - count($uploadMap)); // 6 chapters

    // Count milestone statuses
    $milestoneStatuses = [];

    // Title
    $milestoneStatuses['Title'] = $milestones['title_status'] ?? 'missing';

    // Proposal
    $milestoneStatuses['Proposal'] = 'missing';
    if ($milestones && !empty($milestones['proposal_status'])) {
        $milestoneStatuses['Proposal'] = ($milestones['proposal_status'] === 'completed') ? 'approved' : $milestones['proposal_status'];
    }

    // UREC Form
    $milestoneStatuses['UREC Form'] = isset($urecMap['UREC Form']) ? $urecMap['UREC Form']['status'] : 'missing';

    // UREC Clearance
    $milestoneStatuses['UREC Clearance'] = isset($urecMap['UREC Clearance']) ? $urecMap['UREC Clearance']['status'] : 'missing';

    // Final Defense
    $milestoneStatuses['Final Defense'] = 'missing';
    if ($milestones && !empty($milestones['final_defense_status'])) {
        $milestoneStatuses['Final Defense'] = ($milestones['final_defense_status'] === 'completed') ? 'approved' : $milestones['final_defense_status'];
    }

    // Copyright
    $milestoneStatuses['Copyright/IP'] = 'missing';
    if ($milestones && !empty($milestones['copyright_status'])) {
        $milestoneStatuses['Copyright/IP'] = ($milestones['copyright_status'] === 'completed') ? 'approved' : $milestones['copyright_status'];
    }

    // Count milestone statuses
    foreach ($milestoneStatuses as $status) {
        if ($status === 'approved') {
            $approved++;
        } elseif ($status === 'pending') {
            $pending++;
        } elseif ($status === 'rejected') {
            $rejected++;
        } else {
            $missing++;
        }
    }

    $response = [
        'line' => $lineData,
        'pie' => [
            'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
            'data' => [$approved, $pending, $rejected, $missing]
        ]
    ];

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'error' => $e->getMessage(),
        'line' => [
            'labels' => ['Error'],
            'data' => [0]
        ],
        'pie' => [
            'labels' => ['Error'],
            'data' => [100]
        ]
    ]);
}
?>