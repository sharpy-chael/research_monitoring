<?php
include("connect.php");
$data = json_decode(file_get_contents('php://input'), true);

$school_ids_raw = trim($data['school_ids'] ?? '');
$group_id = $data['group_id'] ?? null;
$new_group = trim($data['new_group'] ?? '');
$delete_group_id = $data['delete_group_id'] ?? null;
$delete_student = $data['delete_student'] ?? false;

header('Content-Type: application/json');

if ($delete_group_id) {
    try {
        $con->beginTransaction();

        $stmt = $con->prepare("
            UPDATE student 
            SET group_id = NULL, is_leader = NULL 
            WHERE group_id = :group_id
        ");
        $stmt->execute(['group_id' => $delete_group_id]);

        $stmt = $con->prepare("DELETE FROM groups WHERE id = :id");
        $stmt->execute(['id' => $delete_group_id]);

        $con->commit();

        echo json_encode(['success' => true,'message' => 'Group deleted']);
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false,'message' => 'Failed to delete group']);
    }
    exit;
}

if ($delete_student && isset($data['id'])) {
    $stmt = $con->prepare("UPDATE student SET group_id = NULL WHERE id=:id");
    $stmt->execute(['id'=>$data['id']]);
    echo json_encode(['success'=>true,'message'=>'Student removed']);
    exit;
}

if (!$school_ids_raw && !$new_group) {
    echo json_encode(['success'=>false, 'message'=>'Missing school IDs or group']);
    exit;
}

if ($new_group) {
    $stmt = $con->prepare("SELECT COUNT(*) FROM groups WHERE UPPER(name) = :name");
    $stmt->execute(['name'=>strtoupper($new_group)]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success'=>false, 'message'=>'Group name already exist']);
        exit;
    }
    $stmt = $con->prepare("INSERT INTO groups (name) VALUES (:name)");
    $stmt->execute(['name'=>$new_group]);
    $group_id = $con->lastInsertId();
} elseif (!$group_id) {
    echo json_encode(['success'=>false, 'message'=>'Please select a group']);
    exit;
}

$school_ids = array_filter(array_map(function($id){
    return preg_replace('/\s+/', '', strtoupper(trim($id)));
}, explode(',', $school_ids_raw)));

$added = [];
$not_found = [];
$already_in_group = [];

foreach($school_ids as $school_id){
    $stmt = $con->prepare("SELECT * FROM student WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
    $stmt->execute(['school_id'=>$school_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        if ($student['group_id']) {
            $already_in_group[] = $student['name'];
        } else {
            $update = $con->prepare("UPDATE student SET group_id=:group_id WHERE id=:id");
            $update->execute(['group_id'=>$group_id, 'id'=>$student['id']]);
            $added[] = $student['name'];
        }
    } else {
        $not_found[] = $school_id;
    }
}

$message = '';
if ($added) $message .= "Added: ".implode(', ',$added).". ";
if ($already_in_group) $message .= "Student already exist in a Group: ".implode(', ',$already_in_group).". ";
if ($not_found) $message .= "Not found: ".implode(', ',$not_found).".";

echo json_encode([
    'success'=>true,
    'message'=>$message,
    'added_members'=>$added,
    'group_id'=>$group_id
]);
