<?php
session_start();
include("connect.php"); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $name = $_POST['name'];
    $password = $_POST['passw'];

    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    $rules = strlen($password) >= 8 && $uppercase && $lowercase && $number && $specialChars;

    if (!$rules) {
        $_SESSION['error_message'] = "The password should be valid";
        header("Location: signing.php");
        exit();
    }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $con->prepare("INSERT INTO advisor (name, pass_word) VALUES (:name, :password)");

    try {
        $stmt->execute([
            'name' => $name,
            'password' => $hashed_password
        ]);
        $_SESSION['success_message'] = "Account created successfully!";
        header("Location: signing.php");
        exit();
    } catch (PDOException $e) {

        $_SESSION['error_message'] = "Error creating account: " . $e->getMessage();
        header("Location: signing.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Sign Up</title>
    <link rel="stylesheet" href="css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?> 
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message"><?php echo $_SESSION['success_message'];?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <div class="containers">
        <div class="signup-box">
            <div class="avatar">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <h2>Advisor Sign Up</h2>
            <form action="" method="post">
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name ="name" placeholder="Enter your name">
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="passw" placeholder="Password" required>
                </div>
                <div class="submit-btn">
                    <input type="submit" name="submit" value="Create Account" required>
                </div>
                <div class="a">
                    <p class="login-link">Go back to <a href="loginn.php">Log In</a></p>
                </div>
                <p style="font-size: 10px;">Password should have at least 8 characters, a capital letter, a number, and a special character.</p>
            </form>
        </div>
    </div>
    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer>
    <script src="js/timeout.js"></script> 
</body>
</html>