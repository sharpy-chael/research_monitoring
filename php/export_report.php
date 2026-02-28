<?php
session_start();
include('../connect.php');

if (!isset($_SESSION['submit'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$type = $_POST['type'] ?? 'full';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

switch ($type) {
    case 'status':
        exportStatusReport($con, $output);
        break;
    case 'sdg':
        exportSDGReport($con, $output);
        break;
    case 'thrust':
        exportThrustReport($con, $output);
        break;
    case 'full':
        exportFullReport($con, $output);
        break;
    default:
        fputcsv($output, ['Invalid report type']);
}

fclose($output);

try {
    $logStmt = $con->prepare("INSERT INTO report_logs (generated_by, report_type) VALUES (:user_id, :report_type)");
    $logStmt->execute(['user_id' => $_SESSION['id'], 'report_type' => $type]);
} catch (PDOException $e) {
}

function exportStatusReport($con, $output) {
    fputcsv($output, ['Group Name', 'Leader', 'Approved Titles', 'Proposal', 'UREC Processing', 'UREC Clearance', 'Final Defense', 'Applied Copyright', 'Research Presented', 'Research Published', 'Copyright Approved', 'Overall Progress %']);
    
    $query = $con->query("
        SELECT g.id as group_id, g.name as group_name, g.title_status,
               s.name as leader_name
        FROM groups g
        LEFT JOIN student s ON g.id = s.group_id AND s.is_leader = TRUE
        ORDER BY g.name
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $milestonesStmt = $con->prepare("
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
        $milestonesStmt->execute(['group_id' => $row['group_id']]);
        $milestones = $milestonesStmt->fetch(PDO::FETCH_ASSOC);
        
        $urecStmt = $con->prepare("
            SELECT document_type, status FROM urec_documents
            WHERE group_id = :group_id
        ");
        $urecStmt->execute(['group_id' => $row['group_id']]);
        $urecData = $urecStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $urec = ['UREC Form' => 'No', 'UREC Clearance' => 'No'];
        foreach ($urecData as $doc) {
            if ($doc['status'] === 'approved') {
                $urec[$doc['document_type']] = 'Yes';
            }
        }
        
        $statusValues = [
            ($milestones['title_status'] ?? '') === 'approved' ? 'Yes' : 'No',
            ($milestones['proposal_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            $urec['UREC Form'],
            $urec['UREC Clearance'],
            ($milestones['final_defense_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['applied_copyright_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['research_presented_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['research_published_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['copyright_approved_status'] ?? '') === 'completed' ? 'Yes' : 'No'
        ];
        
        $completedCount = count(array_filter($statusValues, fn($v) => $v === 'Yes'));
        $progress = round(($completedCount / 9) * 100);
        
        fputcsv($output, array_merge(
            [$row['group_name'], $row['leader_name'] ?? 'No Leader'],
            $statusValues,
            [$progress . '%']
        ));
    }
}

function exportSDGReport($con, $output) {
    fputcsv($output, ['SDG Name', 'Groups Aligned', 'Percentage']);
    
    $totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
    
    $query = $con->query("
        SELECT us.name as sdg_name, COUNT(DISTINCT gs.group_id) as group_count
        FROM un_sdgs us
        LEFT JOIN group_sdgs gs ON us.id = gs.sdg_id
        GROUP BY us.id, us.name
        HAVING COUNT(DISTINCT gs.group_id) > 0
        ORDER BY group_count DESC, us.name
    ");
    
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $unassignedQuery = $con->query("
        SELECT COUNT(DISTINCT g.id) as group_count
        FROM groups g
        LEFT JOIN group_sdgs gs ON g.id = gs.group_id
        WHERE gs.id IS NULL
    ");
    $unassigned = $unassignedQuery->fetchColumn();
    
    if ($unassigned > 0) {
        $rows[] = ['sdg_name' => 'Unassigned', 'group_count' => $unassigned];
    }
    
    foreach ($rows as $row) {
        $percentage = $totalGroups > 0 ? round(($row['group_count'] / $totalGroups) * 100, 2) : 0;
        fputcsv($output, [$row['sdg_name'], $row['group_count'], $percentage . '%']);
    }
}

function exportThrustReport($con, $output) {
    fputcsv($output, ['Research Thrust', 'Groups Aligned', 'Percentage']);
    
    $totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
    
    $query = $con->query("
        SELECT rt.name as thrust_name, COUNT(DISTINCT gt.group_id) as group_count
        FROM research_thrusts rt
        LEFT JOIN group_thrusts gt ON rt.id = gt.thrust_id
        GROUP BY rt.id, rt.name
        HAVING COUNT(DISTINCT gt.group_id) > 0
        ORDER BY group_count DESC, rt.name
    ");
    
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $unassignedQuery = $con->query("
        SELECT COUNT(DISTINCT g.id) as group_count
        FROM groups g
        LEFT JOIN group_thrusts gt ON g.id = gt.group_id
        WHERE gt.id IS NULL
    ");
    $unassigned = $unassignedQuery->fetchColumn();
    
    if ($unassigned > 0) {
        $rows[] = ['thrust_name' => 'Unassigned', 'group_count' => $unassigned];
    }
    
    foreach ($rows as $row) {
        $percentage = $totalGroups > 0 ? round(($row['group_count'] / $totalGroups) * 100, 2) : 0;
        fputcsv($output, [$row['thrust_name'], $row['group_count'], $percentage . '%']);
    }
}

function exportFullReport($con, $output) {
    fputcsv($output, [
        'Group Name', 'Leader', 'Research Title',
        'UN SDGs', 'Research Thrusts', 'Advisor',
        'Approved Titles', 'Proposal', 'UREC Processing', 'UREC Clearance', 'Final Defense',
        'Applied Copyright', 'Research Presented', 'Research Published', 'Copyright Approved',
        'Progress %'
    ]);
    
    $query = $con->query("
        SELECT g.id as group_id, g.name as group_name, g.research_title, g.title_status,
               s.name as leader_name, a.name as advisor_name
        FROM groups g
        LEFT JOIN student s ON g.id = s.group_id AND s.is_leader = TRUE
        LEFT JOIN advisor a ON g.adviser_id = a.id
        ORDER BY g.name
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $sdgsStmt = $con->prepare("
            SELECT STRING_AGG(us.name, ', ' ORDER BY us.name) as sdgs
            FROM un_sdgs us
            JOIN group_sdgs gs ON us.id = gs.sdg_id
            WHERE gs.group_id = :group_id
        ");
        $sdgsStmt->execute(['group_id' => $row['group_id']]);
        $sdgs = $sdgsStmt->fetchColumn() ?: 'Unassigned';
        
        $thrustsStmt = $con->prepare("
            SELECT STRING_AGG(rt.name, ', ' ORDER BY rt.name) as thrusts
            FROM research_thrusts rt
            JOIN group_thrusts gt ON rt.id = gt.thrust_id
            WHERE gt.group_id = :group_id
        ");
        $thrustsStmt->execute(['group_id' => $row['group_id']]);
        $thrusts = $thrustsStmt->fetchColumn() ?: 'Unassigned';
        
        $milestonesStmt = $con->prepare("
            SELECT proposal_status, final_defense_status, applied_copyright_status,
                   research_presented_status, research_published_status, copyright_approved_status
            FROM group_milestones WHERE group_id = :group_id
        ");
        $milestonesStmt->execute(['group_id' => $row['group_id']]);
        $milestones = $milestonesStmt->fetch(PDO::FETCH_ASSOC);
        
        $urecStmt = $con->prepare("
            SELECT document_type, status FROM urec_documents
            WHERE group_id = :group_id
        ");
        $urecStmt->execute(['group_id' => $row['group_id']]);
        $urecData = $urecStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $urec = ['UREC Form' => 'No', 'UREC Clearance' => 'No'];
        foreach ($urecData as $doc) {
            if ($doc['status'] === 'approved') {
                $urec[$doc['document_type']] = 'Yes';
            }
        }
        
        $statusValues = [
            ($row['title_status'] ?? '') === 'approved' ? 'Yes' : 'No',
            ($milestones['proposal_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            $urec['UREC Form'],
            $urec['UREC Clearance'],
            ($milestones['final_defense_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['applied_copyright_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['research_presented_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['research_published_status'] ?? '') === 'completed' ? 'Yes' : 'No',
            ($milestones['copyright_approved_status'] ?? '') === 'completed' ? 'Yes' : 'No'
        ];
        
        $completedCount = count(array_filter($statusValues, fn($v) => $v === 'Yes'));
        $progress = round(($completedCount / 9) * 100);
        
        fputcsv($output, [
            $row['group_name'],
            $row['leader_name'] ?? 'No Leader',
            $row['research_title'] ?? 'No Title',
            $sdgs,
            $thrusts,
            $row['advisor_name'] ?? 'Unassigned',
            $statusValues[0],
            $statusValues[1],
            $statusValues[2],
            $statusValues[3],
            $statusValues[4],
            $statusValues[5],
            $statusValues[6],
            $statusValues[7],
            $statusValues[8],
            $progress . '%'
        ]);
    }
}
?>