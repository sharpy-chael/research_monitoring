<!-- STUDENT LOG IN -->
<?php
session_start();
include("connect.php");

// Fetch active programs for the dropdown
$activePrograms = [];
try {
    $programsStmt = $con->query("SELECT code, name FROM programs WHERE is_active = TRUE ORDER BY code");
    $activePrograms = $programsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If programs table doesn't exist or error, use default values
    $activePrograms = [
        ['code' => 'DIT', 'name' => 'Diploma in Information Technology'],
        ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology']
    ];
}

if (!empty($_POST['submit'])) {
    $_SESSION['submit'] = $_POST['submit'];
    $name = $_POST['name'];
    $school_id = $_POST['school_id'];
    $program = $_POST['program'];
    $password = $_POST['passw'];

    // Check if the program is active
    try {
        $programStmt = $con->prepare("SELECT is_active FROM programs WHERE code = :program");
        $programStmt->execute(['program' => $program]);
        $programData = $programStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$programData || !$programData['is_active']) {
            $_SESSION['error_message'] = "This program is currently inactive. Please contact administrator.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        // If programs table doesn't exist, continue with login
    }

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

        include('php/log_helper.php');
        logActivity($con, $_SESSION['id'], $_SESSION['role'], 'login', $_SESSION['name'] . ' logged in');

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
    <style>
        /* Select dropdown styling to match existing inputs */
        .input-box select {
            width: 100%;
            height: 100%;
            background-color: transparent;
            border: none;
            outline: none;
            border: 1px solid rgb(17, 156, 144);
            border-radius: 40px;
            font-size: 17px;
            color: navy;
            padding: 9px 45px 9px 20px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            /* background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23001f1c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); */
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }
        
        .input-box select::placeholder {
            color: navy;
        }
        
        .input-box select:focus {
            outline: none;
            border-color: rgb(17, 156, 144);
        }
        
        .input-box select option {
            background-color: white;
            color: rgb(0, 43, 40);
            padding: 10px;
        }
        
        .input-box select option:disabled {
            color: navy;
        }
        
        /* Mobile responsive for select */
        @media (max-width: 480px) {
            .input-box select {
                font-size: 14px;
                padding: 15px 40px 15px 15px;
                border-radius: 30px;
                background-position: right 15px center;
                background-size: 18px;
            }
        }
    </style>
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
                <select name="program" required>
                    <option value="" disabled selected>Select your program</option>
                    <?php foreach ($activePrograms as $prog): ?>
                        <option value="<?= htmlspecialchars($prog['code']) ?>">
                            <?= htmlspecialchars($prog['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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