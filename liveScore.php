<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/almanacDatabaseConnection.php';

session_start();


// Twig Setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

//set user if logged in 
$user =  $_SESSION['user'] ?? null;


// Get selected league from GET
$selectedLeague = trim($_GET['league'] ?? '');


// Handle Refresh Button POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
    include 'livescoreAPI.php'; // Update matches from API

    // Redirect to same page to avoid form resubmission
    $url = "liveScore.php";
    if ($selectedLeague !== '') {
        $url .= "?league=" . urlencode($selectedLeague);
    }
    header("Location: $url");
    exit;
}


// Load all leagues for dropdown
$leagues = [];
$stmt = $pdo->query("SELECT DISTINCT competition FROM livematches ORDER BY competition ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $leagues[] = $row['competition']; // Keep raw data; Twig will escape output
}

// Optional: Validate GET league exists in DB
if ($selectedLeague !== '' && !in_array($selectedLeague, $leagues)) {
    $selectedLeague = '';
}

// Fetch matches
if ($selectedLeague !== '') {
    // Filter matches by selected league
    $stmt = $pdo->prepare(" SELECT match_id, competition, country, home_team, away_team,home_team_crest, 
        away_team_crest, home_score, away_score, minute, kickoff
        FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        AND competition = :competition
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
    $stmt->execute(['competition' => $selectedLeague]);
} else {
    // Fetch all leagues
    $stmt = $pdo->query(" SELECT match_id, competition, country, home_team, away_team, home_team_crest,
        away_team_crest, home_score, away_score, minute, kickoff
        FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
}

$matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Group matches by country & competition
$groupedMatches = [];

foreach ($matchesData as $row) {
    $country = $row['country'];
    $competition = $row['competition'];

    if (!isset($groupedMatches[$country])) $groupedMatches[$country] = [];
    if (!isset($groupedMatches[$country][$competition])) $groupedMatches[$country][$competition] = [];

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

// Render Twig template
echo $twig->render('liveScore.html.twig', [
    'groupedMatches' => $groupedMatches,
    'leagues' => $leagues,
    'selectedLeague' => $selectedLeague,
    'user' => $user,
    'current_page' => 'LiveScore'
]);

// Close PDO connection
$pdo = null;