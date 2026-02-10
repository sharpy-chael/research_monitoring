<?php
include("../connect.php");
include('log_helper.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle GET requests for fetching milestone status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only advisors can fetch milestone status
    if ($_SESSION['role'] !== 'advisor') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $group_id = $_GET['group_id'] ?? null;
    
    if (!$group_id) {
        echo json_encode(['success' => false, 'message' => 'Group ID is required']);
        exit;
    }
    
    try {
        // Verify that the advisor is assigned to this group
        $verifyStmt = $con->prepare("
            SELECT id FROM groups 
            WHERE id = :group_id AND adviser_id = :adviser_id
        ");
        $verifyStmt->execute([
            'group_id' => $group_id,
            'adviser_id' => $_SESSION['id']
        ]);
        
        if (!$verifyStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to view this group']);
            exit;
        }
        
        // Fetch milestone statuses
        $stmt = $con->prepare("
            SELECT proposal_status, final_defense_status, copyright_status
            FROM group_milestones
            WHERE group_id = :group_id
        ");
        $stmt->execute(['group_id' => $group_id]);
        $milestones = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($milestones) {
            echo json_encode([
                'success' => true,
                'milestones' => [
                    'Proposal' => $milestones['proposal_status'],
                    'Final Defense' => $milestones['final_defense_status'],
                    'Copyright / IP' => $milestones['copyright_status']
                ]
            ]);
        } else {
            // No records yet, return default values
            echo json_encode([
                'success' => true,
                'milestones' => [
                    'Proposal' => 'missing',
                    'Final Defense' => 'missing',
                    'Copyright / IP' => 'missing'
                ]
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Determine which action to perform
$action = $_POST['action'] ?? 'update_status';

try {
    // Handle Milestone Updates
    if ($action === 'update_milestone') {
        // Check if user is an advisor
        if ($_SESSION['role'] !== 'advisor') {
            echo json_encode(['success' => false, 'message' => 'Only advisors can update milestones']);
            exit;
        }
        
        $group_id = $_POST['group_id'] ?? null;
        $proposal = $_POST['proposal'] ?? 'missing';
        $final_defense = $_POST['final_defense'] ?? 'missing';
        $copyright = $_POST['copyright'] ?? 'missing';
        
        if (!$group_id) {
            echo json_encode(['success' => false, 'message' => 'Group ID is required']);
            exit;
        }
        
        // Verify that the advisor is assigned to this group
        $verifyStmt = $con->prepare("
            SELECT g.id, g.name 
            FROM groups g
            WHERE g.id = :group_id AND g.adviser_id = :adviser_id
        ");
        $verifyStmt->execute([
            'group_id' => $group_id,
            'adviser_id' => $_SESSION['id']
        ]);
        
        $group = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to update this group']);
            exit;
        }
        
        // Get previous status for logging
        $checkStmt = $con->prepare("
            SELECT proposal_status, final_defense_status, copyright_status 
            FROM group_milestones 
            WHERE group_id = :group_id
        ");
        $checkStmt->execute(['group_id' => $group_id]);
        $oldStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oldStatus) {
            // Update existing records
            $updateStmt = $con->prepare("
                UPDATE group_milestones 
                SET proposal_status = :proposal,
                    final_defense_status = :final_defense,
                    copyright_status = :copyright,
                    updated_at = NOW()
                WHERE group_id = :group_id
            ");
            $updateStmt->execute([
                'proposal' => $proposal,
                'final_defense' => $final_defense,
                'copyright' => $copyright,
                'group_id' => $group_id
            ]);
        } else {
            // Insert new record
            $insertStmt = $con->prepare("
                INSERT INTO group_milestones 
                (group_id, proposal_status, final_defense_status, copyright_status, created_at, updated_at)
                VALUES (:group_id, :proposal, :final_defense, :copyright, NOW(), NOW())
            ");
            $insertStmt->execute([
                'group_id' => $group_id,
                'proposal' => $proposal,
                'final_defense' => $final_defense,
                'copyright' => $copyright
            ]);
        }
        
        // Log the milestone changes
        $changes = [];
        if (!$oldStatus || $oldStatus['proposal_status'] !== $proposal) {
            $changes[] = "Proposal: " . ucfirst($proposal);
        }
        if (!$oldStatus || $oldStatus['final_defense_status'] !== $final_defense) {
            $changes[] = "Final Defense: " . ucfirst($final_defense);
        }
        if (!$oldStatus || $oldStatus['copyright_status'] !== $copyright) {
            $changes[] = "Copyright/IP: " . ucfirst($copyright);
        }
        
        if (!empty($changes)) {
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                'update_milestone',
                $_SESSION['name'] . " updated milestones for group '{$group['name']}': " . implode(", ", $changes)
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Milestones updated successfully']);
        exit;
    }
    
    // Handle Upload/Document Status Updates (Original functionality)
    $document_id = $_POST['document_id'] ?? null;
    $upload_id = $_POST['upload_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if ((!$document_id && !$upload_id) || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Handle UREC document status update
    if ($document_id) {
        // Check current status first to prevent modifying approved documents
        $checkStmt = $con->prepare("SELECT status, document_type, original_filename FROM urec_documents WHERE id = :document_id");
        $checkStmt->execute(['document_id' => $document_id]);
        $docInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$docInfo) {
            echo json_encode(['success' => false, 'message' => 'Document not found']);
            exit;
        }
        
        // CRITICAL: Prevent any status changes if already approved
        if ($docInfo['status'] === 'approved') {
            echo json_encode([
                'success' => false, 
                'message' => 'This document has already been approved and cannot be modified. Once approved, the status is locked.'
            ]);
            exit;
        }
        
        // Update the UREC document status
        $stmt = $con->prepare("UPDATE urec_documents SET status = :status WHERE id = :document_id");
        $stmt->execute([
            'status' => $status,
            'document_id' => $document_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $actionType = ($status === 'approved') ? 'approve' : 'reject';
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                $actionType,
                $_SESSION['name'] . " {$status} UREC document: " . ($docInfo['document_type'] ?? 'Unknown') . " (" . ($docInfo['original_filename'] ?? 'Unknown file') . ")"
            );
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    }
    // Handle regular upload status update
    elseif ($upload_id) {
        // FIRST: Check current status to prevent modifying approved documents
        $checkStmt = $con->prepare("SELECT status, task_name, original_filename FROM uploads WHERE upload_id = :upload_id");
        $checkStmt->execute(['upload_id' => $upload_id]);
        $uploadInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$uploadInfo) {
            echo json_encode(['success' => false, 'message' => 'Upload not found']);
            exit;
        }
        
        // CRITICAL: Prevent any status changes if already approved
        if ($uploadInfo['status'] === 'approved') {
            echo json_encode([
                'success' => false, 
                'message' => 'This document has already been approved and cannot be modified. Once approved, the status is locked.'
            ]);
            exit;
        }
        
        // Proceed with update if not approved
        $stmt = $con->prepare("UPDATE uploads SET status = :status WHERE upload_id = :upload_id");
        $stmt->execute([
            'status' => $status,
            'upload_id' => $upload_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $actionType = ($status === 'approved') ? 'approve' : 'reject';
            logActivity(
                $con,
                $_SESSION['id'],
                $_SESSION['role'],
                $actionType,
                $_SESSION['name'] . " {$status} upload: " . ($uploadInfo['task_name'] ?? 'Unknown') . " (" . ($uploadInfo['original_filename'] ?? 'Unknown file') . ")"
            );
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>