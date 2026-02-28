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

// ── All groups for this advisor ───────────────────────────────────────────────
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
            'labels'   => ['No Data'],
            'datasets' => []
        ],
        'pie' => [
            'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
            'data'   => [0, 0, 0, 10]
        ]
    ]);
    exit;
}

// ── LINE CHART ────────────────────────────────────────────────────────────────
// Per group: collect timeline of the 10 requirements becoming approved/completed
$allDates        = [];
$groupProgressData = [];

foreach ($groups as $group) {
    $group_id = $group['group_id'];

    $timelineEvents = [];

    // Requirement 10: Full Manuscript — earliest approved upload
    $manuStmt = $con->prepare("
        SELECT uploaded_at
        FROM uploads
        WHERE school_id IN (SELECT school_id FROM student WHERE group_id = :group_id)
          AND status = 'approved'
        ORDER BY uploaded_at ASC
        LIMIT 1
    ");
    $manuStmt->execute(['group_id' => $group_id]);
    $firstApproved = $manuStmt->fetch(PDO::FETCH_ASSOC);
    if ($firstApproved) {
        $timelineEvents[] = ['date' => $firstApproved['uploaded_at'], 'name' => 'Full Manuscript'];
    }

    // Milestones
    $milestoneStmt = $con->prepare("
        SELECT
            g.research_title,
            g.title_status,
            g.proposal_uploaded_at,
            g.final_defense_uploaded_at,
            g.applied_copyright_uploaded_at,
            g.research_presented_uploaded_at,
            g.research_published_uploaded_at,
            g.copyright_approved_uploaded_at,
            gm.proposal_status,
            gm.final_defense_status,
            gm.applied_copyright_status,
            gm.research_presented_status,
            gm.research_published_status,
            gm.copyright_approved_status,
            gm.created_at
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

    // UREC docs
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
        if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
    }

    if ($milestones) {
        if (!empty($milestones['research_title']) && $milestones['title_status'] === 'approved') {
            $timelineEvents[] = ['date' => $milestones['created_at'] ?? date('Y-m-d H:i:s'), 'name' => 'Title'];
        }
        $milestoneMap = [
            'Proposal'              => ['date_col' => 'proposal_uploaded_at',           'status_col' => 'proposal_status'],
            'Final Defense'         => ['date_col' => 'final_defense_uploaded_at',      'status_col' => 'final_defense_status'],
            'Applied for Copyright' => ['date_col' => 'applied_copyright_uploaded_at',  'status_col' => 'applied_copyright_status'],
            'Research Presented'    => ['date_col' => 'research_presented_uploaded_at', 'status_col' => 'research_presented_status'],
            'Research Published'    => ['date_col' => 'research_published_uploaded_at', 'status_col' => 'research_published_status'],
            'Copyright Approved'    => ['date_col' => 'copyright_approved_uploaded_at', 'status_col' => 'copyright_approved_status'],
        ];
        foreach ($milestoneMap as $label => $cols) {
            if (!empty($milestones[$cols['date_col']]) && ($milestones[$cols['status_col']] ?? '') === 'completed') {
                $timelineEvents[] = ['date' => $milestones[$cols['date_col']], 'name' => $label];
            }
        }
    }

    if (isset($urecMap['UREC Form']) && $urecMap['UREC Form']['status'] === 'approved') {
        $timelineEvents[] = ['date' => $urecMap['UREC Form']['uploaded_at'], 'name' => 'UREC Form'];
    }
    if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved') {
        $timelineEvents[] = ['date' => $urecMap['UREC Clearance']['uploaded_at'], 'name' => 'UREC Clearance'];
    }

    usort($timelineEvents, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

    $progressByDate = [];
    $completedItems = [];
    $totalItems     = 10;

    foreach ($timelineEvents as $event) {
        if (!isset($completedItems[$event['name']])) {
            $completedItems[$event['name']] = true;
            $progressPercent = round((count($completedItems) / $totalItems) * 100, 1);
            $date = date("Y-m-d", strtotime($event['date']));
            $progressByDate[$date] = $progressPercent;
            if (!in_array($date, $allDates)) $allDates[] = $date;
        }
    }

    $groupProgressData[$group_id] = [
        'name' => $group['group_name'],
        'data' => $progressByDate
    ];
}

sort($allDates);

$colors = [
    ['border' => 'rgb(255, 99, 132)',  'bg' => 'rgba(255, 99, 132, 0.2)'],
    ['border' => 'rgb(54, 162, 235)',  'bg' => 'rgba(54, 162, 235, 0.2)'],
    ['border' => 'rgb(255, 206, 86)',  'bg' => 'rgba(255, 206, 86, 0.2)'],
    ['border' => 'rgb(75, 192, 192)',  'bg' => 'rgba(75, 192, 192, 0.2)'],
    ['border' => 'rgb(153, 102, 255)', 'bg' => 'rgba(153, 102, 255, 0.2)'],
    ['border' => 'rgb(255, 159, 64)',  'bg' => 'rgba(255, 159, 64, 0.2)'],
    ['border' => 'rgb(201, 203, 207)', 'bg' => 'rgba(201, 203, 207, 0.2)'],
];

$datasets   = [];
$colorIndex = 0;

foreach ($groupProgressData as $group_id => $groupData) {
    $data         = [];
    $lastProgress = 0;
    foreach ($allDates as $date) {
        if (isset($groupData['data'][$date])) $lastProgress = $groupData['data'][$date];
        $data[] = $lastProgress;
    }
    $color      = $colors[$colorIndex % count($colors)];
    $datasets[] = [
        'label'           => $groupData['name'],
        'data'            => $data,
        'borderColor'     => $color['border'],
        'backgroundColor' => $color['bg'],
        'fill'            => false,
        'tension'         => 0.3
    ];
    $colorIndex++;
}

$formattedDates = array_map(fn($d) => date("M d", strtotime($d)), $allDates);

if (empty($formattedDates)) {
    $formattedDates = ['No Data'];
    $datasets       = [];
}

// ── PIE CHART: Aggregate status across all groups (10 requirements each) ─────
$totalApproved = 0;
$totalPending  = 0;
$totalRejected = 0;
$totalMissing  = 0;

foreach ($groups as $group) {
    $group_id = $group['group_id'];

    // -- Full Manuscript: 1 requirement per group --
    $uploadStmt = $con->prepare("
        SELECT task_name, status
        FROM uploads
        WHERE school_id IN (SELECT school_id FROM student WHERE group_id = :group_id)
        ORDER BY uploaded_at DESC
    ");
    $uploadStmt->execute(['group_id' => $group_id]);
    $allUploads = $uploadStmt->fetchAll(PDO::FETCH_ASSOC);

    $uploadMap = [];
    foreach ($allUploads as $u) {
        if (!isset($uploadMap[$u['task_name']])) $uploadMap[$u['task_name']] = $u;
    }

    if (empty($uploadMap)) {
        $totalMissing++;
    } else {
        $hasApproved = $hasPending = $hasRejected = false;
        foreach ($uploadMap as $u) {
            if ($u['status'] === 'approved') { $hasApproved = true; break; }
            if ($u['status'] === 'pending')  $hasPending  = true;
            if ($u['status'] === 'rejected') $hasRejected = true;
        }
        if ($hasApproved)     $totalApproved++;
        elseif ($hasPending)  $totalPending++;
        elseif ($hasRejected) $totalRejected++;
        else                  $totalMissing++;
    }

    // -- 9 milestones per group --
    $milestoneStmt = $con->prepare("
        SELECT
            g.title_status,
            gm.proposal_status,
            gm.final_defense_status,
            gm.applied_copyright_status,
            gm.research_presented_status,
            gm.research_published_status,
            gm.copyright_approved_status
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

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
        if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
    }

    $milestoneStatuses = [
        'Title'                 => $milestones['title_status'] ?? 'missing',
        'Proposal'              => 'missing',
        'Final Defense'         => 'missing',
        'Applied for Copyright' => 'missing',
        'Research Presented'    => 'missing',
        'Research Published'    => 'missing',
        'Copyright Approved'    => 'missing',
        'UREC Form'             => isset($urecMap['UREC Form'])      ? $urecMap['UREC Form']['status']      : 'missing',
        'UREC Clearance'        => isset($urecMap['UREC Clearance']) ? $urecMap['UREC Clearance']['status'] : 'missing',
    ];

    if ($milestones) {
        $statusMap = [
            'Proposal'              => 'proposal_status',
            'Final Defense'         => 'final_defense_status',
            'Applied for Copyright' => 'applied_copyright_status',
            'Research Presented'    => 'research_presented_status',
            'Research Published'    => 'research_published_status',
            'Copyright Approved'    => 'copyright_approved_status',
        ];
        foreach ($statusMap as $label => $col) {
            $val = $milestones[$col] ?? '';
            $milestoneStatuses[$label] = ($val === 'completed') ? 'approved' : ($val ?: 'missing');
        }
    }

    foreach ($milestoneStatuses as $status) {
        if ($status === 'approved')     $totalApproved++;
        elseif ($status === 'pending')  $totalPending++;
        elseif ($status === 'rejected') $totalRejected++;
        else                            $totalMissing++;
    }
}

echo json_encode([
    'line' => [
        'labels'   => $formattedDates,
        'datasets' => $datasets
    ],
    'pie' => [
        'labels' => ['Approved', 'Pending', 'Rejected', 'Missing'],
        'data'   => [$totalApproved, $totalPending, $totalRejected, $totalMissing]
    ]
]);
?>