<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    include(__DIR__ . "/../connect.php");
    session_start();

    if (!isset($_SESSION['school_id'])) {
        throw new Exception('Not authenticated');
    }

    $school_id = $_SESSION['school_id'];

    // Get student's current group
    $groupStmt = $con->prepare("SELECT group_id FROM student WHERE school_id = :school_id LIMIT 1");
    $groupStmt->execute(['school_id' => $school_id]);
    $studentRow = $groupStmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentRow || !$studentRow['group_id']) {
        ob_end_clean();
        echo json_encode([
            'line' => ['labels' => ['No Data'], 'datasets' => []],
            'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [0, 0, 0, 10]]
        ]);
        exit;
    }

    $current_group_id = $studentRow['group_id'];

    // LINE CHART: single group timeline
    $timelineEvents = [];

    $manuStmt = $con->prepare("
        SELECT uploaded_at FROM uploads
        WHERE school_id = :school_id AND status = 'approved'
        ORDER BY uploaded_at ASC LIMIT 1
    ");
    $manuStmt->execute(['school_id' => $school_id]);
    $firstApproved = $manuStmt->fetch(PDO::FETCH_ASSOC);
    if ($firstApproved) {
        $timelineEvents[] = ['date' => $firstApproved['uploaded_at'], 'name' => 'Full Manuscript'];
    }

    $milestoneStmt = $con->prepare("
        SELECT g.research_title, g.title_status,
               g.proposal_uploaded_at, g.final_defense_uploaded_at,
               g.applied_copyright_uploaded_at, g.research_presented_uploaded_at,
               g.research_published_uploaded_at, g.copyright_approved_uploaded_at,
               gm.proposal_status, gm.final_defense_status, gm.applied_copyright_status,
               gm.research_presented_status, gm.research_published_status,
               gm.copyright_approved_status, gm.created_at
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $milestoneStmt->execute(['group_id' => $current_group_id]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

    $urecStmt = $con->prepare("
        SELECT document_type, status, uploaded_at FROM urec_documents
        WHERE group_id = :group_id ORDER BY uploaded_at DESC
    ");
    $urecStmt->execute(['group_id' => $current_group_id]);
    $urecMap = [];
    foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
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

    if (isset($urecMap['UREC Form']) && $urecMap['UREC Form']['status'] === 'approved')
        $timelineEvents[] = ['date' => $urecMap['UREC Form']['uploaded_at'], 'name' => 'UREC Form'];
    if (isset($urecMap['UREC Clearance']) && $urecMap['UREC Clearance']['status'] === 'approved')
        $timelineEvents[] = ['date' => $urecMap['UREC Clearance']['uploaded_at'], 'name' => 'UREC Clearance'];

    usort($timelineEvents, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

    $allDates = []; $progressByDate = []; $completedItems = [];
    foreach ($timelineEvents as $event) {
        if (!isset($completedItems[$event['name']])) {
            $completedItems[$event['name']] = true;
            $date = date("Y-m-d", strtotime($event['date']));
            $progressByDate[$date] = round((count($completedItems) / 10) * 100, 1);
            if (!in_array($date, $allDates)) $allDates[] = $date;
        }
    }
    sort($allDates);

    $data = []; $lastProgress = 0;
    foreach ($allDates as $date) {
        if (isset($progressByDate[$date])) $lastProgress = $progressByDate[$date];
        $data[] = $lastProgress;
    }

    $formattedDates = array_map(fn($d) => date("M d", strtotime($d)), $allDates);
    $datasets = [];
    if (!empty($allDates)) {
        $datasets[] = [
            'label'           => 'Progress',
            'data'            => $data,
            'borderColor'     => 'rgb(139, 0, 0)',
            'backgroundColor' => 'rgba(139, 0, 0, 0.15)',
            'fill'            => false,
            'tension'         => 0.3,
            'borderWidth'     => 3,
        ];
    } else {
        $formattedDates = ['Start'];
    }

    // PIE CHART
    $approved = 0; $pending = 0; $rejected = 0; $missing = 0;

    $uploadStatusStmt = $con->prepare("SELECT task_name, status FROM uploads WHERE school_id = :school_id ORDER BY uploaded_at DESC");
    $uploadStatusStmt->execute(['school_id' => $school_id]);
    $uploadMap = [];
    foreach ($uploadStatusStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        if (!isset($uploadMap[$u['task_name']])) $uploadMap[$u['task_name']] = $u;
    }
    if (empty($uploadMap)) {
        $missing++;
    } else {
        $hasApproved = $hasPending = $hasRejected = false;
        foreach ($uploadMap as $u) {
            if ($u['status'] === 'approved') { $hasApproved = true; break; }
            if ($u['status'] === 'pending')  $hasPending  = true;
            if ($u['status'] === 'rejected') $hasRejected = true;
        }
        if ($hasApproved)     $approved++;
        elseif ($hasPending)  $pending++;
        elseif ($hasRejected) $rejected++;
        else                  $missing++;
    }

    $pieMilestoneStmt = $con->prepare("
        SELECT g.title_status, gm.proposal_status, gm.final_defense_status,
               gm.applied_copyright_status, gm.research_presented_status,
               gm.research_published_status, gm.copyright_approved_status
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        WHERE g.id = :group_id
    ");
    $pieMilestoneStmt->execute(['group_id' => $current_group_id]);
    $pieMilestones = $pieMilestoneStmt->fetch(PDO::FETCH_ASSOC);

    $pieUrecStmt = $con->prepare("SELECT document_type, status FROM urec_documents WHERE group_id = :group_id ORDER BY uploaded_at DESC");
    $pieUrecStmt->execute(['group_id' => $current_group_id]);
    $pieUrecMap = [];
    foreach ($pieUrecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
        if (!isset($pieUrecMap[$doc['document_type']])) $pieUrecMap[$doc['document_type']] = $doc;
    }

    $milestoneStatuses = [
        'Title'                 => $pieMilestones['title_status'] ?? 'missing',
        'Proposal'              => 'missing',
        'Final Defense'         => 'missing',
        'Applied for Copyright' => 'missing',
        'Research Presented'    => 'missing',
        'Research Published'    => 'missing',
        'Copyright Approved'    => 'missing',
        'UREC Form'             => $pieUrecMap['UREC Form']['status']      ?? 'missing',
        'UREC Clearance'        => $pieUrecMap['UREC Clearance']['status'] ?? 'missing',
    ];
    if ($pieMilestones) {
        foreach ([
            'Proposal'              => 'proposal_status',
            'Final Defense'         => 'final_defense_status',
            'Applied for Copyright' => 'applied_copyright_status',
            'Research Presented'    => 'research_presented_status',
            'Research Published'    => 'research_published_status',
            'Copyright Approved'    => 'copyright_approved_status',
        ] as $label => $col) {
            $val = $pieMilestones[$col] ?? '';
            $milestoneStatuses[$label] = ($val === 'completed') ? 'approved' : ($val ?: 'missing');
        }
    }
    foreach ($milestoneStatuses as $status) {
        if ($status === 'approved')     $approved++;
        elseif ($status === 'pending')  $pending++;
        elseif ($status === 'rejected') $rejected++;
        else                            $missing++;
    }

    ob_end_clean();
    echo json_encode([
        'line' => ['labels' => $formattedDates, 'datasets' => $datasets],
        'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [$approved, $pending, $rejected, $missing]]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'error' => $e->getMessage(),
        'line'  => ['labels' => ['Error'], 'data' => [0]],
        'pie'   => ['labels' => ['Error'], 'data' => [100]]
    ]);
}