<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

$like = '%' . $query . '%';

$stmt = $con->prepare("
    SELECT
        s.id,
        s.school_id,
        TRIM(COALESCE(s.firstname,'') || ' ' || COALESCE(s.middlename,'') || ' ' || COALESCE(s.lastname,'')) AS name,
        p.code as program
    FROM students s
    LEFT JOIN programs p ON s.program_id = p.id
    WHERE s.school_id ILIKE :q1
       OR s.firstname  ILIKE :q2
       OR s.middlename ILIKE :q3
       OR s.lastname   ILIKE :q4
    ORDER BY s.school_id
    LIMIT 10
");
$stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as &$row) {
    $groupStmt = $con->prepare("
        SELECT g.name, sg.is_leader
        FROM student_groups sg
        JOIN groups g ON sg.group_id = g.id
        WHERE sg.student_id = :student_id
        ORDER BY g.name
    ");
    $groupStmt->execute(['student_id' => $row['id']]);
    $row['groups'] = $groupStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($row);

echo json_encode($results);