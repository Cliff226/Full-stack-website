<?php
session_start();

require_once 'vendor/autoload.php';

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$user = $_SESSION['user'] ?? false;

// If not logged in → redirect
if (!$user) {
    $_SESSION['notLoggedIn'] = true;
    header("Location: index.php");
    exit;
}

// Get selected league code from GET
$leagueCode = trim($_GET['league'] ?? '');
if (!$leagueCode) {
    die("No league selected.");
}

// Read cookie
if (isset($_COOKIE['leaguesData'])) {
    $leagues = json_decode($_COOKIE['leaguesData'], true); // JSON → array
} else {
    die("No league data in cookie.");
}

// Check if the selected league exists in the cookie
if (isset($leagues[$leagueCode])) {
    $selectedLeague = $leagues[$leagueCode];
} else {
    die("League not found in cookie.");
}

// Extract league details
$leagueName  = $selectedLeague['name'];
$leagueCrest = $selectedLeague['crest'];
$leagueId    = $selectedLeague['id'];

echo $twig->render('blogArticles.html.twig', [
    'user' => $user,
    'current_page' => 'blogArticles',
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'leagueCode'  => $leagueCode,
    'leagueId'    => $leagueId
]);




