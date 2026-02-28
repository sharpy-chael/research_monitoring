<?php
session_start();
include('../connect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 1) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$like = '%' . $q . '%';

try {
    $results = [];

    $stmt = $con->prepare("
        SELECT id, name, school_id AS user_code, 'student' AS user_type
        FROM student
        WHERE is_active = TRUE
          AND (name ILIKE :q OR school_id ILIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['q' => $like, 'q2' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = $row;
    }

    $stmt = $con->prepare("
        SELECT id, name, advisor_id AS user_code, 'advisor' AS user_type
        FROM advisor
        WHERE is_active = TRUE
          AND (name ILIKE :q OR advisor_id ILIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['q' => $like, 'q2' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = $row;
    }

    $stmt = $con->prepare("
        SELECT id, name, NULL AS user_code, 'coordinator' AS user_type
        FROM coordinator
        WHERE is_active = TRUE
          AND name ILIKE :q
        LIMIT 5
    ");
    $stmt->execute(['q' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = $row;
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}