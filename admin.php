<?php
    include ("connect.php");
    session_start();
    if (!isset($_SESSION['submit'])){
        header('Location: home.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <title>Admin Page</title>
</head>
<body>
<?php include("templates/aside_admin.html"); ?>
<main class="main-content">
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>
</main>
</body>
</html>