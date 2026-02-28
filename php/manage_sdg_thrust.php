<?php
include('../connect.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Get advisor_id for the current user
$advisorId = null;
if ($userRole === 'advisor') {
    $advisorStmt = $con->prepare("SELECT advisor_id FROM advisor WHERE id = :id");
    $advisorStmt->execute(['id' => $userId]);
    $advisorData = $advisorStmt->fetch(PDO::FETCH_ASSOC);
    $advisorId = $advisorData['advisor_id'] ?? null;
}

try {
    switch ($action) {
        case 'get_available':
            $type = $_POST['type'] ?? '';
            $groupId = intval($_POST['group_id'] ?? 0);

            if ($groupId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if ($type === 'sdg') {
                // FIXED: Show ALL SDGs to both advisors and coordinators
                $stmt = $con->prepare("
                    SELECT id, name 
                    FROM un_sdgs 
                    WHERE id NOT IN (
                        SELECT sdg_id FROM group_sdgs WHERE group_id = :group_id
                    )
                    ORDER BY name
                ");
                $stmt->execute(['group_id' => $groupId]);
            } elseif ($type === 'thrust') {
                // FIXED: Show ALL Research Thrusts to both advisors and coordinators
                $stmt = $con->prepare("
                    SELECT id, name 
                    FROM research_thrusts 
                    WHERE id NOT IN (
                        SELECT thrust_id FROM group_thrusts WHERE group_id = :group_id
                    )
                    ORDER BY name
                ");
                $stmt->execute(['group_id' => $groupId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $items]);
            break;

        case 'assign':
            $type = $_POST['type'] ?? '';
            $groupId = intval($_POST['group_id'] ?? 0);
            $ids = json_decode($_POST['ids'] ?? '[]', true);

            if ($groupId <= 0 || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("
                    INSERT INTO group_sdgs (group_id, sdg_id) 
                    VALUES (:group_id, :item_id)
                    ON CONFLICT DO NOTHING
                ");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("
                    INSERT INTO group_thrusts (group_id, thrust_id) 
                    VALUES (:group_id, :item_id)
                    ON CONFLICT DO NOTHING
                ");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            foreach ($ids as $itemId) {
                try {
                    $stmt->execute([
                        'group_id' => $groupId,
                        'item_id' => intval($itemId)
                    ]);
                } catch (PDOException $e) {
                    // Ignore duplicate key errors
                    if ($e->getCode() != 23505) throw $e;
                }
            }

            echo json_encode(['success' => true]);
            break;

        case 'remove_assignment':
            $type = $_POST['type'] ?? '';
            $groupId = intval($_POST['group_id'] ?? 0);
            $itemId = intval($_POST['item_id'] ?? 0);

            if ($groupId <= 0 || $itemId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("DELETE FROM group_sdgs WHERE group_id = :group_id AND sdg_id = :item_id");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("DELETE FROM group_thrusts WHERE group_id = :group_id AND thrust_id = :item_id");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $stmt->execute([
                'group_id' => $groupId,
                'item_id' => $itemId
            ]);

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>