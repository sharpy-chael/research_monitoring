<?php
session_start();
include("../connect.php");

if (!isset($_SESSION['school_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($current) || empty($new) || empty($confirm)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
    exit;
}

$stmt = $con->prepare("SELECT pass_word FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}

if (!password_verify($current, $row['pass_word'])) {
    echo json_encode(["status" => "error", "message" => "Incorrect current password."]);
    exit;
}


$number       = preg_match('@[0-9]@', $new);

if ( !$number|| strlen($new) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters."]);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match."]);
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$update = $con->prepare("UPDATE users SET pass_word = :password WHERE id = :id");
if ($update->execute(['password' => $hashed, 'id' => $_SESSION['id']])) {
    echo json_encode(["status" => "success", "message" => "Password successfully changed!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error updating password. Please try again."]);
}
?>