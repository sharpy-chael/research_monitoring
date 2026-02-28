<?php
session_start();
include("../connect.php");
include('log_helper.php');

if (!isset($_SESSION['id'])) {
    die("Advisor not identified.");
}

$advisor_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name       = $_POST['name'] ?? '';
    $department = $_POST['department'] ?? '';
    $email      = $_POST['email'] ?? '';
    $address    = $_POST['address'] ?? '';
    $gender     = $_POST['gender'] ?? '';

    $updateImage = "";
    $params = [
        'name'       => $name,
        'department' => $department,
        'email'      => $email,
        'address'    => $address,
        'gender'     => $gender,
        'id'         => $advisor_id
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

    $sql = "UPDATE advisor SET name = :name, department = :department, email = :email, address = :address, gender = :gender $updateImage WHERE id = :id";
    $stmt = $con->prepare($sql);
    if ($stmt->execute($params)) {
        $_SESSION['name'] = $name;
        if (!empty($params['images'])) {
            $_SESSION['images'] = $params['images'];
        }
        header("Location: ../advisor_profile.php");
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>