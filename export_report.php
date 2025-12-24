<?php
session_start();
include('connect.php');

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

// Log the export (optional - only if you created report_logs table)
try {
    $logStmt = $con->prepare("
        INSERT INTO report_logs (generated_by, report_type) 
        VALUES (:user_id, :report_type)
    ");
    $logStmt->execute([
        'user_id' => $_SESSION['id'],
        'report_type' => $type
    ]);
} catch (PDOException $e) {
    // Ignore if report_logs table doesn't exist
}

function exportStatusReport($con, $output) {
    fputcsv($output, ['Group Name', 'Leader', 'Approved', 'Pending', 'Rejected', 'Missing', 'Progress %']);
    
    $query = $con->query("
        SELECT 
            g.name as group_name,
            s.name as leader_name,
            g.id as group_id
        FROM groups g
        LEFT JOIN student s ON g.id = s.group_id AND s.is_leader = TRUE
        ORDER BY g.name
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $statusStmt = $con->prepare("
            SELECT 
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                COUNT(DISTINCT task_name) as total_submitted
            FROM uploads
            WHERE school_id IN (
                SELECT school_id FROM student WHERE group_id = :group_id
            )
        ");
        $statusStmt->execute(['group_id' => $row['group_id']]);
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        $missing = 6 - ($status['total_submitted'] ?? 0);
        $progress = round((($status['approved'] ?? 0) / 6) * 100);
        
        fputcsv($output, [
            $row['group_name'],
            $row['leader_name'] ?? 'No Leader',
            $status['approved'] ?? 0,
            $status['pending'] ?? 0,
            $status['rejected'] ?? 0,
            $missing,
            $progress . '%'
        ]);
    }
}

function exportSDGReport($con, $output) {
    fputcsv($output, ['SDG Name', 'Groups Aligned', 'Percentage']);
    
    $totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
    
    $query = $con->query("
        SELECT 
            COALESCE(sd.name, 'Unassigned') as sdg_name,
            COUNT(g.id) as group_count
        FROM groups g
        LEFT JOIN un_sdgs sd ON g.sdg_id = sd.id
        GROUP BY sd.name
        ORDER BY group_count DESC
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $percentage = $totalGroups > 0 ? round(($row['group_count'] / $totalGroups) * 100, 2) : 0;
        
        fputcsv($output, [
            $row['sdg_name'],
            $row['group_count'],
            $percentage . '%'
        ]);
    }
}

function exportThrustReport($con, $output) {
    fputcsv($output, ['Research Thrust', 'Groups Aligned', 'Percentage']);
    
    $totalGroups = $con->query("SELECT COUNT(*) FROM groups")->fetchColumn();
    
    $query = $con->query("
        SELECT 
            COALESCE(rt.name, 'Unassigned') as thrust_name,
            COUNT(g.id) as group_count
        FROM groups g
        LEFT JOIN research_thrusts rt ON g.thrust_id = rt.id
        GROUP BY rt.name
        ORDER BY group_count DESC
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $percentage = $totalGroups > 0 ? round(($row['group_count'] / $totalGroups) * 100, 2) : 0;
        
        fputcsv($output, [
            $row['thrust_name'],
            $row['group_count'],
            $percentage . '%'
        ]);
    }
}

function exportFullReport($con, $output) {
    fputcsv($output, [
        'Group Name', 'Leader', 'Research Title', 'Title Status',
        'UN SDG', 'Research Thrust', 'Advisor',
        'Approved', 'Pending', 'Rejected', 'Missing', 'Progress %'
    ]);
    
    $query = $con->query("
        SELECT 
            g.id as group_id,
            g.name as group_name,
            g.research_title,
            g.title_status,
            s.name as leader_name,
            sd.name as sdg_name,
            rt.name as thrust_name,
            a.name as advisor_name
        FROM groups g
        LEFT JOIN student s ON g.id = s.group_id AND s.is_leader = TRUE
        LEFT JOIN un_sdgs sd ON g.sdg_id = sd.id
        LEFT JOIN research_thrusts rt ON g.thrust_id = rt.id
        LEFT JOIN advisor a ON g.adviser_id = a.id
        ORDER BY g.name
    ");
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $statusStmt = $con->prepare("
            SELECT 
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                COUNT(DISTINCT task_name) as total_submitted
            FROM uploads
            WHERE school_id IN (
                SELECT school_id FROM student WHERE group_id = :group_id
            )
        ");
        $statusStmt->execute(['group_id' => $row['group_id']]);
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        $missing = 6 - ($status['total_submitted'] ?? 0);
        $progress = round((($status['approved'] ?? 0) / 6) * 100);
        
        fputcsv($output, [
            $row['group_name'],
            $row['leader_name'] ?? 'No Leader',
            $row['research_title'] ?? 'No Title',
            ucfirst($row['title_status'] ?? 'missing'),
            $row['sdg_name'] ?? 'Unassigned',
            $row['thrust_name'] ?? 'Unassigned',
            $row['advisor_name'] ?? 'Unassigned',
            $status['approved'] ?? 0,
            $status['pending'] ?? 0,
            $status['rejected'] ?? 0,
            $missing,
            $progress . '%'
        ]);
    }
}
?>