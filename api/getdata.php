<?php

    $dbserver = "localhost";
    $username = "root";
    $dbpassword = "";
    $database = "research_monitoring";
    $con = mysqli_connect($dbserver,$username, $dbpassword, $database);
    $result = $con->query("SELECT name FROM student");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$con->close();

// Output as JSON
echo json_encode($data);
?>