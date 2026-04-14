<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $name      = trim($_POST['name']      ?? '');
    $advisorId = trim($_POST['advisor_id'] ?? '');
    $password  = $_POST['passw'];

    if (empty($name) || empty($advisorId)) {
        $_SESSION['error_message'] = "Name and Advisor ID are required.";
        header("Location: signing.php");
        exit();
    }

    $checkStmt = $con->prepare("SELECT id FROM faculties WHERE email = :advisor_id");
    $checkStmt->execute(['advisor_id' => $advisorId]);
    if ($checkStmt->fetch()) {
        $_SESSION['error_message'] = "Advisor ID already exists. Please use a different ID.";
        header("Location: signing.php");
        exit();
    }

    $checkUser = $con->prepare("SELECT id FROM users WHERE username = :username");
    $checkUser->execute(['username' => $advisorId]);
    if ($checkUser->fetch()) {
        $_SESSION['error_message'] = "Advisor ID already exists. Please use a different ID.";
        header("Location: signing.php");
        exit();
    }

    $number = preg_match('@[0-9]@', $password);
    if (strlen($password) < 8 || !$number) {
        $_SESSION['error_message'] = "The password should be valid";
        header("Location: signing.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $con->beginTransaction();

        $userStmt = $con->prepare("
            INSERT INTO users (username, pass_word, role, is_active)
            VALUES (:username, :password, 'advisor', TRUE)
        ");
        $userStmt->execute([
            'username' => $advisorId,
            'password' => $hashed_password
        ]);
        $user_id = $con->lastInsertId();

        $facultyStmt = $con->prepare("
            INSERT INTO faculties (user_id, name)
            VALUES (:user_id, :name)
        ");
        $facultyStmt->execute([
            'user_id' => $user_id,
            'name'    => $name
        ]);

        $con->commit();
        $_SESSION['success_message'] = "Account created successfully!";
        header("Location: signing.php");
        exit();
    } catch (PDOException $e) {
        $con->rollBack();
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
        <div class="success-message"><?php echo $_SESSION['success_message']; ?></div>
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
                    <input type="text" name="name" placeholder="Enter your name" required>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-id-card"></i></span>
                    <input type="text" name="advisor_id" placeholder="Enter Advisor ID" required>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="passw" placeholder="Password" required>
                </div>
                <div class="submit-btn">
                    <input type="submit" name="submit" value="Create Account">
                </div>
                <div class="a">
                    <p class="login-link">Go back to <a href="loginn.php">Log In</a></p>
                </div>
                <p style="font-size: 10px;">Password should have at least 8 characters.</p>
            </form>
        </div>
    </div>
    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer>
    <script src="js/timeout.js"></script>
</body>
</html>