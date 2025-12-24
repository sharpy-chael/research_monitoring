<?php
session_start();
include('connect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$userType = $_POST['user_type'] ?? '';

try {
    switch ($action) {
        case 'create':
            createUser($con, $userType, $_POST);
            break;
        case 'update':
            updateUser($con, $userType, $_POST);
            break;
        case 'toggle_status':
            toggleUserStatus($con, $userType, $_POST);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createUser($con, $type, $data) {
    $name = trim($data['name'] ?? '');
    $password = trim($data['password'] ?? '');
    
    if (!$name || !$password) {
        throw new Exception('Name and password are required');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    switch ($type) {
        case 'student':
            $schoolId = trim($data['school_id'] ?? '');
            $program = trim($data['program'] ?? '');
            $groupId = !empty($data['group_id']) ? $data['group_id'] : null;
            
            if (!$schoolId || !$program) {
                throw new Exception('School ID and Program are required for students');
            }
            
            // Check if school_id already exists
            $checkStmt = $con->prepare("SELECT id FROM student WHERE school_id = :school_id");
            $checkStmt->execute(['school_id' => $schoolId]);
            if ($checkStmt->fetch()) {
                throw new Exception('School ID already exists');
            }
            
            $stmt = $con->prepare("
                INSERT INTO student (name, school_id, program, pass_word, group_id, is_active)
                VALUES (:name, :school_id, :program, :password, :group_id, TRUE)
            ");
            $stmt->execute([
                'name' => $name,
                'school_id' => $schoolId,
                'program' => $program,
                'password' => $hashedPassword,
                'group_id' => $groupId
            ]);
            break;
            
        case 'advisor':
            $stmt = $con->prepare("
                INSERT INTO advisor (name, pass_word, is_active)
                VALUES (:name, :password, TRUE)
            ");
            $stmt->execute([
                'name' => $name,
                'password' => $hashedPassword
            ]);
            break;
            
        case 'coordinator':
            $stmt = $con->prepare("
                INSERT INTO coordinator (name, pass_word, is_active)
                VALUES (:name, :password, TRUE)
            ");
            $stmt->execute([
                'name' => $name,
                'password' => $hashedPassword
            ]);
            break;
            
        default:
            throw new Exception('Invalid user type');
    }
    
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' created successfully']);
}

function updateUser($con, $type, $data) {
    $userId = $data['user_id'] ?? '';
    $name = trim($data['name'] ?? '');
    $password = trim($data['password'] ?? '');
    
    if (!$userId || !$name) {
        throw new Exception('User ID and name are required');
    }
    
    switch ($type) {
        case 'student':
            $schoolId = trim($data['school_id'] ?? '');
            $program = trim($data['program'] ?? '');
            $groupId = !empty($data['group_id']) ? $data['group_id'] : null;
            
            if (!$schoolId || !$program) {
                throw new Exception('School ID and Program are required for students');
            }
            
            // Check if school_id exists for another student
            $checkStmt = $con->prepare("SELECT id FROM student WHERE school_id = :school_id AND id != :user_id");
            $checkStmt->execute(['school_id' => $schoolId, 'user_id' => $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('School ID already exists for another student');
            }
            
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $con->prepare("
                    UPDATE student 
                    SET name = :name, school_id = :school_id, program = :program, 
                        pass_word = :password, group_id = :group_id
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    'name' => $name,
                    'school_id' => $schoolId,
                    'program' => $program,
                    'password' => $hashedPassword,
                    'group_id' => $groupId,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $con->prepare("
                    UPDATE student 
                    SET name = :name, school_id = :school_id, program = :program, group_id = :group_id
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    'name' => $name,
                    'school_id' => $schoolId,
                    'program' => $program,
                    'group_id' => $groupId,
                    'user_id' => $userId
                ]);
            }
            break;
            
        case 'advisor':
        case 'coordinator':
            $table = $type;
            
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $con->prepare("UPDATE $table SET name = :name, pass_word = :password WHERE id = :user_id");
                $stmt->execute([
                    'name' => $name,
                    'password' => $hashedPassword,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $con->prepare("UPDATE $table SET name = :name WHERE id = :user_id");
                $stmt->execute([
                    'name' => $name,
                    'user_id' => $userId
                ]);
            }
            break;
            
        default:
            throw new Exception('Invalid user type');
    }
    
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' updated successfully']);
}

function toggleUserStatus($con, $type, $data) {
    $userId = $data['user_id'] ?? '';
    $isActive = $data['is_active'] === 'true'; // Convert to boolean
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    $validTypes = ['student', 'advisor', 'coordinator'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Invalid user type');
    }
    
    // Use parameterized query with boolean binding
    $stmt = $con->prepare("UPDATE $type SET is_active = :is_active WHERE id = :user_id");
    $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $status = $isActive ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' ' . $status . ' successfully']);
}
?>