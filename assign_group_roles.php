<?php
include("connect.php");
session_start();

header('Content-Type: application/json');

try {
    $groupId = $_POST['group_id'] ?? '';
    $newGroup = trim($_POST['new_group'] ?? '');
    $leaderId = trim($_POST['leader_id'] ?? '');
    $adviserId = trim($_POST['adviser_id'] ?? '');
    $students = trim($_POST['students'] ?? '');

    // Debug log
    error_log("Received data: " . json_encode($_POST));

    $response = [
        'status' => 'error',
        'message' => '',
        'group_id' => null,
        'adviser_name' => null,
        'leader_name' => null,
        'added_members' => []
    ];

    // Create new group if specified
    if (!empty($newGroup)) {
        // Check if group already exists
        $checkStmt = $con->prepare("SELECT id FROM groups WHERE name = :name");
        $checkStmt->execute(['name' => $newGroup]);
        
        if ($checkStmt->fetch()) {
            $response['message'] = "Group '$newGroup' already exists.";
            echo json_encode($response);
            exit;
        }

        // Create the group
        $insertStmt = $con->prepare("INSERT INTO groups (name) VALUES (:name)");
        $insertStmt->execute(['name' => $newGroup]);
        $groupId = $con->lastInsertId();
        
        error_log("Created new group with ID: $groupId");
    } else if (empty($groupId)) {
        $response['message'] = "Please select a group or create a new one.";
        echo json_encode($response);
        exit;
    }

    // Assign adviser if provided
    if (!empty($adviserId)) {
        // Check if adviser exists
        $adviserCheckStmt = $con->prepare("SELECT id, name FROM advisor WHERE id = :id");
        $adviserCheckStmt->execute(['id' => $adviserId]);
        $adviser = $adviserCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adviser) {
            $updateAdviserStmt = $con->prepare("UPDATE groups SET adviser_id = :adviser_id WHERE id = :group_id");
            $updateAdviserStmt->execute([
                'adviser_id' => $adviserId,
                'group_id' => $groupId
            ]);
            $response['adviser_name'] = $adviser['name'];
            error_log("Assigned adviser: " . $adviser['name']);
        } else {
            $response['message'] = "Adviser ID $adviserId not found.";
            echo json_encode($response);
            exit;
        }
    }

    // Assign leader if provided
    if (!empty($leaderId)) {
        // First, remove any existing leader from this group
        $removeLeaderStmt = $con->prepare("UPDATE student SET is_leader = FALSE WHERE group_id = :group_id AND is_leader = TRUE");
        $removeLeaderStmt->execute(['group_id' => $groupId]);

        // Check if student exists
        $leaderCheckStmt = $con->prepare("SELECT id, name FROM student WHERE school_id = :school_id");
        $leaderCheckStmt->execute(['school_id' => $leaderId]);
        $leader = $leaderCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leader) {
            $updateLeaderStmt = $con->prepare("UPDATE student SET group_id = :group_id, is_leader = TRUE WHERE school_id = :school_id");
            $updateLeaderStmt->execute([
                'group_id' => $groupId,
                'school_id' => $leaderId
            ]);
            $response['leader_name'] = $leader['name'];
            error_log("Assigned leader: " . $leader['name']);
        } else {
            $response['message'] = "Leader with School ID $leaderId not found.";
            echo json_encode($response);
            exit;
        }
    }

    // Assign members if provided
    if (!empty($students)) {
        $studentIds = array_map('trim', explode(',', $students));
        $studentIds = array_filter($studentIds); // Remove empty values
        
        foreach ($studentIds as $studentId) {
            // Skip if this is the leader ID
            if ($studentId === $leaderId) {
                continue;
            }

            // Check if student exists
            $studentCheckStmt = $con->prepare("SELECT id, name FROM student WHERE school_id = :school_id");
            $studentCheckStmt->execute(['school_id' => $studentId]);
            $student = $studentCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $updateStudentStmt = $con->prepare("UPDATE student SET group_id = :group_id, is_leader = FALSE WHERE school_id = :school_id");
                $updateStudentStmt->execute([
                    'group_id' => $groupId,
                    'school_id' => $studentId
                ]);
                $response['added_members'][] = [
                    'id' => $student['id'],
                    'name' => $student['name']
                ];
                error_log("Added member: " . $student['name']);
            } else {
                error_log("Student not found: $studentId");
            }
        }
    }

    $response['status'] = 'success';
    $response['group_id'] = $groupId;
    $response['message'] = 'Group assignment successful!';
    
    error_log("Success response: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>