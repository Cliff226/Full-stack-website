<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';
require_once 'standingsApi.php';

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Get selected league from GET
$leagueCode = trim($_GET['league'] ?? '');

if (!$leagueCode) {
    die("No league selected.");
}

// Get league info
$stmt = $pdo->prepare("SELECT id, name, crest FROM leagues WHERE code = :code");
$stmt->execute(['code' => $leagueCode]);
$league = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$league) {
    die("Invalid league code.");
}

$leagueId = $league['id'];
$leagueName = $league['name'];
$leagueCrest = $league['crest'];

// Check standings table for existing data
$stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :league_id ORDER BY last_updated ASC");
$stmt->execute(['league_id' => $leagueId]);
$standingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine if update is needed
$needsUpdate = true;
if (!empty($standingsData)) {
    $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM standings WHERE league_id = :league_id");
    $stmt->execute(['league_id' => $leagueId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastUpdate = $row['last_update'] ?? null;

    if ($lastUpdate) {
        $lastDate = new DateTime($lastUpdate);
        $today = new DateTime();
        $diff = $today->diff($lastDate)->days;

        if ($diff < 1) {
            $needsUpdate = false; // Data is fresh
        }
    }
}

// Update from API if needed
if ($needsUpdate) {
    updateStandings($leagueCode, $leagueId);
}

// Fetch updated standings
$stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :league_id ORDER BY position ASC");
$stmt->execute(['league_id' => $leagueId]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Escape team names
foreach ($standings as &$row) {
    $row['team_name'] = htmlspecialchars_decode($row['team_name']);
}

// Render Twig template
echo $twig->render('standings.html.twig', [
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings'   => $standings,
    'user'        => $_SESSION['user'] ?? null,
    'current_page' => 'Standings'
]);