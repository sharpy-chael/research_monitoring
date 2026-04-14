<?php
session_start();
include('../connect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$q    = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if (strlen($q) < 1) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

try {
    $results = [];

    $stmt = $con->prepare("
        SELECT s.id, CONCAT(s.firstname, ' ', s.lastname) AS name,
               s.school_id AS user_code, 'student' AS user_type
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE u.is_active = TRUE
          AND (CONCAT(s.firstname, ' ', s.lastname) ILIKE :q OR s.school_id ILIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['q' => $like, 'q2' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = $row;
    }

    $stmt = $con->prepare("
        SELECT f.id, f.name, u.username AS user_code, 'advisor' AS user_type
        FROM faculties f
        JOIN users u ON f.user_id = u.id
        WHERE u.is_active = TRUE
          AND (f.name ILIKE :q OR u.username ILIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['q' => $like, 'q2' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = $row;
    }

    $stmt = $con->prepare("
        SELECT u.id, u.username AS name, NULL AS user_code, 'coordinator' AS user_type
        FROM users u
        WHERE u.role = 'coordinator'
          AND u.is_active = TRUE
          AND u.username ILIKE :q
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
?>