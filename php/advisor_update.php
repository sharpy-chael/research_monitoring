<?php
session_start();
include("../connect.php");
include('log_helper.php');

if (!isset($_SESSION['id'])) {
    die("Advisor not identified.");
}

$advisorUserId = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name       = trim($_POST['name']       ?? '');
    $department = trim($_POST['department'] ?? '');
    $email      = trim($_POST['email']      ?? '');
    $address    = trim($_POST['address']    ?? '');
    $gender     = trim($_POST['gender']     ?? '');

    $updateImage = "";
    $params = [
        'name'       => $name,
        'department' => $department,
        'email'      => $email,
        'address'    => $address,
        'gender'     => $gender,
        'user_id'    => $advisorUserId
    ];

    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFilePath)) {
                $updateImage = ", images = :images";
                $params['images'] = $fileName;
            }
        }
    }

    $sql = "UPDATE faculties SET name = :name, department = :department, email = :email, address = :address, gender = :gender $updateImage WHERE user_id = :user_id";
    $stmt = $con->prepare($sql);

    if ($stmt->execute($params)) {
        $con->prepare("UPDATE users SET username = :name WHERE id = :user_id")
            ->execute(['name' => $name, 'user_id' => $advisorUserId]);

        $_SESSION['name'] = $name;
        if (!empty($params['images'])) {
            $_SESSION['images'] = $params['images'];
        }

        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'update_profile', $_SESSION['name'] . ' updated their profile');
        $_SESSION['flash_success'] = 'Personal Info Updated Successfully';
        header("Location: ../advisor_profile.php");
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>