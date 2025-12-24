<?php
session_start();
include("connect.php"); 

if (!isset($_SESSION['school_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$school_id = $_SESSION['school_id'];
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($current) || empty($new) || empty($confirm)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
    exit;
}

$stmt = $con->prepare("SELECT pass_word FROM student WHERE school_id = :school_id");
$stmt->execute(['school_id' => $school_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}

$db_password = $row['pass_word'];

if (!password_verify($current, $db_password)) {
    echo json_encode(["status" => "error", "message" => "Incorrect current password."]);
    exit;
}

$uppercase = preg_match('@[A-Z]@', $new);
$lowercase = preg_match('@[a-z]@', $new);
$number    = preg_match('@[0-9]@', $new);
$specialChars = preg_match('@[^\w]@', $new);

if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($new) < 8) {
    echo json_encode(["status" => "error", "message" =>
        "Password must be at least 8 characters long, contain an uppercase letter, lowercase letter, a number, and one special character."
    ]);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match."]);
    exit;
}

$hashed_new = password_hash($new, PASSWORD_DEFAULT);

$update = $con->prepare("UPDATE student SET pass_word = :new_pass WHERE school_id = :school_id");
if ($update->execute(['new_pass' => $hashed_new, 'school_id' => $school_id])) {
    echo json_encode(["status" => "success", "message" => "Password successfully changed!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error updating password. Please try again."]);
}
?>