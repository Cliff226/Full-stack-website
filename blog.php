<?php
require_once 'dbConnections/security.php';
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // output escaping
]);

// Get and sanitize session user
$user = htmlspecialchars($_SESSION['user'] ?? '', ENT_QUOTES, 'UTF-8');

// If not logged in → store flag and redirect
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: index.php");
    exit;
}

// Load login modal flag from session
$notLoggedIn = !empty($_SESSION['notLoggedIn']);
unset($_SESSION['notLoggedIn']); 

// Fetch all leagues from database
$stmt = $pdo->prepare("SELECT id, name, code, country, crest FROM leagues");
$stmt->execute();
$leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$leagues) {
    die("No leagues found in leagues table.");
}

// Arrays to store data
$stored_leagues = [];
$leaguesForCookie = [];

foreach ($leagues as $row) {
    // Sanitize DB output
    $leagueName    = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
    $leagueCountry = htmlspecialchars($row['country'], ENT_QUOTES, 'UTF-8');
    $leagueCrest   = htmlspecialchars($row['crest'], ENT_QUOTES, 'UTF-8');
    $leagueCode    = htmlspecialchars($row['code'], ENT_QUOTES, 'UTF-8');

    $stored_leagues[$row['id']] = [
        'name'    => $leagueName,
        'code'    => $leagueCode,
        'country' => $leagueCountry,
        'crest'   => $leagueCrest
    ];

    $leaguesForCookie[$leagueCode] = [
        'id'      => $row['id'],
        'name'    => $leagueName,
        'country' => $leagueCountry,
        'crest'   => $leagueCrest,
        'code'    => $leagueCode
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

// Render the blog template
echo $twig->render('blog.html.twig', [
    'user'         => $user,
    'current_page' => 'blog',
    'notLoggedIn'  => $notLoggedIn,
    'leagues'      => $stored_leagues
]);

// Close PDO connection
$pdo = null;
