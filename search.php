<?php
// Include security headers and functions
require_once 'dbConnections/security.php';

require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();


//  TWIG SETUP
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,         // Disable caching during development
    'autoescape' => 'html',   // Prevent XSS by escaping output by default
]);

// Fetch all clubs from database
$stmt = $pdo->query('SELECT team_name, team_id FROM standings;');
$clubs = $stmt->fetchAll();

// Build an array of sanitized club names
$clubs_list = [];
foreach ($clubs as $row) {
    $clubs_list[] = [
        'id' => $row['team_id'],
        'name' => e($row['team_name'])   // Escape output to prevent XSS
    ];
}


// USER INPUT SEARCH

// recive q securely from GET request filter sanitisetion
$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$q = trim(strtolower($q ?? ''));

// Default response
$hint = "";

// Compare search query with club names
if ($q !== "") {
    foreach ($clubs_list as $club) {
        $name = strtolower($club['name']);

        // If a match is found, append to hint list
        if (stristr($name, $q)) {
            $hint .= ($hint === "" ? "" : ", ") . $club['name'];
        }
    }
}

// Output results
echo $hint === "" ? "no suggestion" : $hint;

// Close DB connection
$pdo = null; 