<?php
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$user = $_SESSION['user'] ?? false;

// If not logged in store flag THEN redirect
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: index.php");
    exit;
}

  
// Load login modal flag from session
$notLoggedIn = $_SESSION['notLoggedIn'] ?? false;
unset($_SESSION['notLoggedIn']); 


// Get all leagues from the database
$stmt = $pdo->prepare("SELECT id, name, code, country, crest FROM leagues");
$stmt->execute(); // You forgot this!
$leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if we got results
if (!$leagues) {
    die("No leagues found in leagues table.");
}

// Arrays to store data
$stored_leagues = [];

// Loop through each league row
foreach ($leagues as $row) {
    $stored_leagues[$row['id']] = [
        'name' => $row['name'],
        'code' => $row['code'],
        'country' => $row['country'],
        'crest' => $row['crest']
    ];

     $leaguesForCookie[$row['code']] = [
        'id'      => $row['id'],
        'name'    => $row['name'],
        'country' => $row['country'],
        'crest'   => $row['crest'],
        'code'    => $row['code']
    ];

}

// Store leagues in a cookie (JSON)
setcookie(
    "leaguesData",
    json_encode($leaguesForCookie),
    time() + 7200, // 2 hours
    "/",
    "",
    false, // secure=false for localhost
    true   // HttpOnly
);

$pdo = null; // Close DB


// Render the blog template
echo $twig->render('blog.html.twig', [
    'user' => $user,
    'current_page' => 'blog',
    'notLoggedIn' => $notLoggedIn,
    'leagues' => $stored_leagues

]);
// Close PDO connection
$pdo = null;