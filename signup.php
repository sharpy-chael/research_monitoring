<?php
session_start();
include("connect.php"); 

$activePrograms = [];
try {
    $programsStmt = $con->query("SELECT code, name FROM programs WHERE is_active = TRUE ORDER BY code");
    $activePrograms = $programsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activePrograms = [
        ['code' => 'DIT', 'name' => 'Diploma in Information Technology'],
        ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology']
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $lastname   = trim($_POST['lastname']   ?? '');
    $firstname  = trim($_POST['firstname']  ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $school_id  = trim($_POST['school_id']  ?? '');
    $program    = $_POST['program'];
    $password   = $_POST['passw'];

    if (!$lastname || !$firstname) {
        $_SESSION['error_message'] = "Last name and first name are required.";
        header("Location: signup.php");
        exit();
    }

    try {
        $programStmt = $con->prepare("SELECT is_active FROM programs WHERE code = :program");
        $programStmt->execute(['program' => $program]);
        $programData = $programStmt->fetch(PDO::FETCH_ASSOC);

        if (!$programData || !$programData['is_active']) {
            $_SESSION['error_message'] = "This program is currently not accepting registrations. Please contact administrator.";
            header("Location: signup.php");
            exit();
        }
    } catch (PDOException $e) {
    }

    $number = preg_match('@[0-9]@', $password);
    $rules  = strlen($password) >= 8 && $number;

    if (!$rules) {
        $_SESSION['error_message'] = "The password should be valid";
        header("Location: signup.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $con->prepare("
        INSERT INTO student (lastname, firstname, middlename, school_id, program, pass_word)
        VALUES (:lastname, :firstname, :middlename, :school_id, :program, :password)
    ");

    try {
        $stmt->execute([
            'lastname'   => $lastname,
            'firstname'  => $firstname,
            'middlename' => $middlename,
            'school_id'  => $school_id,
            'program'    => $program,
            'password'   => $hashed_password
        ]);
        $_SESSION['success_message'] = "Account created successfully!";
        header("Location: signup.php");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23505) {
            $_SESSION['error_message'] = "School ID already exists!";
        } else {
            $_SESSION['error_message'] = "Error creating account: " . $e->getMessage();
        }
        header("Location: signup.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
    <div class="contain">
        <div class="signup-box">
            <div class="avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <h2>STUDENT SIGN UP</h2>
            <form action="" method="post">
                <div class="name-row">
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="lastname" placeholder="Last Name" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="firstname" placeholder="First Name" required>
                    </div>
                    <div class="input-group middle">
                        <input type="text" name="middlename" placeholder="M.I.">
                    </div>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-id-badge"></i></span>
                    <input type="text" name="school_id" placeholder="School ID" required>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-graduation-cap"></i></span>
                    <select name="program" required>
                        <option value="" disabled selected>Select your program</option>
                        <?php foreach ($activePrograms as $prog): ?>
                            <option value="<?= htmlspecialchars($prog['code']) ?>">
                                <?= htmlspecialchars($prog['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="passw" placeholder="Password" required>
                </div>
                <div class="submit-btn">
                    <input type="submit" name="submit" value="Sign Up">
                </div>
                <div class="a">
                    <p class="login-link">Go back to <a href="login.php">Log In</a></p>
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