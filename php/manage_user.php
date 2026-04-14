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

function createUser($con, $type, $data) {
    $password = trim($data['password'] ?? '');
    if (!$password) throw new Exception('Password is required');
    if (strlen($password) < 8) throw new Exception('Password must be at least 8 characters');

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    switch ($type) {
        case 'student':
            $lastname   = trim($data['lastname']   ?? '');
            $firstname  = trim($data['firstname']  ?? '');
            $middlename = trim($data['middlename'] ?? '');
            $schoolId   = trim($data['school_id']  ?? '');
            $programId  = intval($data['program_id'] ?? 0);
            $groupId    = !empty($data['group_id']) ? intval($data['group_id']) : null;

            if (!$lastname || !$firstname) throw new Exception('Last name and first name are required');
            if (!$schoolId || !$programId) throw new Exception('School ID and Program are required');

            $checkStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id");
            $checkStmt->execute(['school_id' => $schoolId]);
            if ($checkStmt->fetch()) throw new Exception('School ID already exists');

            $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username");
            $checkUser->execute(['username' => $schoolId]);
            if ($checkUser->fetch()) throw new Exception('School ID already exists as a username');

            $con->beginTransaction();

            $userStmt = $con->prepare("INSERT INTO users (username, pass_word, role, is_active) VALUES (:username, :password, 'student', TRUE)");
            $userStmt->execute(['username' => $schoolId, 'password' => $hashedPassword]);
            $userId = $con->lastInsertId();

            $studentStmt = $con->prepare("
                INSERT INTO students (user_id, school_id, firstname, lastname, middlename, program_id, is_active)
                VALUES (:user_id, :school_id, :firstname, :lastname, :middlename, :program_id, TRUE)
                RETURNING id
            ");
            $studentStmt->execute([
                'user_id'    => $userId,
                'school_id'  => $schoolId,
                'firstname'  => $firstname,
                'lastname'   => $lastname,
                'middlename' => $middlename,
                'program_id' => $programId,
            ]);
            $studentId = $studentStmt->fetchColumn();

            if ($groupId && $studentId) {
                $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                    ->execute(['student_id' => $studentId, 'group_id' => $groupId]);
            }

            $con->commit();
            break;

        case 'advisor':
            $name      = trim($data['name']       ?? '');
            $advisorId = trim($data['advisor_id'] ?? '');

            if (!$name)      throw new Exception('Name is required');
            if (!$advisorId) throw new Exception('Advisor ID is required');

            $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username");
            $checkUser->execute(['username' => $name]);
            if ($checkUser->fetch()) throw new Exception('Advisor name already exists');

            $con->beginTransaction();
            $userStmt = $con->prepare("INSERT INTO users (username, pass_word, role, is_active) VALUES (:username, :password, 'advisor', TRUE)");
            $userStmt->execute(['username' => $name, 'password' => $hashedPassword]);
            $userId = $con->lastInsertId();

            $facultyStmt = $con->prepare("INSERT INTO faculties (user_id, name) VALUES (:user_id, :name)");
            $facultyStmt->execute(['user_id' => $userId, 'name' => $name]);
            $con->commit();
            break;

        case 'coordinator':
            $name = trim($data['name'] ?? '');
            if (!$name) throw new Exception('Name is required');

            $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username AND role = 'coordinator'");
            $checkUser->execute(['username' => $name]);
            if ($checkUser->fetch()) throw new Exception('Coordinator name already exists');

            $userStmt = $con->prepare("INSERT INTO users (username, pass_word, role, is_active) VALUES (:username, :password, 'coordinator', TRUE)");
            $userStmt->execute(['username' => $name, 'password' => $hashedPassword]);
            break;

        default:
            throw new Exception('Invalid user type');
    }

    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' created successfully']);
}

function updateUser($con, $type, $data) {
    $userId   = intval($data['user_id']  ?? 0);
    $password = trim($data['password']   ?? '');

    if (!$userId) throw new Exception('User ID is required');

    switch ($type) {
        case 'student':
            $lastname   = trim($data['lastname']   ?? '');
            $firstname  = trim($data['firstname']  ?? '');
            $middlename = trim($data['middlename'] ?? '');
            $schoolId   = trim($data['school_id']  ?? '');
            $programId  = intval($data['program_id'] ?? 0);
            $groupId    = !empty($data['group_id']) ? intval($data['group_id']) : null;

            if (!$lastname || !$firstname) throw new Exception('Last name and first name are required');
            if (!$schoolId || !$programId) throw new Exception('School ID and Program are required');

            $checkStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id AND id != :id");
            $checkStmt->execute(['school_id' => $schoolId, 'id' => $userId]);
            if ($checkStmt->fetch()) throw new Exception('School ID already exists for another student');

            $studentRow = $con->prepare("SELECT user_id FROM students WHERE id = :id");
            $studentRow->execute(['id' => $userId]);
            $student = $studentRow->fetch(PDO::FETCH_ASSOC);

            if ($password) {
                if (strlen($password) < 8) throw new Exception('Password must be at least 8 characters');
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $con->prepare("UPDATE users SET pass_word = :password WHERE id = :id")
                    ->execute(['password' => $hashed, 'id' => $student['user_id']]);
            }

            $con->prepare("UPDATE users SET username = :username WHERE id = :id")
                ->execute(['username' => $schoolId, 'id' => $student['user_id']]);

            $con->prepare("
                UPDATE students
                SET lastname = :lastname, firstname = :firstname, middlename = :middlename,
                    school_id = :school_id, program_id = :program_id
                WHERE id = :id
            ")->execute([
                'lastname'   => $lastname,
                'firstname'  => $firstname,
                'middlename' => $middlename,
                'school_id'  => $schoolId,
                'program_id' => $programId,
                'id'         => $userId
            ]);

            // Handle group assignment via student_groups
            if ($groupId) {
                $checkSg = $con->prepare("SELECT id FROM student_groups WHERE student_id = :student_id AND group_id = :group_id");
                $checkSg->execute(['student_id' => $userId, 'group_id' => $groupId]);
                if (!$checkSg->fetch()) {
                    $con->prepare("INSERT INTO student_groups (student_id, group_id, is_leader) VALUES (:student_id, :group_id, FALSE)")
                        ->execute(['student_id' => $userId, 'group_id' => $groupId]);
                }
            }
            break;

        case 'advisor':
            $name      = trim($data['name']       ?? '');
            $advisorId = trim($data['advisor_id'] ?? '');
            if (!$name) throw new Exception('Name is required');

            $facultyRow = $con->prepare("SELECT user_id FROM faculties WHERE id = :id");
            $facultyRow->execute(['id' => $userId]);
            $faculty = $facultyRow->fetch(PDO::FETCH_ASSOC);

            $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username AND id != :uid");
            $checkUser->execute(['username' => $name, 'uid' => $faculty['user_id']]);
            if ($checkUser->fetch()) throw new Exception('Advisor name already exists');

            $con->prepare("UPDATE users SET username = :username WHERE id = :id")
                ->execute(['username' => $name, 'id' => $faculty['user_id']]);

            if ($password) {
                if (strlen($password) < 8) throw new Exception('Password must be at least 8 characters');
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $con->prepare("UPDATE users SET pass_word = :password WHERE id = :id")
                    ->execute(['password' => $hashed, 'id' => $faculty['user_id']]);
            }

            $con->prepare("UPDATE faculties SET name = :name WHERE id = :id")
                ->execute(['name' => $name, 'id' => $userId]);
            break;

        case 'coordinator':
            $name = trim($data['name'] ?? '');
            if (!$name) throw new Exception('Name is required');

            if ($password) {
                if (strlen($password) < 8) throw new Exception('Password must be at least 8 characters');
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $con->prepare("UPDATE users SET username = :username, pass_word = :password WHERE id = :id")
                    ->execute(['username' => $name, 'password' => $hashed, 'id' => $userId]);
            } else {
                $con->prepare("UPDATE users SET username = :username WHERE id = :id")
                    ->execute(['username' => $name, 'id' => $userId]);
            }
            break;

        default:
            throw new Exception('Invalid user type');
    }

    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' updated successfully']);
}

function toggleUserStatus($con, $type, $data) {
    $userId   = intval($data['user_id']   ?? 0);
    $isActive = $data['is_active'] === 'true';

    if (!$userId) throw new Exception('User ID is required');

    switch ($type) {
        case 'student':
            $row = $con->prepare("SELECT user_id FROM students WHERE id = :id");
            $row->execute(['id' => $userId]);
            $student = $row->fetch(PDO::FETCH_ASSOC);
            $con->prepare("UPDATE users SET is_active = :is_active WHERE id = :id")
                ->execute(['is_active' => $isActive, 'id' => $student['user_id']]);
            $con->prepare("UPDATE students SET is_active = :is_active WHERE id = :id")
                ->execute(['is_active' => $isActive, 'id' => $userId]);
            break;

        case 'advisor':
            $row = $con->prepare("SELECT user_id FROM faculties WHERE id = :id");
            $row->execute(['id' => $userId]);
            $faculty = $row->fetch(PDO::FETCH_ASSOC);
            $con->prepare("UPDATE users SET is_active = :is_active WHERE id = :id")
                ->execute(['is_active' => $isActive, 'id' => $faculty['user_id']]);
            break;

        case 'coordinator':
            $con->prepare("UPDATE users SET is_active = :is_active WHERE id = :id")
                ->execute(['is_active' => $isActive, 'id' => $userId]);
            break;

        default:
            throw new Exception('Invalid user type');
    }

    $status = $isActive ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' ' . $status . ' successfully']);
}

function importCsv($con) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') throw new Exception('Only CSV files are allowed');

    // Normalise Windows \r\n → \n so fgetcsv does not produce ghost columns
    $rawContent = file_get_contents($_FILES['csv_file']['tmp_name']);
    $rawContent = str_replace("\r\n", "\n", $rawContent);
    $rawContent = str_replace("\r",   "\n", $rawContent);
    $tmpPath    = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tmpPath, $rawContent);

    $handle = fopen($tmpPath, 'r');
    if (!$handle) throw new Exception('Could not open the uploaded file');

    $validPrograms = [];
    foreach ($con->query("SELECT id, code FROM programs WHERE is_active = TRUE")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $validPrograms[strtoupper($p['code'])] = $p['id'];
    }

    $imported = 0;
    $skipped  = 0;
    $errors   = [];
    $rowNum   = 0;

    $checkStmt = $con->prepare("SELECT id FROM students WHERE school_id = :school_id");
    $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username");

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        $rowNum++;
        if ($rowNum === 1) continue;
        if (empty(array_filter($row))) continue;

        if (count($row) < 6) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Not enough columns";
            continue;
        }

        $lastname   = trim($row[0] ?? '', " \t\n\r\0\x0B");
        $firstname  = trim($row[1] ?? '', " \t\n\r\0\x0B");
        $middlename = trim($row[2] ?? '', " \t\n\r\0\x0B");
        $schoolId   = trim($row[3] ?? '', " \t\n\r\0\x0B");
        $program    = strtoupper(trim($row[4] ?? '', " \t\n\r\0\x0B"));
        $password   = trim($row[5] ?? '', " \t\n\r\0\x0B");

        if (!$lastname || !$firstname) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Last name and first name are required";
            continue;
        }
        if (!$schoolId) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Student ID is required";
            continue;
        }
        if (!isset($validPrograms[$program])) {
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Program '{$program}' is not valid";
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
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Student ID '{$schoolId}' already exists";
            continue;
        }

        $checkUser->execute(['username' => $schoolId]);
        if ($checkUser->fetch()) {
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): Username '{$schoolId}' already exists";
            continue;
        }

        try {
            $con->beginTransaction();
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $con->prepare("INSERT INTO users (username, pass_word, role, is_active) VALUES (:username, :password, 'student', TRUE)")
                ->execute(['username' => $schoolId, 'password' => $hashed]);
            $userId = $con->lastInsertId();

            // No group_id in students table — CSV import creates student without group assignment
            $con->prepare("
                INSERT INTO students (user_id, school_id, firstname, lastname, middlename, program_id, is_active)
                VALUES (:user_id, :school_id, :firstname, :lastname, :middlename, :program_id, TRUE)
            ")->execute([
                'user_id'    => $userId,
                'school_id'  => $schoolId,
                'firstname'  => $firstname,
                'lastname'   => $lastname,
                'middlename' => $middlename,
                'program_id' => $validPrograms[$program]
            ]);

            $con->commit();
            $imported++;
        } catch (PDOException $e) {
            $con->rollBack();
            $skipped++;
            $errors[] = "Row {$rowNum} ({$firstname} {$lastname}): " . $e->getMessage();
        }
    }

    fclose($handle);
    if (isset($tmpPath) && file_exists($tmpPath)) unlink($tmpPath);

    echo json_encode([
        'success'  => true,
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'message'  => "{$imported} student(s) imported, {$skipped} skipped"
    ]);
}
?>