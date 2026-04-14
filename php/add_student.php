<?php
include("../connect.php");
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$school_ids_raw    = trim($data['school_ids']       ?? '');
$group_id          = $data['group_id']              ?? null;
$new_group         = trim($data['new_group']        ?? '');
$delete_group_id   = $data['delete_group_id']       ?? null;
$delete_student    = $data['delete_student']        ?? false;
$create_group      = $data['create_group']          ?? false;
$leader_school_id  = trim($data['leader_school_id'] ?? '');
$member_school_ids = $data['member_school_ids']     ?? [];

header('Content-Type: application/json');

if ($create_group) {
    $group_name     = trim($data['group_name']     ?? '');
    $research_title = trim($data['research_title'] ?? '');

    if (!$group_name) {
        echo json_encode(['success' => false, 'message' => 'Group name is required']);
        exit;
    }

    $stmt = $con->prepare("SELECT COUNT(*) FROM groups WHERE UPPER(name) = :name");
    $stmt->execute(['name' => strtoupper($group_name)]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Group name already exists']);
        exit;
    }

    $sessionUserId = $_SESSION['id'];
    $facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
    $facStmt->execute(['user_id' => $sessionUserId]);
    $facRow = $facStmt->fetch(PDO::FETCH_ASSOC);
    $facultyId = $facRow['id'] ?? null;

    $titleStatus = $research_title ? 'pending_approval' : 'missing';

    try {
        $con->beginTransaction();

        $resStmt = $con->prepare("INSERT INTO research_table (title) VALUES (:title) RETURNING id");
        $resStmt->execute(['title' => $research_title ?: null]);
        $resRow = $resStmt->fetch(PDO::FETCH_ASSOC);
        $researchId = $resRow['id'];

        $insertStmt = $con->prepare("
            INSERT INTO groups (name, adviser_id, research_title, title_status, research_id)
            VALUES (:name, :adviser_id, :title, :status, :research_id)
            RETURNING id
        ");
        $insertStmt->execute([
            'name'        => $group_name,
            'adviser_id'  => $facultyId,
            'title'       => $research_title ?: null,
            'status'      => $titleStatus,
            'research_id' => $researchId,
        ]);
        $result = $insertStmt->fetch(PDO::FETCH_ASSOC);

        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Group created successfully', 'group_id' => $result['id']]);
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to create group: ' . $e->getMessage()]);
    }
    exit;
}

if ($leader_school_id || !empty($member_school_ids)) {
    if (!$group_id) {
        echo json_encode(['success' => false, 'message' => 'Group ID is required']);
        exit;
    }

    $added     = [];
    $not_found = [];

    try {
        $con->beginTransaction();

        if ($leader_school_id) {
            $leaderSchoolId = preg_replace('/\s+/', '', strtoupper(trim($leader_school_id)));
            $stmt = $con->prepare("SELECT * FROM students WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
            $stmt->execute(['school_id' => $leaderSchoolId]);
            $leader = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($leader) {
                $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                $checkStmt->execute(['student_id' => $leader['id'], 'group_id' => $group_id]);
                if ($checkStmt->rowCount() > 0) {
                    $con->prepare("UPDATE student_groups SET is_leader = TRUE WHERE student_id = :student_id AND group_id = :group_id")
                        ->execute(['student_id' => $leader['id'], 'group_id' => $group_id]);
                } else {
                    $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, TRUE)")
                        ->execute(['student_id' => $leader['id'], 'group_id' => $group_id]);
                }
                $added[] = TRIM(($leader['firstname'] ?? '') . ' ' . ($leader['lastname'] ?? '')) . ' (Leader)';
            } else {
                $not_found[] = $leaderSchoolId;
            }
        }

        if (!empty($member_school_ids)) {
            foreach ($member_school_ids as $member_id) {
                $memberSchoolId = preg_replace('/\s+/', '', strtoupper(trim($member_id)));
                if (!$memberSchoolId) continue;

                $stmt = $con->prepare("SELECT * FROM students WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
                $stmt->execute(['school_id' => $memberSchoolId]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($member) {
                    $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                    $checkStmt->execute(['student_id' => $member['id'], 'group_id' => $group_id]);
                    if ($checkStmt->rowCount() === 0) {
                        $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                            ->execute(['student_id' => $member['id'], 'group_id' => $group_id]);
                    }
                    $added[] = TRIM(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? ''));
                } else {
                    $not_found[] = $memberSchoolId;
                }
            }
        }

        $con->commit();

        $message = '';
        if ($added)     $message .= "Added: " . implode(', ', $added) . ". ";
        if ($not_found) $message .= "Not found: " . implode(', ', $not_found) . ".";

        echo json_encode(['success' => true, 'message' => $message]);
        exit;

    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to add members: ' . $e->getMessage()]);
        exit;
    }
}

if ($delete_group_id) {
    try {
        $con->beginTransaction();
        $con->prepare("DELETE FROM student_groups WHERE group_id = :group_id")
            ->execute(['group_id' => $delete_group_id]);
        $con->prepare("DELETE FROM groups WHERE id = :id")
            ->execute(['id' => $delete_group_id]);
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Group deleted']);
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete group']);
    }
    exit;
}

if ($delete_student && isset($data['id'])) {
    $con->prepare("DELETE FROM student_groups WHERE student_id = :student_id AND group_id = :group_id")
        ->execute(['student_id' => $data['id'], 'group_id' => $group_id]);
    echo json_encode(['success' => true, 'message' => 'Student removed']);
    exit;
}

if (!$school_ids_raw && !$new_group) {
    echo json_encode(['success' => false, 'message' => 'Missing school IDs or group']);
    exit;
}

if ($new_group) {
    $stmt = $con->prepare("SELECT COUNT(*) FROM groups WHERE UPPER(name) = :name");
    $stmt->execute(['name' => strtoupper($new_group)]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Group name already exists']);
        exit;
    }
    $stmt = $con->prepare("INSERT INTO groups (name) VALUES (:name) RETURNING id");
    $stmt->execute(['name' => $new_group]);
    $result   = $stmt->fetch(PDO::FETCH_ASSOC);
    $group_id = $result['id'];
} elseif (!$group_id) {
    echo json_encode(['success' => false, 'message' => 'Please select a group']);
    exit;
}

$school_ids = array_filter(array_map(function ($id) {
    return preg_replace('/\s+/', '', strtoupper(trim($id)));
}, explode(',', $school_ids_raw)));

$added            = [];
$not_found        = [];
$already_in_group = [];

foreach ($school_ids as $school_id) {
    $stmt = $con->prepare("SELECT * FROM students WHERE REPLACE(UPPER(school_id),' ','') = :school_id");
    $stmt->execute(['school_id' => $school_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
        $checkStmt->execute(['student_id' => $student['id'], 'group_id' => $group_id]);
        if ($checkStmt->rowCount() > 0) {
            $already_in_group[] = TRIM(($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? ''));
        } else {
            $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                ->execute(['student_id' => $student['id'], 'group_id' => $group_id]);
            $added[] = TRIM(($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? ''));
        }
    } else {
        $not_found[] = $school_id;
    }
}

$message = '';
if ($added)            $message .= "Added: " . implode(', ', $added) . ". ";
if ($already_in_group) $message .= "Already in this group: " . implode(', ', $already_in_group) . ". ";
if ($not_found)        $message .= "Not found: " . implode(', ', $not_found) . ".";

echo json_encode([
    'success'       => true,
    'message'       => $message,
    'added_members' => $added,
    'group_id'      => $group_id
]);