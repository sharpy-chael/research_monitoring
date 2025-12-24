<!-- STUDENT LOG IN -->
<?php
session_start();
include("connect.php");

if (!empty($_POST['submit'])) {
    $_SESSION['submit'] = $_POST['submit'];
    $name = $_POST['name'];
    $school_id = $_POST['school_id'];
    $program = $_POST['program'];
    $password = $_POST['passw'];

    $stmt = $con->prepare("SELECT * FROM student WHERE school_id = :school_id AND name = :name AND program = :program");
    $stmt->execute(['school_id' => $school_id, 'name' => $name, 'program' => $program]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['pass_word'])) {
        // Check if account is active
        if (!$row['is_active']) {
            $_SESSION['error_message'] = "Your account has been deactivated. Contact administrator.";
            header("Location: login.php");
            exit;
        }
        
        $_SESSION['name'] = $row['name'];
        $_SESSION['id'] = $row['id'];
        $_SESSION['school_id'] = $row['school_id'];
        $_SESSION['program'] = $row['program'];
        $_SESSION['images'] = $row['images'];
        $_SESSION['role'] = 'student';

        if (!isset($_SESSION['from_portal']) || $_SESSION['from_portal'] !== true) {
            header('Location: portal.php');
            exit();
        }
        unset($_SESSION['from_portal']);
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Incorrect Info or User doesn't exist";
        header("Location: login.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <title>Student Log In</title>
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="wrapper">
        <a href="portal.php"><i class='bx  bxs-arrow-left-stroke'  ></i></a>
        <form method="post" action="#">
            <h1>Log In Form</h1>
            <div class="input-box">
                <label for="name">Student</label>
                <input type="text" name ="name" placeholder="Enter your name" required>
                <i class='bxr  bx-user'  ></i> 
            </div>
            <div class="input-box">
                <label for="school_id">Student ID</label>
                <input type="text" name ="school_id" placeholder="Enter your Student ID" required>
                <i class='bxrds  bx-user-id-card'></i>  
            </div>
            <div class="input-box">
                <label for="program">Program</label>
                <input type="text" name="program" placeholder="Enter your program" required>
                <i class='bxr  bx-book'  ></i> 
            </div>
            <div class="input-box">
                <label for="password">Password</label>
                <input type="password" name="passw" placeholder="Password" required>
                <i class='bxr  bx-lock'  ></i>
            </div>
            <div class="btn">
                <input type="submit" name="submit" value="Log In">
            </div>     
            <div class="a">  
                <p>Dont have an account yet? <a href="signup.php">Sign up</a></p>
            </div>
        </form> 
    </div>
    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer> 
    <script src="js/timeout.js"></script> 
</body>
</html>