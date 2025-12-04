<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/almanacDatabaseConnection.php';// Used to load the database connection

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);
//set user if logged in 
$user =  $_SESSION['user'] ?? null;


// Get selected league from GET
$selectedLeague = trim($_GET['league'] ?? '');


// Handle Refresh Button POST
//When the refresh button is pressed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
    include 'livescoreAPI.php'; // Update matches from API

    // Redirect to same page to avoid form resubmission
    $url = "liveScore.php";
    if ($selectedLeague !== '') {
        $url .= "?league=" . urlencode($selectedLeague);
    }
    // Redirect and stop script execution
    header("Location: $url");
    exit;
}


// Load all leagues for dropdown
$leagues = [];
// Uses DISTINCT so each competition appears only once
$stmt = $pdo->query("SELECT DISTINCT competition FROM livematches ORDER BY competition ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $leagues[] = $row['competition']; // Keep raw data; Twig will escape output
}

// Validate GET league If ?league does not match any known league reset
if (!empty($selectedLeague) && !in_array($selectedLeague, $leagues)) {
    $selectedLeague = '';
}

// Fetch Matches From Database
if (!empty($selectedLeague)) {
    // If a league is selected filter matches by selected league
    $stmt = $pdo->prepare(" SELECT match_id, competition, country, home_team, away_team,home_team_crest, 
        away_team_crest, home_score, away_score, minute, kickoff
        FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        AND competition = :competition
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
    $stmt->execute(['competition' => $selectedLeague]);
} else {
    // If not fetch all leagues
    $stmt = $pdo->query(" SELECT match_id, competition, country, home_team, away_team, home_team_crest,
        away_team_crest, home_score, away_score, minute, kickoff
        FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
}

$matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);


//  Group Matches by Country then by Competition
// groupedMatches[country][competition][]

$groupedMatches = [];

if (!empty($matchesData) && is_array($matchesData)) {
    foreach ($matchesData as $row) {
        $country = $row['country'];
        $competition = $row['competition'];
        
         // Initialise group structure 
        if (!isset($groupedMatches[$country])) $groupedMatches[$country] = [];
        if (!isset($groupedMatches[$country][$competition])) $groupedMatches[$country][$competition] = [];

        // Build the match structure for the Twig template
        $groupedMatches[$country][$competition][] = [
            'id' => (int)$row['match_id'],
            'competition' => ['name' => $competition],
            'area' => ['name' => $country],
            'homeTeam' => ['name' => $row['home_team']],
            'awayTeam' => ['name' => $row['away_team']],
            'homeTeamCrest' => ['crest' => $row['home_team_crest']],
            'awayTeamCrest' => ['crest' => $row['away_team_crest']],
            'score' => [
                'fullTime' => [
                    'home' => (int)$row['home_score'],
                    'away' => (int)$row['away_score'],
                ]
            ],
            'minute' => $row['minute'],
            'utcDate' => $row['kickoff'],
        ];
    }
}

// Render Twig template
echo $twig->render('/liveScore.html.twig', [
    'groupedMatches' => $groupedMatches,
    'leagues' => $leagues,
    'selectedLeague' => $selectedLeague,
    'user' => $user,
    'current_page' => 'LiveScore'
]);

// Close PDO connection
$pdo = null;