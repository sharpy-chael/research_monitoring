<?php
session_start();
include('../connect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action   = $_POST['action']    ?? '';
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
        case 'import_csv':
            importCsv($con);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function buildDisplayName($firstname, $middlename, $lastname) {
    $fn = trim($firstname ?? '');
    $mn = trim($middlename ?? '');
    $ln = trim($lastname ?? '');
    return trim($fn . ($mn ? ' ' . $mn : '') . ($ln ? ' ' . $ln : ''));
}

// ── CREATE ───────────────────────────────────────────────────────────────────

function createUser($con, $type, $data) {
    $password = trim($data['password'] ?? '');

    if (!$password) {
        throw new Exception('Password is required');
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    switch ($type) {
        case 'student':
            $lastname   = trim($data['lastname']   ?? '');
            $firstname  = trim($data['firstname']  ?? '');
            $middlename = trim($data['middlename'] ?? '');
            $schoolId   = trim($data['school_id']  ?? '');
            $program    = trim($data['program']    ?? '');
            $groupId    = !empty($data['group_id']) ? $data['group_id'] : null;

            if (!$lastname || !$firstname) {
                throw new Exception('Last name and first name are required for students');
            }
            if (!$schoolId || !$program) {
                throw new Exception('School ID and Program are required for students');
            }

            $checkStmt = $con->prepare("SELECT id FROM student WHERE school_id = :school_id");
            $checkStmt->execute(['school_id' => $schoolId]);
            if ($checkStmt->fetch()) {
                throw new Exception('School ID already exists');
            }

            $stmt = $con->prepare("
                INSERT INTO student (lastname, firstname, middlename, school_id, program, pass_word, group_id, is_active)
                VALUES (:lastname, :firstname, :middlename, :school_id, :program, :password, :group_id, TRUE)
            ");
            $stmt->execute([
                'lastname'   => $lastname,
                'firstname'  => $firstname,
                'middlename' => $middlename,
                'school_id'  => $schoolId,
                'program'    => $program,
                'password'   => $hashedPassword,
                'group_id'   => $groupId
            ]);
            break;

        case 'advisor':
            $name      = trim($data['name']       ?? '');
            $advisorId = trim($data['advisor_id'] ?? '');

            if (!$name) {
                throw new Exception('Name is required');
            }
            if (!$advisorId) {
                throw new Exception('Advisor ID is required');
            }

            $checkStmt = $con->prepare("SELECT id FROM advisor WHERE advisor_id = :advisor_id");
            $checkStmt->execute(['advisor_id' => $advisorId]);
            if ($checkStmt->fetch()) {
                throw new Exception('Advisor ID already exists');
            }

            $stmt = $con->prepare("
                INSERT INTO advisor (name, advisor_id, pass_word, is_active)
                VALUES (:name, :advisor_id, :password, TRUE)
            ");
            $stmt->execute([
                'name'       => $name,
                'advisor_id' => $advisorId,
                'password'   => $hashedPassword
            ]);
            break;

        case 'coordinator':
            $name = trim($data['name'] ?? '');
            if (!$name) {
                throw new Exception('Name is required');
            }
            $stmt = $con->prepare("
                INSERT INTO coordinator (name, pass_word, is_active)
                VALUES (:name, :password, TRUE)
            ");
            $stmt->execute(['name' => $name, 'password' => $hashedPassword]);
            break;

        default:
            throw new Exception('Invalid user type');
    }

    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' created successfully']);
}

// ── UPDATE ───────────────────────────────────────────────────────────────────

function updateUser($con, $type, $data) {
    $userId   = $data['user_id']  ?? '';
    $password = trim($data['password'] ?? '');

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    switch ($type) {
        case 'student':
            $lastname   = trim($data['lastname']   ?? '');
            $firstname  = trim($data['firstname']  ?? '');
            $middlename = trim($data['middlename'] ?? '');
            $schoolId   = trim($data['school_id']  ?? '');
            $program    = trim($data['program']    ?? '');
            $groupId    = !empty($data['group_id']) ? $data['group_id'] : null;

            if (!$lastname || !$firstname) {
                throw new Exception('Last name and first name are required for students');
            }
            if (!$schoolId || !$program) {
                throw new Exception('School ID and Program are required for students');
            }

            $checkStmt = $con->prepare("SELECT id FROM student WHERE school_id = :school_id AND id != :user_id");
            $checkStmt->execute(['school_id' => $schoolId, 'user_id' => $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('School ID already exists for another student');
            }

            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $con->prepare("
                    UPDATE student
                    SET lastname = :lastname, firstname = :firstname, middlename = :middlename,
                        school_id = :school_id, program = :program,
                        pass_word = :password, group_id = :group_id
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    'lastname'   => $lastname,
                    'firstname'  => $firstname,
                    'middlename' => $middlename,
                    'school_id'  => $schoolId,
                    'program'    => $program,
                    'password'   => $hashedPassword,
                    'group_id'   => $groupId,
                    'user_id'    => $userId
                ]);
            } else {
                $stmt = $con->prepare("
                    UPDATE student
                    SET lastname = :lastname, firstname = :firstname, middlename = :middlename,
                        school_id = :school_id, program = :program, group_id = :group_id
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    'lastname'   => $lastname,
                    'firstname'  => $firstname,
                    'middlename' => $middlename,
                    'school_id'  => $schoolId,
                    'program'    => $program,
                    'group_id'   => $groupId,
                    'user_id'    => $userId
                ]);
            }
            break;

        case 'advisor':
            $name      = trim($data['name']       ?? '');
            $advisorId = trim($data['advisor_id'] ?? '');

            if (!$name) {
                throw new Exception('Name is required');
            }

            if ($advisorId) {
                $checkStmt = $con->prepare("SELECT id FROM advisor WHERE advisor_id = :advisor_id AND id != :user_id");
                $checkStmt->execute(['advisor_id' => $advisorId, 'user_id' => $userId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Advisor ID already exists for another advisor');
                }
            }

            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                if ($advisorId) {
                    $stmt = $con->prepare("UPDATE advisor SET name = :name, advisor_id = :advisor_id, pass_word = :password WHERE id = :user_id");
                    $stmt->execute(['name' => $name, 'advisor_id' => $advisorId, 'password' => $hashedPassword, 'user_id' => $userId]);
                } else {
                    $stmt = $con->prepare("UPDATE advisor SET name = :name, pass_word = :password WHERE id = :user_id");
                    $stmt->execute(['name' => $name, 'password' => $hashedPassword, 'user_id' => $userId]);
                }
            } else {
                if ($advisorId) {
                    $stmt = $con->prepare("UPDATE advisor SET name = :name, advisor_id = :advisor_id WHERE id = :user_id");
                    $stmt->execute(['name' => $name, 'advisor_id' => $advisorId, 'user_id' => $userId]);
                } else {
                    $stmt = $con->prepare("UPDATE advisor SET name = :name WHERE id = :user_id");
                    $stmt->execute(['name' => $name, 'user_id' => $userId]);
                }
            }
            break;

        case 'coordinator':
            $name = trim($data['name'] ?? '');
            if (!$name) {
                throw new Exception('Name is required');
            }
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $con->prepare("UPDATE coordinator SET name = :name, pass_word = :password WHERE id = :user_id");
                $stmt->execute(['name' => $name, 'password' => $hashedPassword, 'user_id' => $userId]);
            } else {
                $stmt = $con->prepare("UPDATE coordinator SET name = :name WHERE id = :user_id");
                $stmt->execute(['name' => $name, 'user_id' => $userId]);
            }
            break;

        default:
            throw new Exception('Invalid user type');
    }

    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' updated successfully']);
}

// ── TOGGLE STATUS ────────────────────────────────────────────────────────────

function toggleUserStatus($con, $type, $data) {
    $userId   = $data['user_id']   ?? '';
    $isActive = $data['is_active'] === 'true';

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    $validTypes = ['student', 'advisor', 'coordinator'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Invalid user type');
    }

    $stmt = $con->prepare("UPDATE $type SET is_active = :is_active WHERE id = :user_id");
    $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
    $stmt->bindValue(':user_id',   $userId,   PDO::PARAM_INT);
    $stmt->execute();

    $status = $isActive ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' ' . $status . ' successfully']);
}

// ── IMPORT CSV ───────────────────────────────────────────────────────────────

function importCsv($con) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $file = $_FILES['csv_file'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        throw new Exception('Only CSV files are allowed');
    }

    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Could not open the uploaded file');
    }

    $validPrograms = [];
    $progStmt = $con->query("SELECT code FROM programs WHERE is_active = TRUE");
    foreach ($progStmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $validPrograms[] = strtoupper($p['code']);
    }

    $imported = 0;
    $skipped  = 0;
    $errors   = [];
    $rowNum   = 0;

    $insertStmt = $con->prepare("
        INSERT INTO student (lastname, firstname, middlename, school_id, program, pass_word, group_id, is_active)
        VALUES (:lastname, :firstname, :middlename, :school_id, :program, :password, NULL, TRUE)
    ");

    $checkStmt = $con->prepare("SELECT id FROM student WHERE school_id = :school_id");

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        $rowNum++;

        if ($rowNum === 1) {
            continue;
        }

        if (empty(array_filter($row))) {
            continue;
        }

        if (count($row) < 6) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Not enough columns (expected lastname, firstname, middlename, school_id, program, password)";
            continue;
        }

        $lastname   = trim($row[0]);
        $firstname  = trim($row[1]);
        $middlename = trim($row[2]);
        $schoolId   = trim($row[3]);
        $program    = strtoupper(trim($row[4]));
        $password   = trim($row[5]);

        if (empty($lastname) || empty($firstname)) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Last name and first name are required";
            continue;
        }

        if (empty($schoolId)) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Student ID is required";
            continue;
        }

        if (!in_array($program, $validPrograms)) {
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Program '{$program}' is not a valid active program";
            continue;
        }

        if (strlen($password) < 8) {
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Password must be at least 8 characters";
            continue;
        }

        $checkStmt->execute(['school_id' => $schoolId]);
        if ($checkStmt->fetch()) {
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Student ID '{$schoolId}' already exists — skipped";
            continue;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt->execute([
            'lastname'   => $lastname,
            'firstname'  => $firstname,
            'middlename' => $middlename,
            'school_id'  => $schoolId,
            'program'    => $program,
            'password'   => $hashedPassword
        ]);

        $imported++;
    }

    fclose($handle);

    echo json_encode([
        'success'  => true,
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'message'  => "{$imported} student(s) imported, {$skipped} skipped"
    ]);
}
?>