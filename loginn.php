<!-- ADVISOR LOG IN -->
<?php
session_start();
include "connect.php";

if (!empty($_POST['submit'])) {
    $_SESSION['submit'] = $_POST['submit'];
    $name = $_POST['name'];
    $password = $_POST['password'];

    $stmt = $con->prepare("SELECT * FROM advisor WHERE name = :name");
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['pass_word'])) {
        // Check if account is active
        if (!$row['is_active']) {
            $_SESSION['error_message'] = "Your account has been deactivated. Contact administrator.";
            header("Location: loginn.php");
            exit;
        }
        
        $_SESSION['name'] = $row['name'];
        $_SESSION['id'] = $row['id'];
        $_SESSION['images'] = $row['images'];
        $_SESSION['role'] = 'advisor';

        if (!isset($_SESSION['from_portal']) || $_SESSION['from_portal'] !== true) {
            header('Location: portal.php');
            exit();
        }
        unset($_SESSION['from_portal']);
        header("Location: advisor.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Incorrect Info or User doesn't exist";
        header("Location: loginn.php");
        exit;
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
    <title>Advisor Log In</title>
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="wrap">
         <a href="portal.php"><i class='bx  bxs-arrow-left-stroke'  ></i></a>
            <form action="" method="post">
                <h1>Log In Form</h1>
                <div class="inputed">
                    <label for="Advisor">Advisor</label>
                    <input type="text" name="name" placeholder="Name">
                    <i class='bxr  bx-user'  ></i>
                </div>
                <div class="inputed">
                    <label for="password">Password</label>
                    <input type="password" name="password" placeholder="Password">
                    <i class='bxr  bx-lock'  ></i>
                </div>
                <div class="btn">
                    <input type="submit" name="submit" value="Log In">
                </div>
                <div class="a">
                    <p>Dont have an account yet? <a href="signing.php">Sign up</a></p>
                </div>     
            </form>
        </div>
    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer> 
    <script src="js/timeout.js"></script> 
</body>
</html>