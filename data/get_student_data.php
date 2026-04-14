<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Required milestones count toward progress %; optional are bonus only
$REQUIRED_LABELS = ['Title Approved', 'Proposal Approved', 'UREC Applied', 'UREC Approved', 'Research Completed', 'Hardbound Submitted'];
$REQUIRED_TOTAL  = count($REQUIRED_LABELS); // 6

try {
    include(__DIR__ . "/../connect.php");
    session_start();

    if (!isset($_SESSION['school_id'])) {
        throw new Exception('Not authenticated');
    }

    $school_id = $_SESSION['school_id'];

    $studentStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id LIMIT 1");
    $studentStmt->execute(['school_id' => $school_id]);
    $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentRow) {
        ob_end_clean();
        echo json_encode([
            'line' => ['labels' => ['No Data'], 'datasets' => []],
            'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [0, 0, 0, $REQUIRED_TOTAL]]
        ]);
        exit;
    }

    $studentId = $studentRow['id'];

    $groupsStmt = $con->prepare("
        SELECT sg.group_id, g.name AS group_name, g.research_id
        FROM student_groups sg
        JOIN groups g ON sg.group_id = g.id
        WHERE sg.student_id = :student_id
        ORDER BY sg.id ASC
    ");
    $groupsStmt->execute(['student_id' => $studentId]);
    $groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($groups)) {
        ob_end_clean();
        echo json_encode([
            'line' => ['labels' => ['No Data'], 'datasets' => []],
            'pie'  => ['labels' => ['Approved', 'Pending', 'Rejected', 'Missing'], 'data' => [0, 0, 0, $REQUIRED_TOTAL]]
        ]);
        exit;
    }

    $allDates          = [];
    $groupProgressData = [];

    foreach ($groups as $group) {
        $current_group_id = $group['group_id'];
        $researchId       = $group['research_id'];
        $timelineEvents   = [];

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
        $milestoneStmt->execute(['group_id' => $current_group_id]);
        $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

        // UREC docs
        $urecMap = [];
        if ($researchId) {
            $urecStmt = $con->prepare("
                SELECT document_type, status, approved_at FROM urec_documents
                WHERE research_id = :research_id ORDER BY uploaded_at DESC
            ");
            $urecStmt->execute(['research_id' => $researchId]);
            foreach ($urecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
                if (!isset($urecMap[$doc['document_type']])) $urecMap[$doc['document_type']] = $doc;
            }
        }

        if ($milestones) {
            // Title
            if (!empty($milestones['research_title']) && $milestones['title_status'] === 'approved') {
                $date = $milestones['title_approved_at'] ?? $milestones['created_at'] ?? date('Y-m-d H:i:s');
                $timelineEvents[] = ['date' => $date, 'name' => 'Title Approved'];
            }

            // Group milestones (key => display label)
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
            if (isset($urecMap[$docType]) && $urecMap[$docType]['status'] === 'approved' && !empty($urecMap[$docType]['approved_at'])) {
                $timelineEvents[] = ['date' => $urecMap[$docType]['approved_at'], 'name' => $label];
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

        $groupProgressData[$current_group_id] = [
            'name' => $group['group_name'],
            'data' => $progressByDate,
        ];
    }

    sort($allDates);

    $colors = [
        ['border' => 'rgb(139, 0, 0)',    'bg' => 'rgba(139, 0, 0, 0.15)'],
        ['border' => 'rgb(54, 162, 235)', 'bg' => 'rgba(54, 162, 235, 0.15)'],
        ['border' => 'rgb(255, 159, 64)', 'bg' => 'rgba(255, 159, 64, 0.15)'],
        ['border' => 'rgb(75, 192, 192)', 'bg' => 'rgba(75, 192, 192, 0.15)'],
        ['border' => 'rgb(153, 102, 255)','bg' => 'rgba(153, 102, 255, 0.15)'],
        ['border' => 'rgb(255, 206, 86)', 'bg' => 'rgba(255, 206, 86, 0.15)'],
    ];

    $datasets   = [];
    $colorIndex = 0;

    foreach ($groupProgressData as $gid => $groupData) {
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
            'borderWidth'     => 3,
        ];
        $colorIndex++;
    }

    $formattedDates = array_map(fn($d) => date("M d", strtotime($d)), $allDates);
    if (empty($formattedDates)) { $formattedDates = ['Start']; $datasets = []; }

    // Pie chart — required milestones only; optional shown only if they have a status
    $approved = 0; $pending = 0; $rejected = 0; $missing = 0;

    foreach ($groups as $group) {
        $current_group_id = $group['group_id'];
        $researchId       = $group['research_id'];

        $pieMilestoneStmt = $con->prepare("
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
        $pieMilestoneStmt->execute(['group_id' => $current_group_id]);
        $pieMilestones = $pieMilestoneStmt->fetch(PDO::FETCH_ASSOC);

        $pieUrecMap = [];
        if ($researchId) {
            $pieUrecStmt = $con->prepare("
                SELECT document_type, status FROM urec_documents
                WHERE research_id = :research_id ORDER BY uploaded_at DESC
            ");
            $pieUrecStmt->execute(['research_id' => $researchId]);
            foreach ($pieUrecStmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
                if (!isset($pieUrecMap[$doc['document_type']])) $pieUrecMap[$doc['document_type']] = $doc;
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
            $normalise($pieMilestones['title_status']              ?? ''),
            $normalise($pieMilestones['proposal_status']           ?? ''),
            $normalise($pieUrecMap['UREC Form']['status']          ?? ''),
            $normalise($pieUrecMap['UREC Clearance']['status']     ?? ''),
            $normalise($pieMilestones['final_defense_status']      ?? ''),
            $normalise($pieMilestones['hardbound_submitted_status'] ?? ''),
        ];
        foreach ($requiredStatuses as $s) {
            if ($s === 'approved')     $approved++;
            elseif ($s === 'pending')  $pending++;
            elseif ($s === 'rejected') $rejected++;
            else                       $missing++;
        }

        // Optional milestones — only count if not missing
        $optionalStatuses = [
            $pieMilestones['applied_copyright_status']  ?? '',
            $pieMilestones['research_presented_status'] ?? '',
            $pieMilestones['research_published_status'] ?? '',
            $pieMilestones['copyright_approved_status'] ?? '',
        ];
        foreach ($optionalStatuses as $v) {
            if (empty($v)) continue;
            $s = $normalise($v);
            if ($s === 'approved')     $approved++;
            elseif ($s === 'pending')  $pending++;
            elseif ($s === 'rejected') $rejected++;
        }
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