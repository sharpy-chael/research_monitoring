<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_group') {
        if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'advisor') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
        $facStmt->execute(['user_id' => $_SESSION['id']]);
        $facRow = $facStmt->fetch(PDO::FETCH_ASSOC);
        $facultyId = $facRow['id'] ?? null;

        if (!$facultyId) {
            echo json_encode(['success' => false, 'message' => 'Advisor ID not found']);
            exit;
        }

        $groupName = trim($_POST['group_name'] ?? '');

        if (empty($groupName)) {
            echo json_encode(['success' => false, 'message' => 'Group name is required']);
            exit;
        }

        $checkStmt = $con->prepare("SELECT id FROM groups WHERE name = :name");
        $checkStmt->execute(['name' => $groupName]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A group with this name already exists']);
            exit;
        }

        $insertStmt = $con->prepare("INSERT INTO groups (name, adviser_id) VALUES (:name, :adviser_id) RETURNING id");
        $insertStmt->execute(['name' => $groupName, 'adviser_id' => $facultyId]);
        $newGroupId = $insertStmt->fetch(PDO::FETCH_ASSOC)['id'];

        echo json_encode(['success' => true, 'message' => 'Group created successfully', 'group_id' => $newGroupId]);
        exit;
    }

    if ($action === 'add_members') {
        if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'advisor') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $facStmt = $con->prepare("SELECT id FROM faculties WHERE user_id = :user_id");
        $facStmt->execute(['user_id' => $_SESSION['id']]);
        $facRow = $facStmt->fetch(PDO::FETCH_ASSOC);
        $facultyId = $facRow['id'] ?? null;

        if (!$facultyId) {
            echo json_encode(['success' => false, 'message' => 'Advisor ID not found']);
            exit;
        }

        $groupId   = $_POST['group_id']   ?? null;
        $leaderId  = trim($_POST['leader_id']  ?? '');
        $memberIds = trim($_POST['member_ids'] ?? '');

        if (empty($groupId)) {
            echo json_encode(['success' => false, 'message' => 'Group ID is required']);
            exit;
        }

        $verifyStmt = $con->prepare("SELECT id FROM groups WHERE id = :group_id AND adviser_id = :adviser_id");
        $verifyStmt->execute(['group_id' => $groupId, 'adviser_id' => $facultyId]);
        if (!$verifyStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to modify this group']);
            exit;
        }

        $addedMembers = []; $notFound = [];

        if (!empty($leaderId)) {
            $leaderCheckStmt = $con->prepare("
                SELECT id, TRIM(COALESCE(firstname,'') || ' ' || COALESCE(middlename,'') || ' ' || COALESCE(lastname,'')) AS name
                FROM students WHERE school_id = :school_id
            ");
            $leaderCheckStmt->execute(['school_id' => $leaderId]);
            $leader = $leaderCheckStmt->fetch(PDO::FETCH_ASSOC);

            if ($leader) {
                $con->prepare("UPDATE student_groups SET is_leader = FALSE WHERE group_id = :group_id AND is_leader = TRUE")
                    ->execute(['group_id' => $groupId]);

                $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                $checkStmt->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
                if ($checkStmt->rowCount() > 0) {
                    $con->prepare("UPDATE student_groups SET is_leader = TRUE WHERE student_id = :student_id AND group_id = :group_id")
                        ->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
                } else {
                    $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, TRUE)")
                        ->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
                }
                $addedMembers[] = $leader['name'] . ' (Leader)';
            } else {
                $notFound[] = $leaderId . ' (Leader)';
            }
        }

        if (!empty($memberIds)) {
            $memberIdArray = array_filter(array_map('trim', explode(',', $memberIds)));
            foreach ($memberIdArray as $memberId) {
                if ($memberId === $leaderId) continue;

                $memberCheckStmt = $con->prepare("
                    SELECT id, TRIM(COALESCE(firstname,'') || ' ' || COALESCE(middlename,'') || ' ' || COALESCE(lastname,'')) AS name
                    FROM students WHERE school_id = :school_id
                ");
                $memberCheckStmt->execute(['school_id' => $memberId]);
                $member = $memberCheckStmt->fetch(PDO::FETCH_ASSOC);

                if ($member) {
                    $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                    $checkStmt->execute(['student_id' => $member['id'], 'group_id' => $groupId]);
                    if ($checkStmt->rowCount() === 0) {
                        $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                            ->execute(['student_id' => $member['id'], 'group_id' => $groupId]);
                    }
                    $addedMembers[] = $member['name'];
                } else {
                    $notFound[] = $memberId;
                }
            }
        }

        $message = '';
        if (!empty($addedMembers)) $message .= 'Added: '     . implode(', ', $addedMembers) . '. ';
        if (!empty($notFound))     $message .= 'Not found: ' . implode(', ', $notFound)      . '. ';
        if (empty($message))       $message = 'No changes were made.';

        echo json_encode([
            'success'       => true,
            'message'       => trim($message),
            'added_members' => $addedMembers,
            'not_found'     => $notFound
        ]);
        exit;
    }

    $groupId   = $_POST['group_id']   ?? '';
    $newGroup  = trim($_POST['new_group']  ?? '');
    $leaderId  = trim($_POST['leader_id']  ?? '');
    $advisorId = trim($_POST['advisor_id'] ?? '');
    $students  = trim($_POST['students']   ?? '');

    $response = [
        'status'        => 'error',
        'message'       => '',
        'group_id'      => null,
        'adviser_name'  => null,
        'leader_name'   => null,
        'added_members' => []
    ];

    if (!empty($newGroup)) {
        $checkStmt = $con->prepare("SELECT id FROM groups WHERE name = :name");
        $checkStmt->execute(['name' => $newGroup]);
        if ($checkStmt->fetch()) {
            $response['message'] = "Group '$newGroup' already exists.";
            echo json_encode($response);
            exit;
        }
        $insertStmt = $con->prepare("INSERT INTO groups (name) VALUES (:name) RETURNING id");
        $insertStmt->execute(['name' => $newGroup]);
        $groupId = $insertStmt->fetch(PDO::FETCH_ASSOC)['id'];
    } else if (empty($groupId)) {
        $response['message'] = "Please select a group or create a new one.";
        echo json_encode($response);
        exit;
    }

    if (!empty($advisorId)) {
        $adviserCheckStmt = $con->prepare("SELECT id, name FROM faculties WHERE id = :adviser_id");
        $adviserCheckStmt->execute(['adviser_id' => $advisorId]);
        $adviser = $adviserCheckStmt->fetch(PDO::FETCH_ASSOC);

        if ($adviser) {
            $con->prepare("UPDATE groups SET adviser_id = :adviser_id WHERE id = :group_id")
                ->execute(['adviser_id' => $advisorId, 'group_id' => $groupId]);
            $response['adviser_name'] = $adviser['name'];
        } else {
            $response['message'] = "Adviser ID $advisorId not found.";
            echo json_encode($response);
            exit;
        }
    }

    if (!empty($leaderId)) {
        $leaderCheckStmt = $con->prepare("
            SELECT id, TRIM(COALESCE(firstname,'') || ' ' || COALESCE(middlename,'') || ' ' || COALESCE(lastname,'')) AS name
            FROM students WHERE school_id = :school_id
        ");
        $leaderCheckStmt->execute(['school_id' => $leaderId]);
        $leader = $leaderCheckStmt->fetch(PDO::FETCH_ASSOC);

        if ($leader) {
            $con->prepare("UPDATE student_groups SET is_leader = FALSE WHERE group_id = :group_id AND is_leader = TRUE")
                ->execute(['group_id' => $groupId]);

            $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
            $checkStmt->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
            if ($checkStmt->rowCount() > 0) {
                $con->prepare("UPDATE student_groups SET is_leader = TRUE WHERE student_id = :student_id AND group_id = :group_id")
                    ->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
            } else {
                $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, TRUE)")
                    ->execute(['student_id' => $leader['id'], 'group_id' => $groupId]);
            }
            $response['leader_name'] = $leader['name'];
        } else {
            $response['message'] = "Leader with School ID $leaderId not found.";
            echo json_encode($response);
            exit;
        }
    }

    if (!empty($students)) {
        $studentIds = array_filter(array_map('trim', explode(',', $students)));
        foreach ($studentIds as $studentId) {
            if ($studentId === $leaderId) continue;

            $studentCheckStmt = $con->prepare("
                SELECT id, TRIM(COALESCE(firstname,'') || ' ' || COALESCE(middlename,'') || ' ' || COALESCE(lastname,'')) AS name
                FROM students WHERE school_id = :school_id
            ");
            $studentCheckStmt->execute(['school_id' => $studentId]);
            $student = $studentCheckStmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                $checkStmt = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                $checkStmt->execute(['student_id' => $student['id'], 'group_id' => $groupId]);
                if ($checkStmt->rowCount() === 0) {
                    $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                        ->execute(['student_id' => $student['id'], 'group_id' => $groupId]);
                }
                $response['added_members'][] = ['id' => $student['id'], 'name' => $student['name']];
            } else {
                $response['message'] = "Student with School ID $studentId not found.";
                echo json_encode($response);
                exit;
            }
        }
    }

    $response['status']   = 'success';
    $response['group_id'] = $groupId;
    $response['message']  = 'Group assignment successful!';
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}