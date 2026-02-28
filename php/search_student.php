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
        school_id,
        COALESCE(NULLIF(TRIM(full_name), ''), TRIM(firstname || ' ' || middlename || ' ' || lastname)) AS name,
        program
    FROM student
    WHERE school_id ILIKE ?
       OR full_name ILIKE ?
       OR firstname ILIKE ?
       OR middlename ILIKE ?
       OR lastname ILIKE ?
    ORDER BY school_id
    LIMIT 10
");
$stmt->execute([$like, $like, $like, $like, $like]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);