<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();

// Twig setup with output escaping
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

// Check if user is logged in
$user = $_SESSION['name'] ?? false;
$email = $_SESSION['user'] ?? null;
$status = $_SESSION['status'] ?? '';
$context = $_SESSION['context'] ?? '';
unset($_SESSION['status'], $_SESSION['context']);

if (!$user || !$email) {
    $_SESSION['notLoggedIn'] = true; 
    header("Location: index.php");
    exit;
}

// Get selected league code from GET with filtering
$leagueCode = trim(filter_input(INPUT_GET, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
if (!$leagueCode) {
    die("No league selected.");
}

// Read leagues from cookie
if (isset($_COOKIE['leaguesData'])) {
    $leagues = json_decode($_COOKIE['leaguesData'], true);
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

// Fetch posts from database using prepared statement
$stmt = $pdo->prepare(" SELECT id, title, content, author_name, image_path, created_at, league_code
    FROM blog_posts
    WHERE league_code = :league_code
    ORDER BY created_at DESC
");
$stmt->execute([':league_code' => $leagueCode]);

$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $posts[] = $row; // Twig will escape output
}

// Render template
echo $twig->render('blogArticles.html.twig', [
    'user' => $user,
    'current_page' => 'blogArticles',
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'leagueCode'  => $leagueCode,
    'leagueId'    => $leagueId,
    'posts'       => $posts,
    'status'      => $status,
    'context'     => $context
]);

$pdo = null;
