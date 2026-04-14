<?php
include('../connect.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_available':
            $type     = $_POST['type']     ?? '';
            $groupId  = intval($_POST['group_id']    ?? 0);
            $researchId = intval($_POST['research_id'] ?? 0);

            if ($groupId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if (!$researchId) {
                $resStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :id");
                $resStmt->execute(['id' => $groupId]);
                $resRow = $resStmt->fetch(PDO::FETCH_ASSOC);
                $researchId = $resRow['research_id'] ?? 0;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("
                    SELECT id, name FROM sdgs
                    WHERE id NOT IN (
                        SELECT sdg_id FROM thrusts_assignments
                        WHERE research_id = :research_id AND sdg_id IS NOT NULL
                    )
                    ORDER BY name
                ");
                $stmt->execute(['research_id' => $researchId]);
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("
                    SELECT id, name FROM thrusts
                    WHERE id NOT IN (
                        SELECT thrust_id FROM thrusts_assignments
                        WHERE research_id = :research_id AND thrust_id IS NOT NULL
                    )
                    ORDER BY name
                ");
                $stmt->execute(['research_id' => $researchId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $items]);
            break;

        case 'assign':
            $type       = $_POST['type']     ?? '';
            $groupId    = intval($_POST['group_id']    ?? 0);
            $researchId = intval($_POST['research_id'] ?? 0);
            $ids        = json_decode($_POST['ids'] ?? '[]', true);

            if ($groupId <= 0 || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            if (!$researchId) {
                $resStmt = $con->prepare("SELECT research_id FROM groups WHERE id = :id");
                $resStmt->execute(['id' => $groupId]);
                $resRow = $resStmt->fetch(PDO::FETCH_ASSOC);
                $researchId = $resRow['research_id'] ?? 0;
            }

            if (!$researchId) {
                echo json_encode(['success' => false, 'message' => 'Group has no linked research record']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("
                    INSERT INTO thrusts_assignments (research_id, sdg_id)
                    VALUES (:research_id, :item_id)
                    ON CONFLICT DO NOTHING
                ");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("
                    INSERT INTO thrusts_assignments (research_id, thrust_id)
                    VALUES (:research_id, :item_id)
                    ON CONFLICT DO NOTHING
                ");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            foreach ($ids as $itemId) {
                try {
                    $stmt->execute(['research_id' => $researchId, 'item_id' => intval($itemId)]);
                } catch (PDOException $e) {
                    if ($e->getCode() != 23505) throw $e;
                }
            }

            echo json_encode(['success' => true]);
            break;

        case 'remove_assignment':
            $type       = $_POST['type']        ?? '';
            $researchId = intval($_POST['group_id']  ?? 0);
            $itemId     = intval($_POST['item_id']   ?? 0);

            if ($researchId <= 0 || $itemId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            if ($type === 'sdg') {
                $stmt = $con->prepare("DELETE FROM thrusts_assignments WHERE research_id = :research_id AND sdg_id = :item_id");
            } elseif ($type === 'thrust') {
                $stmt = $con->prepare("DELETE FROM thrusts_assignments WHERE research_id = :research_id AND thrust_id = :item_id");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                exit;
            }

            $stmt->execute(['research_id' => $researchId, 'item_id' => $itemId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>