<?php
session_start();
include("../connect.php");
header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("SELECT setting_key, setting_value, setting_type, description FROM system_settings ORDER BY setting_key");
        echo json_encode(['success' => true, 'settings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $con->beginTransaction();

        $boolStmt = $con->query("SELECT setting_key FROM system_settings WHERE setting_type = 'boolean'");
        $booleanSettings = $boolStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($booleanSettings as $key) {
            $con->prepare("UPDATE system_settings SET setting_value = 'false', updated_by = :updated_by, updated_at = NOW() WHERE setting_key = :key")
                ->execute(['updated_by' => $user_id, 'key' => $key]);
        }

        $updatedSettings = [];

        foreach ($_POST as $key => $value) {
            $checkStmt = $con->prepare("SELECT setting_type FROM system_settings WHERE setting_key = :key");
            $checkStmt->execute(['key' => $key]);
            $setting = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($setting) {
                if ($setting['setting_type'] === 'boolean') {
                    $value = ($value === 'true' || $value === '1' || $value === 'on') ? 'true' : 'false';
                }
                $con->prepare("UPDATE system_settings SET setting_value = :value, updated_by = :updated_by, updated_at = NOW() WHERE setting_key = :key")
                    ->execute(['value' => $value, 'updated_by' => $user_id, 'key' => $key]);
                $updatedSettings[] = $key;
            }
        }

        $con->commit();

        $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
            VALUES (:user_id, :user_type, 'settings_update', :description, :ip_address)
        ")->execute([
            'user_id'     => $user_id,
            'user_type'   => $_SESSION['role'],
            'description' => 'Updated system settings: ' . implode(', ', $updatedSettings),
            'ip_address'  => $_SERVER['REMOTE_ADDR']
        ]);

        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (PDOException $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>