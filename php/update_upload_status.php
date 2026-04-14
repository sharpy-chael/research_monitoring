<?php
include("../connect.php");
include('log_helper.php');
session_start();

ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action      = $_POST['action']      ?? 'update_status';
$document_id = $_POST['document_id'] ?? null;
$upload_id   = $_POST['upload_id']   ?? null;
$status      = $_POST['status']      ?? null;

try {
    if ($action === 'update_milestone') {
        if ($_SESSION['role'] !== 'advisor') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Only advisors can update milestones']);
            exit;
        }

        $group_id     = $_POST['group_id']     ?? null;
        $proposal     = $_POST['proposal']     ?? 'missing';
        $final_defense = $_POST['final_defense'] ?? 'missing';
        $copyright    = $_POST['copyright']    ?? 'missing';

        if (!$group_id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Group ID is required']);
            exit;
        }

        $checkStmt = $con->prepare("SELECT group_id FROM group_milestones WHERE group_id = :group_id");
        $checkStmt->execute(['group_id' => $group_id]);

        if ($checkStmt->rowCount() > 0) {
            $con->prepare("
                UPDATE group_milestones
                SET proposal_status = :proposal, final_defense_status = :final_defense,
                    copyright_approved_status = :copyright
                WHERE group_id = :group_id
            ")->execute([
                'proposal'      => $proposal,
                'final_defense' => $final_defense,
                'copyright'     => $copyright,
                'group_id'      => $group_id
            ]);
        } else {
            $con->prepare("
                INSERT INTO group_milestones (group_id, proposal_status, final_defense_status, copyright_approved_status)
                VALUES (:group_id, :proposal, :final_defense, :copyright)
            ")->execute([
                'group_id'      => $group_id,
                'proposal'      => $proposal,
                'final_defense' => $final_defense,
                'copyright'     => $copyright
            ]);
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Milestones updated successfully']);
        exit;
    }

    if ((!$document_id && !$upload_id) || !in_array($status, ['approved', 'rejected'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    if ($document_id) {
        $checkStmt = $con->prepare("SELECT status, document_type, original_filename FROM urec_documents WHERE id = :id");
        $checkStmt->execute(['id' => $document_id]);
        $docInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$docInfo) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Document not found']);
            exit;
        }

        if ($docInfo['status'] === 'approved') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'This document is already approved and cannot be modified']);
            exit;
        }

        $con->prepare("UPDATE urec_documents SET status = :status WHERE id = :id")
            ->execute(['status' => $status, 'id' => $document_id]);

        logActivity($con, $_SESSION['id'], $_SESSION['role'], $status === 'approved' ? 'approve' : 'reject',
            $_SESSION['name'] . " {$status} UREC document: " . ($docInfo['document_type'] ?? '') . " (" . ($docInfo['original_filename'] ?? '') . ")"
        );

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    }

    if ($upload_id) {
        $checkStmt = $con->prepare("SELECT status, task_name, original_filename FROM uploads WHERE upload_id = :id");
        $checkStmt->execute(['id' => $upload_id]);
        $uploadInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$uploadInfo) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Upload not found']);
            exit;
        }

        if ($uploadInfo['status'] === 'approved') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'This document is already approved and cannot be modified']);
            exit;
        }

        $con->prepare("UPDATE uploads SET status = :status WHERE upload_id = :id")
            ->execute(['status' => $status, 'id' => $upload_id]);

        logActivity($con, $_SESSION['id'], $_SESSION['role'], $status === 'approved' ? 'approve' : 'reject',
            $_SESSION['name'] . " {$status} upload: " . ($uploadInfo['task_name'] ?? '') . " (" . ($uploadInfo['original_filename'] ?? '') . ")"
        );

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    }

} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>