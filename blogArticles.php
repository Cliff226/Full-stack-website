<?php
session_start();

require_once 'vendor/autoload.php';
require_once 'standingsDatabaseConnection.php'; // PDO connection
require_once 'standingsApi.php'; // API update function

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$user = $_SESSION['user'] ?? false;

// If not logged in store flag THEN redirect
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: index.php");
    exit;
}

// Get selected league from GET
$leagueCode = trim($_GET['league'] ?? '');
if (!$leagueCode) {
    die("No league selected.");
}

    $stmt = $pdo->prepare("SELECT * FROM leagues WHERE code = :code");
    $stmt->execute(['code' => $leagueCode]);
    $leagueData = $stmt->fetch(PDO::FETCH_ASSOC);

    $leagueId = $leagueData['id'];
    $leagueName = $leagueData['name'];
    $leagueCrest = $leagueData['crest'];

echo $twig->render('blogArticles.html.twig', [
    'user' => $user,
    'current_page' => 'blogArticles',
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest

]);



