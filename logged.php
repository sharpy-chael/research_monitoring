<?php
session_start();
include "connect.php";

if (!empty($_POST['submit'])) {
    $name = $_POST['name'];
    $password = $_POST['password'];

    $stmt = $con->prepare("SELECT * FROM users WHERE username = :name AND role = 'coordinator'");
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['pass_word'])) {
        if (!$row['is_active']) {
            $_SESSION['error_message'] = "Your account has been deactivated. Contact administrator.";
            header("Location: logged.php");
            exit;
        }

        $_SESSION['name'] = $row['username'];
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = 'coordinator';
        $_SESSION['submit'] = true;

        include('php/log_helper.php');
        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'login', $_SESSION['name'] . ' logged in');

        if (!isset($_SESSION['from_portal']) || $_SESSION['from_portal'] !== true) {
            header('Location: portal.php');
            exit();
        }
        unset($_SESSION['from_portal']);
        header("Location: coordinator.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Incorrect Info or User doesn't exist";
        header("Location: logged.php");
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
    <title>Coordinator Log In</title>
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="wraps">
        <a href="portal.php"><i class='bx bxs-arrow-left-stroke'></i></a>
        <form action="" method="post">
            <h1>Coordinator</h1>
            <div class="input">
                <label for="Administrator">Username</label>
                <input type="text" name="name" placeholder="Enter your Username" required>
                <i class='bxr bx-user'></i>
            </div>
            <div class="input">
                <label for="Password">Password</label>
                <input type="password" name="password" placeholder="Enter your Password" required>
                <i class='bxr bx-lock'></i>
            </div>
            <div class="btn">
                <input type="submit" name="submit" value="Log In">
            </div>
            <div class="a">
                <p>Welcome, <a href="#">Coordinator</a>.</p>
            </div>
        </form>
    </div>
    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer>
    <script src="js/timeout.js"></script>
</body>
</html>