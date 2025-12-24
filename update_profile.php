<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = $_POST['name'];
    $program = $_POST['program'];
    $title = $_POST['title']; // ✅ ADD THIS
    $school_id = $_SESSION['school_id'];

    $updateImage = "";
    $params = [
        ':name' => $name,
        ':program' => $program,
        ':research_title' => $title, // ✅ ADD THIS
        ':school_id' => $school_id
    ];

    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/";
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

    // ✅ UPDATE research_title
    $sql = "
        UPDATE student 
        SET 
            name = :name,
            program = :program,
            research_title = :research_title
            $updateImage
        WHERE school_id = :school_id
    ";

    $stmt = $con->prepare($sql);

    if ($stmt->execute($params)) {

        // ✅ Update session
        $_SESSION['name'] = $name;
        $_SESSION['program'] = $program;
        $_SESSION['research_title'] = $title;

        if (!empty($updateImage)) {
            $_SESSION['images'] = $fileName;
        }

        header("Location: student_profile.php");
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>
