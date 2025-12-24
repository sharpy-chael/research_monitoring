<?php
$host = "localhost";          
$port = "5432";               
$dbname = "research_monitoring"; 
$user = "postgres";          
$password = "pangitsiyulip";  

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $con = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);



} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
