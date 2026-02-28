<?php
include("../connect.php");
session_start();
$data = json_decode(file_get_contents('php://input'), true);

$school_ids_raw = trim($data['school_ids'] ?? '');
$group_id = $data['group_id'] ?? null;
$new_group = trim($data['new_group'] ?? '');
$delete_group_id = $data['delete_group_id'] ?? null;
$delete_student = $data['delete_student'] ?? false;
$create_group = $data['create_group'] ?? false;
$leader_school_id = trim($data['leader_school_id'] ?? '');
$member_school_ids = $data['member_school_ids'] ?? [];
$force_add = $data['force_add'] ?? false;
$force_school_ids = $data['force_school_ids'] ?? [];

header('Content-Type: application/json');

if ($create_group) {
    $group_name = trim($data['group_name'] ?? '');
    $research_title = trim($data['research_title'] ?? '');

    if (!$group_name) {
        echo json_encode(['success'=>false, 'message'=>'Group name is required']);
        exit;
    }

    $stmt = $con->prepare("SELECT COUNT(*) FROM groups WHERE UPPER(name) = :name");
    $stmt->execute(['name'=>strtoupper($group_name)]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success'=>false, 'message'=>'Group name already exists']);
        exit;
    }

    $sessionUserId = $_SESSION['id'];
    $advisorStmt = $con->prepare("SELECT id, advisor_id FROM advisor WHERE id = :id");
    $advisorStmt->execute(['id' => $sessionUserId]);
    $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
    $adviserId = $advisorData['id'] ?? $sessionUserId;
    $advisorId = $advisorData['advisor_id'] ?? $sessionUserId;

    $titleStatus = $research_title ? 'pending_approval' : 'missing';

    $insertStmt = $con->prepare("
        INSERT INTO groups (name, adviser_id, advisor_id, research_title, title_status, title_submitted_at)
        VALUES (:name, :adviser_id, :advisor_id, :title, :status, :submitted_at)
        RETURNING id
    ");
    $insertStmt->execute([
        'name' => $group_name,
        'adviser_id' => $adviserId,
        'advisor_id' => $advisorId,
        'title' => $research_title ?: null,
        'status' => $titleStatus,
        'submitted_at' => $research_title ? date('Y-m-d H:i:s') : null
    ]);
    $result = $insertStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true, 'message'=>'Group created successfully', 'group_id'=>$result['id']]);
    exit;
}

if ($leader_school_id || !empty($member_school_ids)) {
    if (!$group_id) {
        echo json_encode(['success'=>false, 'message'=>'Group ID is required']);
        exit;
    }

    $added = [];
    $not_found = [];
    $conflicts = [];

    try {
        $con->beginTransaction();

        if ($leader_school_id) {
            $leaderSchoolId = preg_replace('/\s+/', '', strtoupper(trim($leader_school_id)));
            $stmt = $con->prepare("SELECT * FROM student WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
            $stmt->execute(['school_id'=>$leaderSchoolId]);
            $leader = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($leader) {
                $isConflict = $leader['group_id'] && $leader['group_id'] != $group_id;
                $isForced = $force_add && in_array($leaderSchoolId, array_map(fn($s) => preg_replace('/\s+/', '', strtoupper($s)), $force_school_ids));

                if ($isConflict && !$isForced) {
                    $conflicts[] = ['name' => $leader['full_name'], 'school_id' => $leader['school_id'], 'role' => 'leader'];
                } else {
                    $update = $con->prepare("UPDATE student SET group_id=:group_id, is_leader=TRUE WHERE id=:id");
                    $update->execute(['group_id'=>$group_id, 'id'=>$leader['id']]);
                    $added[] = $leader['full_name'] . ' (Leader)';
                }
            } else {
                $not_found[] = $leaderSchoolId;
            }
        }

        if (!empty($member_school_ids)) {
            foreach ($member_school_ids as $member_id) {
                $memberSchoolId = preg_replace('/\s+/', '', strtoupper(trim($member_id)));
                if (!$memberSchoolId) continue;

                $stmt = $con->prepare("SELECT * FROM student WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
                $stmt->execute(['school_id'=>$memberSchoolId]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($member) {
                    $isConflict = $member['group_id'] && $member['group_id'] != $group_id;
                    $isForced = $force_add && in_array($memberSchoolId, array_map(fn($s) => preg_replace('/\s+/', '', strtoupper($s)), $force_school_ids));

                    if ($isConflict && !$isForced) {
                        $conflicts[] = ['name' => $member['full_name'], 'school_id' => $member['school_id'], 'role' => 'member'];
                    } else {
                        $update = $con->prepare("UPDATE student SET group_id=:group_id, is_leader=FALSE WHERE id=:id");
                        $update->execute(['group_id'=>$group_id, 'id'=>$member['id']]);
                        $added[] = $member['full_name'];
                    }
                } else {
                    $not_found[] = $memberSchoolId;
                }
            }
        }

        if (!empty($conflicts)) {
            $con->rollBack();
            echo json_encode([
                'success' => false,
                'conflict' => true,
                'conflicts' => $conflicts,
                'message' => 'Some students are already in another group'
            ]);
            exit;
        }

        $con->commit();

        $message = '';
        if ($added) $message .= "Added: ".implode(', ', $added).". ";
        if ($not_found) $message .= "Not found: ".implode(', ', $not_found).".";

        echo json_encode(['success'=>true, 'message'=>$message]);
        exit;

    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success'=>false, 'message'=>'Failed to add members: ' . $e->getMessage()]);
        exit;
    }
}

if ($delete_group_id) {
    try {
        $con->beginTransaction();
        $stmt = $con->prepare("UPDATE student SET group_id = NULL, is_leader = NULL WHERE group_id = :group_id");
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
    $stmt = $con->prepare("UPDATE student SET group_id = NULL, is_leader = FALSE WHERE id=:id");
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
    $stmt = $con->prepare("INSERT INTO groups (name) VALUES (:name) RETURNING id");
    $stmt->execute(['name'=>$new_group]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $group_id = $result['id'];
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

foreach ($school_ids as $school_id) {
    $stmt = $con->prepare("SELECT * FROM student WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
    $stmt->execute(['school_id'=>$school_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        if ($student['group_id']) {
            $already_in_group[] = $student['full_name'];
        } else {
            $update = $con->prepare("UPDATE student SET group_id=:group_id WHERE id=:id");
            $update->execute(['group_id'=>$group_id, 'id'=>$student['id']]);
            $added[] = $student['full_name'];
        }
    } else {
        $not_found[] = $school_id;
    }
}

$message = '';
if ($added) $message .= "Added: ".implode(', ', $added).". ";
if ($already_in_group) $message .= "Student already exist in a Group: ".implode(', ', $already_in_group).". ";
if ($not_found) $message .= "Not found: ".implode(', ', $not_found).".";

echo json_encode([
    'success'=>true,
    'message'=>$message,
    'added_members'=>$added,
    'group_id'=>$group_id
]);