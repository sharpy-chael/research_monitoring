<?php
session_start();
include('connect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['submit']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$itemType = $_POST['item_type'] ?? '';

try {
    switch ($action) {
        case 'create':
            createItem($con, $itemType, $_POST);
            break;
        case 'update':
            updateItem($con, $itemType, $_POST);
            break;
        case 'toggle_status':
            toggleStatus($con, $itemType, $_POST);
            break;
        case 'set_active_ay':
            setActiveAY($con, $_POST);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createItem($con, $type, $data) {
    switch ($type) {
        case 'program':
            $code = trim($data['code'] ?? '');
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (!$code || !$name) {
                throw new Exception('Program code and name are required');
            }
            
            // Check if code exists
            $checkStmt = $con->prepare("SELECT id FROM programs WHERE code = :code");
            $checkStmt->execute(['code' => $code]);
            if ($checkStmt->fetch()) {
                throw new Exception('Program code already exists');
            }
            
            $stmt = $con->prepare("
                INSERT INTO programs (code, name, description, is_active)
                VALUES (:code, :name, :description, TRUE)
            ");
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Program created successfully']);
            break;
            
        case 'academic-year':
            $yearStart = intval($data['year_start'] ?? 0);
            $yearEnd = intval($data['year_end'] ?? 0);
            $semester = intval($data['semester'] ?? 1);
            
            if (!$yearStart || !$yearEnd) {
                throw new Exception('Start year and end year are required');
            }
            
            if ($yearEnd <= $yearStart) {
                throw new Exception('End year must be greater than start year');
            }
            
            // Check if already exists
            $checkStmt = $con->prepare("
                SELECT id FROM academic_years 
                WHERE year_start = :year_start AND year_end = :year_end AND semester = :semester
            ");
            $checkStmt->execute([
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'semester' => $semester
            ]);
            if ($checkStmt->fetch()) {
                throw new Exception('This academic year and semester combination already exists');
            }
            
            $stmt = $con->prepare("
                INSERT INTO academic_years (year_start, year_end, semester, is_active)
                VALUES (:year_start, :year_end, :semester, FALSE)
            ");
            $stmt->execute([
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'semester' => $semester
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Academic year created successfully']);
            break;
            
        case 'research-status':
            $name = trim($data['status_name'] ?? '');
            $description = trim($data['status_description'] ?? '');
            $color = trim($data['color'] ?? '#007bff');
            $displayOrder = intval($data['display_order'] ?? 0);
            
            if (!$name) {
                throw new Exception('Status name is required');
            }
            
            // Check if name exists
            $checkStmt = $con->prepare("SELECT id FROM research_statuses WHERE name = :name");
            $checkStmt->execute(['name' => $name]);
            if ($checkStmt->fetch()) {
                throw new Exception('Status name already exists');
            }
            
            $stmt = $con->prepare("
                INSERT INTO research_statuses (name, description, color, display_order, is_active)
                VALUES (:name, :description, :color, :display_order, TRUE)
            ");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'color' => $color,
                'display_order' => $displayOrder
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Research status created successfully']);
            break;
            
        default:
            throw new Exception('Invalid item type');
    }
}

function updateItem($con, $type, $data) {
    $itemId = intval($data['item_id'] ?? 0);
    
    if (!$itemId) {
        throw new Exception('Item ID is required');
    }
    
    switch ($type) {
        case 'program':
            $code = trim($data['code'] ?? '');
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (!$code || !$name) {
                throw new Exception('Program code and name are required');
            }
            
            // Check if code exists for another program
            $checkStmt = $con->prepare("SELECT id FROM programs WHERE code = :code AND id != :id");
            $checkStmt->execute(['code' => $code, 'id' => $itemId]);
            if ($checkStmt->fetch()) {
                throw new Exception('Program code already exists');
            }
            
            $stmt = $con->prepare("
                UPDATE programs 
                SET code = :code, name = :name, description = :description
                WHERE id = :id
            ");
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'id' => $itemId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Program updated successfully']);
            break;
            
        case 'academic-year':
            $yearStart = intval($data['year_start'] ?? 0);
            $yearEnd = intval($data['year_end'] ?? 0);
            $semester = intval($data['semester'] ?? 1);
            
            if (!$yearStart || !$yearEnd) {
                throw new Exception('Start year and end year are required');
            }
            
            if ($yearEnd <= $yearStart) {
                throw new Exception('End year must be greater than start year');
            }
            
            // Check if already exists for another record
            $checkStmt = $con->prepare("
                SELECT id FROM academic_years 
                WHERE year_start = :year_start AND year_end = :year_end 
                AND semester = :semester AND id != :id
            ");
            $checkStmt->execute([
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'semester' => $semester,
                'id' => $itemId
            ]);
            if ($checkStmt->fetch()) {
                throw new Exception('This academic year and semester combination already exists');
            }
            
            $stmt = $con->prepare("
                UPDATE academic_years 
                SET year_start = :year_start, year_end = :year_end, semester = :semester
                WHERE id = :id
            ");
            $stmt->execute([
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'semester' => $semester,
                'id' => $itemId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Academic year updated successfully']);
            break;
            
        case 'research-status':
            $name = trim($data['status_name'] ?? '');
            $description = trim($data['status_description'] ?? '');
            $color = trim($data['color'] ?? '#007bff');
            $displayOrder = intval($data['display_order'] ?? 0);
            
            if (!$name) {
                throw new Exception('Status name is required');
            }
            
            // Check if name exists for another status
            $checkStmt = $con->prepare("SELECT id FROM research_statuses WHERE name = :name AND id != :id");
            $checkStmt->execute(['name' => $name, 'id' => $itemId]);
            if ($checkStmt->fetch()) {
                throw new Exception('Status name already exists');
            }
            
            $stmt = $con->prepare("
                UPDATE research_statuses 
                SET name = :name, description = :description, color = :color, display_order = :display_order
                WHERE id = :id
            ");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'color' => $color,
                'display_order' => $displayOrder,
                'id' => $itemId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Research status updated successfully']);
            break;
            
        default:
            throw new Exception('Invalid item type');
    }
}

function toggleStatus($con, $type, $data) {
    $itemId = intval($data['item_id'] ?? 0);
    $isActive = ($data['is_active'] === 'true') ? 't' : 'f';
    
    if (!$itemId) {
        throw new Exception('Item ID is required');
    }
    
    $validTypes = ['program', 'research-status'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Invalid item type');
    }
    
    $table = ($type === 'program') ? 'programs' : 'research_statuses';
    
    $stmt = $con->prepare("UPDATE " . $table . " SET is_active = :is_active WHERE id = :id");
    $stmt->execute([
        'is_active' => $isActive,
        'id' => $itemId
    ]);
    
    $status = ($isActive === 't') ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' ' . $status . ' successfully']);
}

function setActiveAY($con, $data) {
    $ayId = intval($data['ay_id'] ?? 0);
    $isActive = ($data['is_active'] === 'true');
    
    if (!$ayId) {
        throw new Exception('Academic year ID is required');
    }
    
    if ($isActive) {
        // Deactivate all other academic years first
        $con->query("UPDATE academic_years SET is_active = FALSE");
    }
    
    // Set the selected one
    $stmt = $con->prepare("UPDATE academic_years SET is_active = :is_active WHERE id = :id");
    $stmt->execute([
        'is_active' => $isActive ? 't' : 'f',
        'id' => $ayId
    ]);
    
    $status = $isActive ? 'set as active' : 'deactivated';
    echo json_encode(['success' => true, 'message' => 'Academic year ' . $status . ' successfully']);
}
?>