<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Shown when the team searched does not exits 
$teamNotfound = $_SESSION['teamNotfound'] ?? false;
unset($_SESSION['teamNotfound']); // uset session so the message doesnt show twice 

//Shown when a user tries to access something without logging in
$notLoggedIn = $_SESSION['notLoggedIn'] ?? false;
unset($_SESSION['notLoggedIn']);// uset session so the message doesnt show twice 

//Initialising variables
$userData = null;
$user = null;

// Convert the JSON string back into a PHP array
if (isset($_COOKIE["userData"])) {
    $userData = json_decode($_COOKIE["userData"], true);
}
// Stores user full account array if login
if(isset($_SESSION['user'])){
    $user =  $_SESSION['user'] ?? null;
}

// Render page
echo $twig->render('index.html.twig', [
    'current_page' => 'Home', // Navigation highlighting
    'user'          => $user, // Logged-in user's session data
    'teamNotfound' => $teamNotfound,// Message if team not found
    'notLoggedIn' => $notLoggedIn, // Message for forbidden access
    'userData'=> $userData // information from cookie 

]);

