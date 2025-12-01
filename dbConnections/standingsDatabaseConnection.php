<?php

// PDO database connection
$host = "localhost";
$db   = "league_standings"; 
$user = "root";
$pass = "";
$charset = 'utf8mb4'; // better than utf8 for full Unicode support

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log detailed error but show a generic message 
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed.");
}

// Twig Helper Function for XSS-Safe Output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}