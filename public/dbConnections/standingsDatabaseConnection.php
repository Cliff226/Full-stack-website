<?php

// PDO database connection
$host = "localhost";        // Database server 
$db   = "league_standings"; // Name of the database you want to connect to
$user = "root";             // Database username
$pass = "";                 // Database password
$charset = "utf8mb4"; 
// Character set for full Unicode support (handles emojis, special chars)

// Combining all the connection info into a single string
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Makes PDO throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch database rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements for security
];

// Create PDO Connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options); // Try to connect to the database
} catch (PDOException $e) {
    // If connection fails log the detailed error for the developer
    error_log("Database Connection Error: " . $e->getMessage());
    // Show a generic message to the user to not expose sensitive info
    die("Database connection failed.");
}

// Twig Helper Function for XSS-Safe Output
// This function is used to escapes special HTML characters to prevent XSS attacks
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}