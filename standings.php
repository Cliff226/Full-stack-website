<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';
require_once 'standingsApi.php';

session_start();


// Twig setup

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // always escape output by default for safety
]);


// Get logged-in user from session (for display)
$user = $_SESSION['user'] ?? null;


// Get selected league from GET
// Trim whitespace and allowing only /[^a-zA-Z0-9_-]/ to prevent injection
$leagueCode = trim($_GET['league'] ?? '');
$leagueCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $leagueCode);

if (!$leagueCode) {
    die("No league selected.");
}

// Fetch the league info from DB
$stmt = $pdo->prepare("SELECT id, name, crest FROM leagues WHERE code = :code");
$stmt->execute(['code' => $leagueCode]);
$league = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$league) {
    die("Invalid league code.");
}

$leagueId = $league['id'];
$leagueName = $league['name'];
$leagueCrest = $league['crest'];

// Check if standings data exists and is fresh
$stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM standings WHERE league_id = :league_id");
$stmt->execute(['league_id' => $leagueId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$lastUpdate = $row['last_update'] ?? null;

$needsUpdate = true;
if ($lastUpdate) {
    $lastDate = new DateTime($lastUpdate);
    $today = new DateTime();

    //If dat is more than 1 day old it needs refresh from api
    $diff = $today->diff($lastDate)->days;
    if ($diff < 1) {
        $needsUpdate = false;
    }
}

// Update standings from API if needed
if ($needsUpdate) {
    updateStandings($leagueCode, $leagueId); // function uses prepared statements internally
}


// Fetch updated standings
$stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :league_id ORDER BY position ASC");
$stmt->execute(['league_id' => $leagueId]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decode HTML entities for display
foreach ($standings as &$row) {
    $row['team_name'] = htmlspecialchars_decode($row['team_name']);
}


// Render Twig template
echo $twig->render('standings.html.twig', [
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings'   => $standings,
    'user'        => $user,
    'current_page' => 'Standings'
]);

// Close PDO connection
$pdo = null;
