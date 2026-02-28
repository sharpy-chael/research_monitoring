<?php
session_start();
include("../connect.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $lastname   = $_POST['lastname'];
    $firstname  = $_POST['firstname'];
    $middlename = $_POST['middlename'] ?? '';
    $program    = $_POST['program'];
    $title      = $_POST['title'] ?? $_SESSION['research_title'];
    $email      = $_POST['email'];
    $address    = $_POST['address'];
    $gender     = $_POST['gender'];
    $school_id  = $_SESSION['school_id'];

    $fn = trim($firstname);
    $mn = trim($middlename);
    $ln = trim($lastname);
    $displayName = trim($fn . ($mn ? ' ' . $mn : '') . ($ln ? ' ' . $ln : ''));

    $updateImage = "";
    $params = [
        ':lastname'       => $lastname,
        ':firstname'      => $firstname,
        ':middlename'     => $middlename,
        ':program'        => $program,
        ':research_title' => $title,
        ':email'          => $email,
        ':address'        => $address,
        ':gender'         => $gender,
        ':school_id'      => $school_id
    ];

    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFilePath)) {
                $updateImage = ', images = :images';
                $params[':images'] = $fileName;
            }
        }
    }

    $sql = "
        UPDATE student 
        SET 
            lastname = :lastname,
            firstname = :firstname,
            middlename = :middlename,
            program = :program,
            research_title = :research_title,
            email = :email,
            address = :address,
            gender = :gender
            $updateImage
        WHERE school_id = :school_id
    ";

    $stmt = $con->prepare($sql);

    if ($stmt->execute($params)) {
        $_SESSION['lastname']       = $lastname;
        $_SESSION['firstname']      = $firstname;
        $_SESSION['middlename']     = $middlename;
        $_SESSION['name']           = $displayName;
        $_SESSION['program']        = $program;
        $_SESSION['research_title'] = $title;
        $_SESSION['email']          = $email;
        $_SESSION['address']        = $address;
        $_SESSION['gender']         = $gender;

        if (!empty($updateImage)) {
            $_SESSION['images'] = $fileName;
        }

        header("Location: ../student_profile.php");
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>