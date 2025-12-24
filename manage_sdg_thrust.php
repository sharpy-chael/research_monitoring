<?php
include('connect.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $type = $_POST['type'] ?? '';
            $name = trim($_POST['name'] ?? '');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Name is required']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("INSERT INTO un_sdgs (name) VALUES (:name) RETURNING id");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("INSERT INTO research_thrusts (name) VALUES (:name) RETURNING id");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $stmt->execute(['name' => $name]);
            $id = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'delete':
            $type = $_POST['type'] ?? '';
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("DELETE FROM un_sdgs WHERE id = :id");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("DELETE FROM research_thrusts WHERE id = :id");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_available':
            $type = $_POST['type'] ?? '';
            $groupId = intval($_POST['group_id'] ?? 0);

            if ($groupId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("
                    SELECT id, name 
                    FROM un_sdgs 
                    WHERE id NOT IN (
                        SELECT sdg_id FROM group_sdgs WHERE group_id = :group_id
                    )
                    ORDER BY name
                ");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("
                    SELECT id, name 
                    FROM research_thrusts 
                    WHERE id NOT IN (
                        SELECT thrust_id FROM group_thrusts WHERE group_id = :group_id
                    )
                    ORDER BY name
                ");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $stmt->execute(['group_id' => $groupId]);
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
                    ON CONFLICT (group_id, sdg_id) DO NOTHING
                ");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("
                    INSERT INTO group_thrusts (group_id, thrust_id) 
                    VALUES (:group_id, :item_id)
                    ON CONFLICT (group_id, thrust_id) DO NOTHING
                ");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            foreach ($ids as $itemId) {
                $stmt->execute([
                    'group_id' => $groupId,
                    'item_id' => intval($itemId)
                ]);
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
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>