<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'standingsDatabaseConnection.php';
require_once 'standingsApi.php';

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Get league code from URL
$leagueCode = $_GET['league'] ?? null;

if (!$leagueCode) {
    die("No league selected.");
}

// Get league ID and name
$stmt = $pdo->prepare("SELECT id, name, crest FROM leagues WHERE code = :code");
$stmt->execute(['code' => $leagueCode]);
$league = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$league) {
    die("Invalid league code.");
}

$leagueId = $league['id'];
$leagueName = $league['name'];
$leagueCrest = $league['crest'];


// Check team_matches table for existing data
$stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :league_id ORDER BY last_updated ASC");
$stmt->execute(['league_id' => $leagueId]);
$matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$needsUpdate = true; // Default: fetch from API
if (!empty($matchesData)) {
    // Check last_updated
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

//if data is older than one day update from API
if ($needsUpdate) {
    updateStandings($leagueCode, $leagueId);
}

// Fetch updated standings from DB
$sql = "
    SELECT * FROM standings
    WHERE league_id = :league_id
    ORDER BY position ASC
";
$stmt = $pdo->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . implode(", ", $pdo->errorInfo()));
}

$stmt->execute(['league_id' => $leagueId]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render template
echo $twig->render('standings.html.twig', [
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings'   => $standings,
    'user'        => $_SESSION['user'] ?? null,
]);