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
    case 'status':  exportStatusReport($con, $output);  break;
    case 'sdg':     exportSDGReport($con, $output);     break;
    case 'thrust':  exportThrustReport($con, $output);  break;
    case 'full':
    default:        exportFullReport($con, $output);    break;
}

fclose($output);

try {
    $logStmt = $con->prepare("INSERT INTO report_logs (generated_by, report_type) VALUES (:user_id, :report_type)");
    $logStmt->execute(['user_id' => $_SESSION['id'], 'report_type' => $type]);
} catch (PDOException $e) {}

function formatDate($value) {
    if (empty($value)) return '';
    return date("F d, Y", strtotime($value));
}

function getStudentNames($con, $groupId) {
    $stmt = $con->prepare("
        SELECT TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(NULLIF(s.middlename,''),'') || ' ' || COALESCE(s.lastname,'')) AS full_name
        FROM students s
        JOIN student_groups sg ON s.id = sg.student_id
        WHERE sg.group_id = :group_id
        ORDER BY sg.is_leader DESC, s.lastname, s.firstname
    ");
    $stmt->execute(['group_id' => $groupId]);
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return implode(', ', array_filter(array_map('trim', $names)));
}

function getProgramName($con, $groupId) {
    $stmt = $con->prepare("
        SELECT p.name FROM programs p
        JOIN students s ON s.program_id = p.id
        JOIN student_groups sg ON sg.student_id = s.id
        WHERE sg.group_id = :group_id
        ORDER BY sg.is_leader DESC LIMIT 1
    ");
    $stmt->execute(['group_id' => $groupId]);
    return $stmt->fetchColumn() ?: '';
}

function getSDGs($con, $researchId) {
    if (!$researchId) return '';
    $stmt = $con->prepare("SELECT STRING_AGG(sd.name, ', ' ORDER BY sd.name) FROM sdgs sd JOIN thrusts_assignments ta ON sd.id = ta.sdg_id WHERE ta.research_id = :research_id");
    $stmt->execute(['research_id' => $researchId]);
    return $stmt->fetchColumn() ?: '';
}

function getThrusts($con, $researchId) {
    if (!$researchId) return '';
    $stmt = $con->prepare("SELECT STRING_AGG(t.name, ', ' ORDER BY t.name) FROM thrusts t JOIN thrusts_assignments ta ON t.id = ta.thrust_id WHERE ta.research_id = :research_id");
    $stmt->execute(['research_id' => $researchId]);
    return $stmt->fetchColumn() ?: '';
}

function getUrecApprovedAt($con, $researchId, $docType) {
    if (!$researchId) return '';
    $stmt = $con->prepare("SELECT approved_at FROM urec_documents WHERE research_id = :research_id AND document_type = :doc_type ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute(['research_id' => $researchId, 'doc_type' => $docType]);
    return $stmt->fetchColumn() ?: '';
}

function getManuscriptApprovedAt($con, $groupId) {
    $stmt = $con->prepare("SELECT uploaded_at FROM uploads WHERE group_id = :group_id AND task_name = 'Full Manuscript (Chapter 1-5)' AND status = 'approved' ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute(['group_id' => $groupId]);
    return $stmt->fetchColumn() ?: '';
}

function exportFullReport($con, $output) {
    fputcsv($output, [
        'Research Title', 'Student Researchers', 'Adviser', 'Program',
        'SDG Alignment', 'University Research Agenda',
        'Title Approved', 'Proposal Approved', 'UREC Applied', 'UREC Approved',
        'Research Completed', 'Hardbound Submitted', 'Copyright Applied',
        'Research Presented', 'Research Published',
    ]);

    $groups = $con->query("
        SELECT g.id AS group_id, g.research_title, g.research_id,
               g.title_approved_at, g.title_status,
               COALESCE(f.name, '') AS adviser_name,
               gm.proposal_approved_at, gm.final_defense_approved_at, gm.hardbound_submitted_approved_at,
               gm.applied_copyright_approved_at, gm.research_presented_approved_at, gm.research_published_approved_at
        FROM groups g
        LEFT JOIN faculties f ON g.adviser_id = f.id
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        ORDER BY g.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groups as $g) {
        $gid = $g['group_id'];
        $rid = $g['research_id'];
        fputcsv($output, [
            $g['research_title'] ?: '',
            getStudentNames($con, $gid),
            $g['adviser_name'],
            getProgramName($con, $gid),
            getSDGs($con, $rid),
            getThrusts($con, $rid),
            $g['title_status'] === 'approved' ? formatDate($g['title_approved_at']) : '',
            formatDate($g['proposal_approved_at']),
            formatDate(getUrecApprovedAt($con, $rid, 'UREC Form')),
            formatDate(getUrecApprovedAt($con, $rid, 'UREC Clearance')),
            formatDate($g['final_defense_approved_at']),
            formatDate($g['hardbound_submitted_approved_at']),
            formatDate($g['applied_copyright_approved_at']),
            formatDate($g['research_presented_approved_at']),
            formatDate($g['research_published_approved_at']),
        ]);
    }
}

function exportStatusReport($con, $output) {
    fputcsv($output, [
        'Research Title', 'Student Researchers', 'Adviser', 'Program',
        'Title Approved', 'Proposal Approved', 'UREC Applied', 'UREC Approved',
        'Research Completed', 'Hardbound Submitted', 'Copyright Applied',
        'Research Presented', 'Research Published', 'Progress %'
    ]);

    $groups = $con->query("
        SELECT g.id AS group_id, g.research_title, g.research_id,
               g.title_approved_at, g.title_status,
               COALESCE(f.name, '') AS adviser_name,
               gm.proposal_status, gm.proposal_approved_at,
               gm.final_defense_status, gm.final_defense_approved_at,
               gm.hardbound_submitted_status, gm.hardbound_submitted_approved_at,
               gm.applied_copyright_status, gm.applied_copyright_approved_at,
               gm.research_presented_status, gm.research_presented_approved_at,
               gm.research_published_status, gm.research_published_approved_at,
               gm.copyright_approved_status
        FROM groups g
        LEFT JOIN faculties f ON g.adviser_id = f.id
        LEFT JOIN group_milestones gm ON g.id = gm.group_id
        ORDER BY g.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groups as $g) {
        $gid = $g['group_id'];
        $rid = $g['research_id'];

        $checks = [
            $g['title_status'] === 'approved',
            ($g['proposal_status']            ?? '') === 'completed',
            !empty(getUrecApprovedAt($con, $rid, 'UREC Form')),
            !empty(getUrecApprovedAt($con, $rid, 'UREC Clearance')),
            ($g['final_defense_status']       ?? '') === 'completed',
            ($g['hardbound_submitted_status'] ?? '') === 'completed',
            ($g['applied_copyright_status']   ?? '') === 'completed',
            ($g['research_presented_status']  ?? '') === 'completed',
            ($g['research_published_status']  ?? '') === 'completed',
        ];
        $progress = round((count(array_filter($checks)) / count($checks)) * 100);

        fputcsv($output, [
            $g['research_title'] ?: '',
            getStudentNames($con, $gid),
            $g['adviser_name'],
            getProgramName($con, $gid),
            $g['title_status'] === 'approved' ? formatDate($g['title_approved_at']) : '',
            formatDate($g['proposal_approved_at']),
            formatDate(getUrecApprovedAt($con, $rid, 'UREC Form')),
            formatDate(getUrecApprovedAt($con, $rid, 'UREC Clearance')),
            formatDate($g['final_defense_approved_at']),
            formatDate($g['hardbound_submitted_approved_at']),
            formatDate($g['applied_copyright_approved_at']),
            formatDate($g['research_presented_approved_at']),
            formatDate($g['research_published_approved_at']),
            $progress . '%',
        ]);
    }
}

function exportSDGReport($con, $output) {
    fputcsv($output, ['SDG Alignment', 'Research Title', 'Student Researchers', 'Adviser', 'Program']);
    $rows = $con->query("
        SELECT sd.name AS sdg_name, g.id AS group_id, g.research_title, COALESCE(f.name,'') AS adviser_name
        FROM sdgs sd JOIN thrusts_assignments ta ON sd.id = ta.sdg_id
        JOIN groups g ON g.research_id = ta.research_id
        LEFT JOIN faculties f ON g.adviser_id = f.id
        ORDER BY sd.name, g.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        fputcsv($output, [$r['sdg_name'], $r['research_title'] ?: '', getStudentNames($con, $r['group_id']), $r['adviser_name'], getProgramName($con, $r['group_id'])]);
    }
}

function exportThrustReport($con, $output) {
    fputcsv($output, ['University Research Agenda', 'Research Title', 'Student Researchers', 'Adviser', 'Program']);
    $rows = $con->query("
        SELECT t.name AS thrust_name, g.id AS group_id, g.research_title, COALESCE(f.name,'') AS adviser_name
        FROM thrusts t JOIN thrusts_assignments ta ON t.id = ta.thrust_id
        JOIN groups g ON g.research_id = ta.research_id
        LEFT JOIN faculties f ON g.adviser_id = f.id
        ORDER BY t.name, g.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        fputcsv($output, [$r['thrust_name'], $r['research_title'] ?: '', getStudentNames($con, $r['group_id']), $r['adviser_name'], getProgramName($con, $r['group_id'])]);
    }
}