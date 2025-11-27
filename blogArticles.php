<?php
session_start();

require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

//Check if user is logged in 
$user = $_SESSION['name'] ?? false; // Get username from session if exists
$email = $_SESSION['user'] ?? null; // Get email/username from session

if (!$user || !$email) {
    // If user is not logged in, redirect to login page
    $_SESSION['notLoggedIn'] = true; 
    header("Location: index.php");
    exit;
}

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

//Fetch posts from database using league_code ---
$stmt = $pdo->prepare(" SELECT id, title, content, author_name, image_path, created_at, league_code
    FROM blog_posts
    WHERE league_code = :league_code
    ORDER BY created_at DESC
");
$stmt->execute([':league_code' => $leagueCode]);
// Store all posts in an array
$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $posts[] = $row; // Add each post to the array
}

echo $twig->render('blogArticles.html.twig', [
    'user' => $user,
    'current_page' => 'blogArticles',
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'leagueCode'  => $leagueCode,
    'leagueId'    => $leagueId,
    'posts'       => $posts
]);
// Close PDO connection
$pdo = null;




