<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';
require_once 'standingsApi.php';

session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

$matchesData = [];
$groupedMatches = [];

//set user if logged in 
if(isset($_SESSION['user'])){
    $user =  $_SESSION['user'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clubNameSearch'])) {

    // Sanitize input
    $clubName = htmlspecialchars(trim($_POST['clubNameSearch']), ENT_QUOTES, 'UTF-8');

    // Fetch team ID
    $stmt = $pdo->prepare("SELECT team_id FROM standings WHERE team_name = :club LIMIT 1");
    $stmt->execute(['club' => $clubName]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        $_SESSION['teamNotfound'] = true;
        header("Location: index.php");
        exit;
    }

    $teamId = (int)$team['team_id'];

    // Load existing matches
    $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
    $stmt->execute(['team_id' => $teamId]);
    $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $needsUpdate = true;

    if (!empty($matchesData)) {
        $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM team_matches WHERE id = :team_id");
        $stmt->execute(['team_id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['last_update'])) {
            $lastDate = new DateTime($row['last_update']);
            $now = new DateTime();
            if ($now->getTimestamp() - $lastDate->getTimestamp() < 3600) {
                $needsUpdate = false; // updated within 1 hour
            }
        }
    }

    // Update from API if needed
    if ($needsUpdate) {
        $teamIdForAPI = $teamId; // pass to API script
        include 'TeamMatchesAPI.php';

        // Reload matches
        $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
        $stmt->execute(['team_id' => $teamId]);
        $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update standings
        $country = $matchesData[0]['country'];
        $stmt = $pdo->prepare("SELECT * FROM leagues WHERE country = :country LIMIT 1");
        $stmt->execute(['country' => $country]);
        $leagueData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($leagueData) {
            $leagueCode = $leagueData['code'];
            $leagueId = $leagueData['id'];
            updateStandings($leagueCode, $leagueId);
        }
    }

    // Get league info
    $country = $matchesData[0]['country'];
    $stmt = $pdo->prepare("SELECT * FROM leagues WHERE country = :country LIMIT 1");
    $stmt->execute(['country' => $country]);
    $leagueData = $stmt->fetch(PDO::FETCH_ASSOC);

    $leagueName = htmlspecialchars($leagueData['name'], ENT_QUOTES, 'UTF-8');
    $leagueCrest = $leagueData['crest'];
    $leagueId = $leagueData['id'];

    // Get standings
    $stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :league_id ORDER BY position ASC");
    $stmt->execute(['league_id'=> $leagueId]);
    $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get team data
    $stmt = $pdo->prepare("SELECT * FROM standings WHERE team_id = :team_id");
    $stmt->execute(['team_id' => $teamId]);
    $teamData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Group matches by competition only
    foreach ($matchesData as $row) {
        $competition = $row['competition'] ?? '';
        if (!isset($groupedMatches[$competition])) {
            $groupedMatches[$competition] = [];
        }
        $groupedMatches[$competition][] = [
            'id' => $row['match_id'],
            'competition' => ['name' => $competition],
            'homeTeam' => ['name' => $row['home_team']],
            'homeTeamCrest' => $row['home_team_crest'],
            'awayTeam' => ['name' => $row['away_team']],
            'awayTeamCrest' => $row['away_team_crest'],
            'score' => [
                'fullTime' => [
                    'home' => (int)$row['home_score'],
                    'away' => (int)$row['away_score']
                ]
            ],
            'minute' => htmlspecialchars_decode($row['minute'] ?? '-'),
            'utcDate' => $row['kickoff']
        ];
    }
}

// Render page
echo $twig->render('teamSearch.html.twig', [
    'groupedMatches' => $groupedMatches,
    'user' => $user,
    'current_page' => 'LiveScore',
    'leagueName' => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings' => $standings,
    'teamData' => $teamData,
]);


//Close PDO connection
$pdo = null;
