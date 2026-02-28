<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';

if ($action === 'add_sdg') {
    $name = trim($data['name'] ?? '');
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
        exit;
    }
    $check = $con->prepare("SELECT id FROM un_sdgs WHERE LOWER(name) = LOWER(:name)");
    $check->execute(['name' => $name]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'SDG already exists']);
        exit;
    }
    $stmt = $con->prepare("INSERT INTO un_sdgs (name, advisor_id) VALUES (:name, NULL) RETURNING id");
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'id' => $row['id']]);
    exit;
}

if ($action === 'delete_sdg') {
    $id = (int)($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    $con->prepare("DELETE FROM group_sdgs WHERE sdg_id = :id")->execute(['id' => $id]);
    $con->prepare("DELETE FROM un_sdgs WHERE id = :id")->execute(['id' => $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'add_thrust') {
    $name = trim($data['name'] ?? '');
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
        exit;
    }
    $check = $con->prepare("SELECT id FROM research_thrusts WHERE LOWER(name) = LOWER(:name)");
    $check->execute(['name' => $name]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Thrust already exists']);
        exit;
    }
    $stmt = $con->prepare("INSERT INTO research_thrusts (name, advisor_id) VALUES (:name, NULL) RETURNING id");
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'id' => $row['id']]);
    exit;
}

if ($action === 'delete_thrust') {
    $id = (int)($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    $con->prepare("DELETE FROM group_thrusts WHERE thrust_id = :id")->execute(['id' => $id]);
    $con->prepare("DELETE FROM research_thrusts WHERE id = :id")->execute(['id' => $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove_member') {
    $student_id = (int)($data['student_id'] ?? 0);
    $group_id   = (int)($data['group_id'] ?? 0);
    if (!$student_id || !$group_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    $stmt = $con->prepare("UPDATE student SET group_id = NULL, is_leader = FALSE WHERE id = :id AND group_id = :gid");
    $stmt->execute(['id' => $student_id, 'gid' => $group_id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);