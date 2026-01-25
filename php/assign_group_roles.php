<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    // ============================================
    // ADVISOR: CREATE NEW GROUP
    // ============================================
    if ($action === 'create_group') {
        // Verify user is an advisor
        if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'advisor') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        // Get the actual advisor_id from advisor table
        $sessionUserId = $_SESSION['id'];
        $advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
        $advisorStmt->execute(['id' => $sessionUserId]);
        $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
        $advisorId = $advisorData['advisor_id'];
        
        if (!$advisorId) {
            echo json_encode(['success' => false, 'message' => 'Advisor ID not found']);
            exit;
        }
        
        $groupName = trim($_POST['group_name'] ?? '');
        
        if (empty($groupName)) {
            echo json_encode(['success' => false, 'message' => 'Group name is required']);
            exit;
        }
        
        // Check if group name already exists
        $checkStmt = $con->prepare("SELECT id FROM groups WHERE name = :name");
        $checkStmt->execute(['name' => $groupName]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A group with this name already exists']);
            exit;
        }
        
        // Create the group with advisor_id
        $insertStmt = $con->prepare("INSERT INTO groups (name, advisor_id) VALUES (:name, :advisor_id)");
        $insertStmt->execute([
            'name' => $groupName,
            'advisor_id' => $advisorId
        ]);
        
        $newGroupId = $con->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Group created successfully',
            'group_id' => $newGroupId
        ]);
        exit;
    }
    
    // ============================================
    // ADVISOR: ADD MEMBERS TO GROUP
    // ============================================
    if ($action === 'add_members') {
        // Verify user is an advisor
        if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'advisor') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        // Get the actual advisor_id from advisor table
        $sessionUserId = $_SESSION['id'];
        $advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
        $advisorStmt->execute(['id' => $sessionUserId]);
        $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
        $advisorId = $advisorData['advisor_id'];
        
        if (!$advisorId) {
            echo json_encode(['success' => false, 'message' => 'Advisor ID not found']);
            exit;
        }
        
        $groupId = $_POST['group_id'] ?? null;
        $leaderId = trim($_POST['leader_id'] ?? '');
        $memberIds = trim($_POST['member_ids'] ?? '');
        
        if (empty($groupId)) {
            echo json_encode(['success' => false, 'message' => 'Group ID is required']);
            exit;
        }
        
        // Verify that this group belongs to the logged-in advisor
        $verifyStmt = $con->prepare("SELECT id FROM groups WHERE id = :group_id AND advisor_id = :advisor_id");
        $verifyStmt->execute(['group_id' => $groupId, 'advisor_id' => $advisorId]);
        
        if (!$verifyStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to modify this group']);
            exit;
        }
        
        $addedMembers = [];
        $notFound = [];
        $alreadyInGroup = [];
        
        // Assign leader if provided
        if (!empty($leaderId)) {
            // First, remove any existing leader from this group
            $removeLeaderStmt = $con->prepare("UPDATE student SET is_leader = FALSE WHERE group_id = :group_id AND is_leader = TRUE");
            $removeLeaderStmt->execute(['group_id' => $groupId]);
            
            // Check if student exists
            $leaderCheckStmt = $con->prepare("SELECT id, name, group_id FROM student WHERE school_id = :school_id");
            $leaderCheckStmt->execute(['school_id' => $leaderId]);
            $leader = $leaderCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($leader) {
                if ($leader['group_id'] && $leader['group_id'] != $groupId) {
                    $alreadyInGroup[] = $leader['name'] . ' (Leader)';
                } else {
                    $updateLeaderStmt = $con->prepare("UPDATE student SET group_id = :group_id, is_leader = TRUE WHERE school_id = :school_id");
                    $updateLeaderStmt->execute([
                        'group_id' => $groupId,
                        'school_id' => $leaderId
                    ]);
                    $addedMembers[] = $leader['name'] . ' (Leader)';
                }
            } else {
                $notFound[] = $leaderId . ' (Leader)';
            }
        }
        
        // Assign members if provided
        if (!empty($memberIds)) {
            $memberIdArray = array_map('trim', explode(',', $memberIds));
            $memberIdArray = array_filter($memberIdArray); // Remove empty values
            
            foreach ($memberIdArray as $memberId) {
                // Skip if this is the leader ID
                if ($memberId === $leaderId) {
                    continue;
                }
                
                // Check if student exists
                $memberCheckStmt = $con->prepare("SELECT id, name, group_id FROM student WHERE school_id = :school_id");
                $memberCheckStmt->execute(['school_id' => $memberId]);
                $member = $memberCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($member) {
                    if ($member['group_id']) {
                        $alreadyInGroup[] = $member['name'];
                    } else {
                        $updateMemberStmt = $con->prepare("UPDATE student SET group_id = :group_id, is_leader = FALSE WHERE school_id = :school_id");
                        $updateMemberStmt->execute([
                            'group_id' => $groupId,
                            'school_id' => $memberId
                        ]);
                        $addedMembers[] = $member['name'];
                    }
                } else {
                    $notFound[] = $memberId;
                }
            }
        }
        
        // Build response message
        $message = '';
        if (!empty($addedMembers)) {
            $message .= 'Added: ' . implode(', ', $addedMembers) . '. ';
        }
        if (!empty($alreadyInGroup)) {
            $message .= 'Already in a group: ' . implode(', ', $alreadyInGroup) . '. ';
        }
        if (!empty($notFound)) {
            $message .= 'Not found: ' . implode(', ', $notFound) . '. ';
        }
        
        if (empty($message)) {
            $message = 'No changes were made.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => trim($message),
            'added_members' => $addedMembers,
            'already_in_group' => $alreadyInGroup,
            'not_found' => $notFound
        ]);
        exit;
    }
    
    // ============================================
    // ORIGINAL COORDINATOR FUNCTIONALITY (KEPT FOR BACKWARDS COMPATIBILITY)
    // Note: This is now deprecated as coordinators no longer create groups
    // ============================================
    $groupId = $_POST['group_id'] ?? '';
    $newGroup = trim($_POST['new_group'] ?? '');
    $leaderId = trim($_POST['leader_id'] ?? '');
    $advisorId = trim($_POST['advisor_id'] ?? '');
    $students = trim($_POST['students'] ?? '');

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
    if (!empty($advisorId)) {
        // Check if adviser exists using advisor_id column
        $adviserCheckStmt = $con->prepare("SELECT advisor_id, name FROM advisor WHERE advisor_id = :advisor_id");
        $adviserCheckStmt->execute(['advisor_id' => $advisorId]);
        $adviser = $adviserCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adviser) {
            // Update groups table with advisor_id
            $updateAdviserStmt = $con->prepare("UPDATE groups SET advisor_id = :advisor_id WHERE id = :group_id");
            $updateAdviserStmt->execute([
                'advisor_id' => $advisorId,
                'group_id' => $groupId
            ]);
            $response['adviser_name'] = $adviser['name'];
            error_log("Assigned adviser: " . $adviser['name']);
        } else {
            $response['message'] = "Adviser ID $advisorId not found.";
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
                $response['message'] = "Student with School ID $studentId not found.";
                echo json_encode($response);
                exit;
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