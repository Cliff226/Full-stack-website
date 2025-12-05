<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Shown when the team searched does not exits 
$teamNotfound = filter_var($_SESSION['teamNotfound'] ?? false, FILTER_VALIDATE_BOOLEAN);
unset($_SESSION['teamNotfound']); // Prevent showing twice

//Shown when a user tries to access something without logging in
$notLoggedIn = filter_var($_SESSION['notLoggedIn'] ?? false, FILTER_VALIDATE_BOOLEAN);
unset($_SESSION['notLoggedIn']); // Prevent showing twice


//Initialising variables
$userData = null;
if (isset($_COOKIE['userData'])) {
    $data = json_decode(trim($_COOKIE['userData']), true);
    if (is_array($data)) {
        $userData = [
            'name' => htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'surname' => htmlspecialchars($data['surname'] ?? '', ENT_QUOTES, 'UTF-8'),
            'favorite_league' => htmlspecialchars($data['favorite_league'] ?? '', ENT_QUOTES, 'UTF-8'),
        ];
    }
} elseif (isset($_SESSION['name'])) {
    // Fallback to session if cookie not available yet
    $userData = ['name' => htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8')];
}

$user = null;
if (isset($_SESSION['user'])) {
    // Sanitise session string
    $user = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');
}

// Render page
echo $twig->render('index.html.twig', [
    'current_page' => 'Home', // Navigation highlighting
    'user'          => $user, // Logged-in user's session data
    'teamNotfound' => $teamNotfound,// Message if team not found
    'notLoggedIn' => $notLoggedIn, // Message for forbidden access
    'userData'=> $userData // information from cookie 

]);

