<?php
/**
 * Helper functions to retrieve and use system settings
 */

function getSetting($con, $settingKey, $defaultValue = null) {
    try {
        $stmt = $con->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute(['key' => $settingKey]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['setting_value'];
        }
        
        return $defaultValue;
    } catch (PDOException $e) {
        return $defaultValue;
    }
}

function getSettingBool($con, $settingKey, $defaultValue = false) {
    $value = getSetting($con, $settingKey, $defaultValue ? 'true' : 'false');
    return strtolower(trim($value)) === 'true';
}

function getSettingInt($con, $settingKey, $defaultValue = 0) {
    $value = getSetting($con, $settingKey, $defaultValue);
    return (int)$value;
}
?>