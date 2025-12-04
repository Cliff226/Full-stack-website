<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/standingsDatabaseConnection.php';// Used to load the database connection

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Fetch the club id and names from the DB

$stmt = $pdo->query('SELECT team_name, team_id FROM standings;');
//Store them in an array
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build array with the Clubs List
$clubs_list = [];
foreach ($clubs as $row) {
    $clubs_list[] = [
        'id' => $row['team_id'],                        //row with id 
        'name' => $row['team_name'],                    // raw for matching
        'name_safe' => e($row['team_name']),   // escaped for HTML output
        'name_lower' => strtolower($row['team_name'])  // for search
    ];
}

//Get and Sanitise User Input

$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Converts input to lowercase for case-insensitive search

$q = trim(strtolower($q ?? ''));


// Initialises the variable
$hint = "";


// Checks if the user actually typed something
if ($q !== "") {
    //loop throw all clubs in database
    foreach ($clubs_list as $club) {
        // Converts the club name to lowercase
        $name = strtolower($club['name']);

        // Checks if the user’s input $q appears anywhere in the club name.        
        if (str_contains($club['name_lower'], $q)) {
            // Adds the matching club to the $hint string and a comma is added in between 
            $hint .= ($hint === "" ? "" : ", ") . $club['name_safe'];
        }
    }
}


// If no matches, returns "no suggestion".
echo $hint === "" ? "no suggestion" : $hint;


// Close DB connection
$pdo = null;
