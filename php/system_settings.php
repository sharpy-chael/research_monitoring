<?php
include("../connect.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['submit'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

// List settings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $con->query("
            SELECT setting_key, setting_value, setting_type, description
            FROM system_settings
            ORDER BY setting_key
        ");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'settings' => $settings]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $con->beginTransaction();
        
        // First, get all boolean settings to handle unchecked checkboxes
        $booleanSettings = [];
        $stmt = $con->query("SELECT setting_key FROM system_settings WHERE setting_type = 'boolean'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $booleanSettings[] = $row['setting_key'];
        }
        
        // Set all boolean settings to 'false' first (for unchecked checkboxes)
        foreach ($booleanSettings as $key) {
            $updateStmt = $con->prepare("
                UPDATE system_settings 
                SET setting_value = 'false', updated_by = :updated_by, updated_at = NOW()
                WHERE setting_key = :key
            ");
            $updateStmt->execute([
                'updated_by' => $user_id,
                'key' => $key
            ]);
        }
        
        $updatedSettings = [];
        
        // Now update with actual POST values
        foreach ($_POST as $key => $value) {
            // Check if setting exists
            $checkStmt = $con->prepare("SELECT setting_type FROM system_settings WHERE setting_key = :key");
            $checkStmt->execute(['key' => $key]);
            $setting = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($setting) {
                // Handle boolean values - they come as 'true' or 'false' strings
                if ($setting['setting_type'] === 'boolean') {
                    $value = ($value === 'true' || $value === '1' || $value === 'on') ? 'true' : 'false';
                }
                
                // Update setting
                $updateStmt = $con->prepare("
                    UPDATE system_settings 
                    SET setting_value = :value, updated_by = :updated_by, updated_at = NOW()
                    WHERE setting_key = :key
                ");
                $updateStmt->execute([
                    'value' => $value,
                    'updated_by' => $user_id,
                    'key' => $key
                ]);
                
                $updatedSettings[] = $key;
            }
        }
        
        $con->commit();
        
        // Log the action
        $logStmt = $con->prepare("
            INSERT INTO system_logs (user_id, user_type, action_type, description, ip_address)
            VALUES (:user_id, :user_type, 'settings_update', :description, :ip_address)
        ");
        $logStmt->execute([
            'user_id' => $user_id,
            'user_type' => $_SESSION['role'],
            'description' => 'Updated system settings: ' . implode(', ', $updatedSettings),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    } catch (PDOException $e) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>