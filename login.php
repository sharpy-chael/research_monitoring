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
    $activePrograms = [
        ['code' => 'DIT',  'name' => 'Diploma in Information Technology'],
        ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology']
    ];
}

if (!empty($_POST['submit'])) {
    $_SESSION['submit'] = $_POST['submit'];
    $school_id = $_POST['school_id'];
    $program   = $_POST['program'];
    $password  = $_POST['passw'];

    // ── 1. Check program is active ───────────────────────────────────────────
    try {
        $programStmt = $con->prepare("SELECT is_active FROM programs WHERE code = :program");
        $programStmt->execute(['program' => $program]);
        $programData = $programStmt->fetch(PDO::FETCH_ASSOC);

        if (!$programData || !$programData['is_active']) {
            $_SESSION['error_message'] = "This program is currently inactive. Please contact the administrator.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        // If programs table doesn't exist, continue
    }

    // ── 2. Verify credentials ────────────────────────────────────────────────
    $stmt = $con->prepare("SELECT * FROM student WHERE school_id = :school_id AND program = :program");
    $stmt->execute(['school_id' => $school_id, 'program' => $program]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['pass_word'])) {

        // ── 3. Check account is active ───────────────────────────────────────
        if (!$row['is_active']) {
            $_SESSION['error_message'] = "Your account has been deactivated. Contact the administrator.";
            header("Location: login.php");
            exit;
        }

        // ── 4. Academic Year check ───────────────────────────────────────────
        try {
            $ayStmt = $con->prepare("
                SELECT year_start, year_end
                FROM academic_years
                WHERE is_active = true
                  AND CURRENT_DATE BETWEEN year_start AND year_end
                LIMIT 1
            ");
            $ayStmt->execute();
            $activeAY = $ayStmt->fetch(PDO::FETCH_ASSOC);

            if (!$activeAY) {
                $latestAY = $con->query("
                    SELECT year_start, year_end
                    FROM academic_years
                    ORDER BY year_end DESC
                    LIMIT 1
                ")->fetch(PDO::FETCH_ASSOC);

                $yearLabel = $latestAY
                    ? "Academic Year " . date('Y', strtotime($latestAY['year_start']))
                      . "–" . date('Y', strtotime($latestAY['year_end']))
                    : "the current academic year";

                $_SESSION['error_message'] = "The {$yearLabel} is over. Please contact the administrator.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            // If academic_years table doesn't exist, continue
        }
        // ── End Academic Year check ──────────────────────────────────────────

        $_SESSION['name']      = $row['name'];
        $_SESSION['id']        = $row['id'];
        $_SESSION['school_id'] = $row['school_id'];
        $_SESSION['program']   = $row['program'];
        $_SESSION['images']    = $row['images'];
        $_SESSION['role']      = 'student';

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
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="wrapper">
        <a href="portal.php"><i class='bx bxs-arrow-left-stroke'></i></a>
        <form method="post" action="#">
            <h1>Student</h1>
            <div class="input-box">
                <label for="school_id">Student ID</label>
                <input type="text" name="school_id" placeholder="Enter your Student ID" required>
                <i class='bxrds bx-user-id-card'></i>
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
                <i class='bxr bx-book'></i>
            </div>
            <div class="input-box">
                <label for="password">Password</label>
                <input type="password" name="passw" placeholder="Enter your Password" required>
                <i class='bxr bx-lock'></i>
            </div>
            <div class="btn">
                <input type="submit" name="submit" value="Log In">
            </div>
            <div class="a">
                <p>Don't have an account yet? <a href="signup.php">Sign up</a></p>
            </div>
        </form>
    </div>

    <footer class="footer">
        <p>© 2025 Research Monitoring System</p>
    </footer>
    <script src="js/timeout.js"></script>
</body>
</html>