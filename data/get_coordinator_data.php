<?php
header('Content-Type: application/json');
include('../connect.php');
session_start();

if (!isset($_SESSION['submit'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $program    = $_GET['program'] ?? 'all';
    $advisor_id = $_GET['advisor'] ?? 'all';

    $activeYear = $con->query("SELECT id, year_start, year_end FROM academic_years WHERE is_active = TRUE LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$activeYear) {
        echo json_encode(['line' => ['labels' => [], 'datasets' => []], 'pie' => ['labels' => [], 'data' => []], 'progress' => 0]);
        exit;
    }

    $yearStart = $activeYear['year_start'];

    // Build WHERE clause
    $whereClause = "1=1";
    $params = [];

    if ($program !== 'all') {
        $whereClause .= " AND EXISTS (
            SELECT 1 FROM students s
            JOIN programs p ON s.program_id = p.id
            WHERE s.group_id = g.id AND p.code = :program
        )";
        $params['program'] = $program;
    }

    if ($advisor_id !== 'all') {
        $whereClause .= " AND g.adviser_id = :advisor_id";
        $params['advisor_id'] = $advisor_id;
    }

    // Total groups
    $stmt = $con->prepare("SELECT COUNT(*) FROM groups g WHERE $whereClause");
    $stmt->execute($params);
    $totalGroups = $stmt->fetchColumn();

    $milestoneLabels = [
        'Approved Titles', 'Proposal', 'UREC Processing', 'UREC Clearance',
        'Final Defense', 'Applied for Copyright', 'Research Presented',
        'Research Published', 'Copyright Approved'
    ];

    $colors = [
        'rgb(76, 175, 80)', 'rgb(33, 150, 243)', 'rgb(156, 39, 176)',
        'rgb(233, 30, 99)', 'rgb(255, 152, 0)', 'rgb(255, 87, 34)',
        'rgb(121, 85, 72)', 'rgb(96, 125, 139)', 'rgb(0, 150, 136)'
    ];

    // Generate months
    $monthsStmt = $con->prepare("
        SELECT TO_CHAR(generate_series, 'Mon YYYY') as month_label,
               DATE_TRUNC('month', generate_series) as month_date
        FROM generate_series(
            DATE_TRUNC('month', :year_start::date),
            DATE_TRUNC('month', CURRENT_DATE),
            '1 month'::interval
        )
        ORDER BY generate_series
    ");
    $monthsStmt->execute(['year_start' => $yearStart]);
    $monthsData = $monthsStmt->fetchAll(PDO::FETCH_ASSOC);

    $lineData = ['labels' => [], 'datasets' => []];
    foreach ($milestoneLabels as $i => $label) {
        $lineData['datasets'][] = [
            'label'           => $label,
            'data'            => [],
            'borderColor'     => $colors[$i],
            'backgroundColor' => str_replace('rgb', 'rgba', str_replace(')', ', 0.2)', $colors[$i])),
            'tension'         => 0.3
        ];
    }

    foreach ($monthsData as $monthInfo) {
        $lineData['labels'][] = $monthInfo['month_label'];
        $monthEnd = date('Y-m-d H:i:s', strtotime($monthInfo['month_date'] . ' + 1 month - 1 second'));

        $cumulativeQuery = "
            SELECT
                COUNT(CASE WHEN g.title_status = 'approved' AND g.title_approved_at <= :month_end THEN 1 END) as title_approved,
                COUNT(CASE WHEN gm.proposal_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'proposal' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as proposal,
                COUNT(CASE WHEN ud_form.status = 'approved' AND ud_form.uploaded_at <= :month_end THEN 1 END) as urec_form,
                COUNT(CASE WHEN ud_clear.status = 'approved' AND ud_clear.uploaded_at <= :month_end THEN 1 END) as urec_clearance,
                COUNT(CASE WHEN gm.final_defense_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'final_defense' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as final_defense,
                COUNT(CASE WHEN gm.applied_copyright_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'applied_copyright' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as applied_copyright,
                COUNT(CASE WHEN gm.research_presented_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'research_presented' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as research_presented,
                COUNT(CASE WHEN gm.research_published_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'research_published' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as research_published,
                COUNT(CASE WHEN gm.copyright_approved_status = 'completed'
                    AND EXISTS (SELECT 1 FROM research_updates ru WHERE ru.research_id = g.research_id AND ru.milestone_type = 'copyright_approved' AND ru.uploaded_at <= :month_end)
                    THEN 1 END) as copyright_approved
            FROM groups g
            LEFT JOIN group_milestones gm ON g.id = gm.group_id
            LEFT JOIN (
                SELECT DISTINCT ON (research_id) research_id, status, uploaded_at
                FROM urec_documents WHERE document_type = 'UREC Form'
                ORDER BY research_id, uploaded_at DESC
            ) ud_form ON g.research_id = ud_form.research_id
            LEFT JOIN (
                SELECT DISTINCT ON (research_id) research_id, status, uploaded_at
                FROM urec_documents WHERE document_type = 'UREC Clearance'
                ORDER BY research_id, uploaded_at DESC
            ) ud_clear ON g.research_id = ud_clear.research_id
            WHERE $whereClause
        ";

        $cumulativeParams = array_merge($params, ['month_end' => $monthEnd]);
        $cumulativeStmt   = $con->prepare($cumulativeQuery);
        $cumulativeStmt->execute($cumulativeParams);
        $cumulative = $cumulativeStmt->fetch(PDO::FETCH_ASSOC);

        $cumulativeData = [
            (int)$cumulative['title_approved'],
            (int)$cumulative['proposal'],
            (int)$cumulative['urec_form'],
            (int)$cumulative['urec_clearance'],
            (int)$cumulative['final_defense'],
            (int)$cumulative['applied_copyright'],
            (int)$cumulative['research_presented'],
            (int)$cumulative['research_published'],
            (int)$cumulative['copyright_approved'],
        ];

        for ($i = 0; $i < 9; $i++) {
            $pct = $totalGroups > 0 ? round(($cumulativeData[$i] / $totalGroups) * 100, 2) : 0;
            $lineData['datasets'][$i]['data'][] = $pct;
        }
    }

    // Pie chart (current totals)
    $pieQuery = "
        SELECT
            COUNT(CASE WHEN g.title_status = 'approved' THEN 1 END) as title_approved,
            COUNT(CASE WHEN gm.proposal_status = 'completed' THEN 1 END) as proposal,
            COUNT(CASE WHEN ud_form.status = 'approved' THEN 1 END) as urec_form,
            COUNT(CASE WHEN ud_clear.status = 'approved' THEN 1 END) as urec_clearance,
            COUNT(CASE WHEN gm.final_defense_status = 'completed' THEN 1 END) as final_defense,
            COUNT(CASE WHEN gm.applied_copyright_status = 'completed' THEN 1 END) as applied_copyright,
            COUNT(CASE WHEN gm.research_presented_status = 'completed' THEN 1 END) as research_presented,
            COUNT(CASE WHEN gm.research_published_status = 'completed' THEN 1 END) as research_published,
            COUNT(CASE WHEN gm.copyright_approved_status = 'completed' THEN 1 END) as copyright_approved
        FROM groups g
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        LEFT JOIN (
            SELECT DISTINCT ON (research_id) research_id, status
            FROM urec_documents WHERE document_type = 'UREC Form'
            ORDER BY research_id, uploaded_at DESC
        ) ud_form ON g.research_id = ud_form.research_id
        LEFT JOIN (
            SELECT DISTINCT ON (research_id) research_id, status
            FROM urec_documents WHERE document_type = 'UREC Clearance'
            ORDER BY research_id, uploaded_at DESC
        ) ud_clear ON g.research_id = ud_clear.research_id
        WHERE $whereClause
    ";

    $pieStmt = $con->prepare($pieQuery);
    $pieStmt->execute($params);
    $pieCounts = $pieStmt->fetch(PDO::FETCH_ASSOC);

    $pieData = [
        (int)$pieCounts['title_approved'],
        (int)$pieCounts['proposal'],
        (int)$pieCounts['urec_form'],
        (int)$pieCounts['urec_clearance'],
        (int)$pieCounts['final_defense'],
        (int)$pieCounts['applied_copyright'],
        (int)$pieCounts['research_presented'],
        (int)$pieCounts['research_published'],
        (int)$pieCounts['copyright_approved'],
    ];

    $totalCompleted  = array_sum($pieData);
    $totalPossible   = $totalGroups * 9;
    $overallProgress = $totalPossible > 0 ? round(($totalCompleted / $totalPossible) * 100, 2) : 0;

    echo json_encode([
        'line'     => $lineData,
        'pie'      => ['labels' => $milestoneLabels, 'data' => $pieData],
        'progress' => $overallProgress
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}