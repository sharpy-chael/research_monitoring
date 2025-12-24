<?php
session_start();
include ('connect.php');

if (!isset($_SESSION['submit'])) {
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
<link href="https://cdn.boxicons.com/fonts/basic/boxicons.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/manage.css">

<title>Coordinator Dashboard</title>
</head>

<body>

<?php include("templates/aside_coordinator.html"); ?>

<!-- REQUIRED by home.css -->
<main class="main-content">

    <h1>Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']); ?>.</p>

    <!-- CONTENT WRAPPER (prevents overflow bugs) -->
    <div class="dashboard-wrapper">

        <!-- PROGRESS CARD -->
        <div class="card progress-card">
            <div class="card-head">
                <strong>OVERALL RESEARCH PROGRESS</strong>
                <span class="progress-percent" id="progress-text">0%</span>
            </div>

            <div class="progress-bar">
                <div
                    id="progress-bar-fill"
                    class="progress-bar-fill"
                    style="width:0%">
                </div>
            </div>
        </div>

        <!-- CHART -->
        <div class="chart-wrapper">
            <div id="root"></div>
            <!-- Update this to coordinator build file once you build it -->
            <script
                type="module"
                src="./react-app/dist/assets/coordinator-D-BFdTQT.js"
                defer>
            </script>
        </div>

    </div>
    <div style="height: 50px; " class="space"></div>
</main>

</body>
</html>