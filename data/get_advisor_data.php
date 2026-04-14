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

// Required milestones count toward progress %; optional are bonus only
$REQUIRED_LABELS = ['Title Approved', 'Proposal Approved', 'UREC Applied', 'UREC Approved', 'Research Completed', 'Hardbound Submitted'];
$REQUIRED_TOTAL  = count($REQUIRED_LABELS); // 6

$advisorUserId = $_SESSION['id'];

$facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
$facStmt->execute(['user_id' => $advisorUserId]);
$facRow    = $facStmt->fetch(PDO::FETCH_ASSOC);
$facultyId = $facRow['id'] ?? null;

if (!$facultyId) {
    echo json_encode([
        'line' => ['labels' => ['No Data'], 'datasets' => []],
        'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [0, 0, 0, 0]]
    ]);
    exit;
}

$groupsStmt = $con->prepare("
    SELECT id AS group_id, name AS group_name, research_id
    FROM groups
    WHERE adviser_id = :adviser_id
    ORDER BY name
");
$groupsStmt->execute(['adviser_id' => $facultyId]);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($groups)) {
    echo json_encode([
        'line' => ['labels' => ['No Data'], 'datasets' => []],
        'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [0, 0, 0, $REQUIRED_TOTAL]]
    ]);
    exit;
}

$allDates          = [];
$groupProgressData = [];

foreach ($groups as $group) {
    $group_id    = $group['group_id'];
    $research_id = $group['research_id'];
    $timelineEvents = [];

    // Milestone statuses + approved_at timestamps
    $milestoneStmt = $con->prepare("
        SELECT g.research_title, g.title_status, g.title_approved_at,
               gm.proposal_status,            gm.proposal_approved_at,
               gm.final_defense_status,       gm.final_defense_approved_at,
               gm.hardbound_submitted_status, gm.hardbound_submitted_approved_at,
               gm.applied_copyright_status,   gm.applied_copyright_approved_at,
               gm.research_presented_status,  gm.research_presented_approved_at,
               gm.research_published_status,  gm.research_published_approved_at,
               gm.created_at
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

    // UREC docs
    $urecDocs = [];
    if ($research_id) {
        $urecStmt = $con->prepare("
            SELECT document_type, status, approved_at FROM urec_documents
            WHERE research_id = :research_id ORDER BY uploaded_at DESC
        ");
        $urecStmt->execute(['research_id' => $research_id]);
        foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            if (!isset($urecDocs[$doc['document_type']])) $urecDocs[$doc['document_type']] = $doc;
        }
    }

    if ($milestones) {
        // Title
        if (!empty($milestones['research_title']) && $milestones['title_status'] === 'approved') {
            $date = $milestones['title_approved_at'] ?? $milestones['created_at'] ?? date('Y-m-d H:i:s');
            $timelineEvents[] = ['date' => $date, 'name' => 'Title Approved'];
        }

        // Group milestones
        $gmMap = [
            'proposal'            => 'Proposal Approved',
            'final_defense'       => 'Research Completed',
            'hardbound_submitted' => 'Hardbound Submitted',
            'applied_copyright'   => 'Copyright Applied',   // optional
            'research_presented'  => 'Research Presented',  // optional
            'research_published'  => 'Research Published',  // optional
            'copyright_approved'  => 'Copyright Approved',  // optional
        ];
        foreach ($gmMap as $key => $label) {
            $statusCol = $key . '_status';
            $dateCol   = $key . '_approved_at';
            if (($milestones[$statusCol] ?? '') === 'completed' && !empty($milestones[$dateCol])) {
                $timelineEvents[] = ['date' => $milestones[$dateCol], 'name' => $label];
            }
        }
    }

    // UREC — use approved_at date
    foreach (['UREC Form' => 'UREC Applied', 'UREC Clearance' => 'UREC Approved'] as $docType => $label) {
        if (isset($urecDocs[$docType]) && $urecDocs[$docType]['status'] === 'approved' && !empty($urecDocs[$docType]['approved_at'])) {
            $timelineEvents[] = ['date' => $urecDocs[$docType]['approved_at'], 'name' => $label];
        }
    }

    usort($timelineEvents, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

    // Progress % based on required milestones only
    $progressByDate = [];
    $completedItems = [];
    foreach ($timelineEvents as $event) {
        if (!isset($completedItems[$event['name']])) {
            $completedItems[$event['name']] = true;
            $requiredDone = count(array_intersect(array_keys($completedItems), $REQUIRED_LABELS));
            $pct  = min(round(($requiredDone / $REQUIRED_TOTAL) * 100, 1), 100);
            $date = date("Y-m-d", strtotime($event['date']));
            $progressByDate[$date] = $pct;
            if (!in_array($date, $allDates)) $allDates[] = $date;
        }
    }

    $groupProgressData[$group_id] = [
        'name' => $group['group_name'],
        'data' => $progressByDate,
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
        'tension'         => 0.3,
    ];
    $colorIndex++;
}

$formattedDates = array_map(fn($d) => date("M d", strtotime($d)), $allDates);
if (empty($formattedDates)) { $formattedDates = ['No Data']; $datasets = []; }

// Pie chart — required milestones only; optional shown only if they have a status
$totalApproved = 0;
$totalPending  = 0;
$totalRejected = 0;
$totalMissing  = 0;

foreach ($groups as $group) {
    $group_id    = $group['group_id'];
    $research_id = $group['research_id'];

    $milestoneStmt = $con->prepare("
        SELECT g.title_status,
               gm.proposal_status,
               gm.final_defense_status,
               gm.hardbound_submitted_status,
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

    $urecMap = [];
    if ($research_id) {
        $urecStmt = $con->prepare("
            SELECT document_type, status FROM urec_documents
            WHERE research_id = :research_id ORDER BY id DESC
        ");
        $urecStmt->execute(['research_id' => $research_id]);
        foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
        }
    }

    $normalise = fn($v) => match($v) {
        'approved', 'completed' => 'approved',
        'pending', 'endorsed'   => 'pending',
        'rejected'              => 'rejected',
        default                 => 'missing',
    };

    // Required milestones always contribute to pie
    $requiredStatuses = [
        $normalise($milestones['title_status']               ?? ''),
        $normalise($milestones['proposal_status']            ?? ''),
        $normalise($urecMap['UREC Form']['status']           ?? ''),
        $normalise($urecMap['UREC Clearance']['status']      ?? ''),
        $normalise($milestones['final_defense_status']       ?? ''),
        $normalise($milestones['hardbound_submitted_status'] ?? ''),
    ];
    foreach ($requiredStatuses as $s) {
        if ($s === 'approved')     $totalApproved++;
        elseif ($s === 'pending')  $totalPending++;
        elseif ($s === 'rejected') $totalRejected++;
        else                       $totalMissing++;
    }

    // Optional milestones — only count if not missing
    $optionalStatuses = [
        $milestones['applied_copyright_status']  ?? '',
        $milestones['research_presented_status'] ?? '',
        $milestones['research_published_status'] ?? '',
        $milestones['copyright_approved_status'] ?? '',
    ];
    foreach ($optionalStatuses as $v) {
        if (empty($v)) continue;
        $s = $normalise($v);
        if ($s === 'approved')     $totalApproved++;
        elseif ($s === 'pending')  $totalPending++;
        elseif ($s === 'rejected') $totalRejected++;
    }
}

echo json_encode([
    'line' => ['labels' => $formattedDates, 'datasets' => $datasets],
    'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [$totalApproved, $totalPending, $totalRejected, $totalMissing]]
]);