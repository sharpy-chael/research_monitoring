<?php
    include("connect.php");
    session_start();
    $_SESSION['from_portal'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Monitoring System | Portal</title>
    
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="header">
        <img src="images/logo.png" alt="PUP Logo" class="logo">
        <h1>POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</h1>
    </header>

    <main class="main">
        <h2 class="system-title">Research Monitoring System</h2>
        <div class="portal-container">
            <div class="portal-card admin">
                <div class="card-header">
                    <img src="images/user.png" alt="Admin Icon" class="admin">
                    <p>Admin</p>
                </div>
                <div class="card-body">
                    <a href="log_in.php" class="btn btn-login">LOGIN</a>
                </div>
            </div>
            <div class="portal-card coordinator">
                <div class="card-header">
                    <img src="images/man.png" alt="Admin Icon" class="admin">
                    <p>Coordinator</p>
                </div>
                <div class="card-body">
                    <a href="logged.php" class="btn btn-login">LOGIN</a>
                </div>
            </div>
            <div class="portal-card adviser">
                <div class="card-header">
                    <img src="images/teacher (1).png" alt="Adviser Icon" class="advisor">
                    <p>Adviser</p>
                </div>
                <div class="card-body">
                    <a href="loginn.php" class="btn btn-login">LOGIN</a>
                </div>
            </div>
            <div class="portal-card student">
                <div class="card-header">
                    <img src="images/student.png" alt="Student Icon" class="student">
                    <p>Student</p>
                </div>
                <div class="card-body">
                    <a href="login.php" class="btn btn-login">LOGIN</a>
                </div>
            </div>

        </div>
    </main>

    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer>
</body>
</html>