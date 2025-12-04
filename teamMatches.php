<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/standingsDatabaseConnection.php';//Load teh datbase connection
require_once 'standingsApi.php';//load teh api if needed


session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);


//// Initialises the variables
$matchesData = [];
$groupedMatches = [];
$user = $_SESSION['user'] ?? null;

$leagueName = '';
$leagueCrest = '';
$leagueId = 0;
$standings = [];
$teamData = [];



//If foarm is submited

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clubNameSearch'])) {

    
    //Sanitise teh search input

    $clubName = trim($_POST['clubNameSearch'] ?? '');

    // Escape before using it anywhere (XSS protection)
    $clubNameEscaped = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');

    if ($clubName === '') {
        $_SESSION['teamNotfound'] = true;
        header("Location: index.php");
        exit;
    }


    //Get the team id 
    $stmt = $pdo->prepare("SELECT team_id FROM standings WHERE team_name = :club LIMIT 1");
    $stmt->execute(['club' => $clubNameEscaped]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        $_SESSION['teamNotfound'] = true;
        header("Location: index.php");
        exit;
    }

    $teamId = (int)$team['team_id']; // safe cast


    //Load streod matches

    $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
    $stmt->execute(['team_id' => $teamId]);
    $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $needsUpdate = true;



     //cheack teh last update

    if (!empty($matchesData)) {
        $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM team_matches WHERE team_id = :team_id");
        $stmt->execute(['team_id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['last_update'])) {
            $lastDate = new DateTime($row['last_update']);
            $now = new DateTime();

            // If less than 1 hour old → NO API CALL (prevents spam)
            if ($now->getTimestamp() - $lastDate->getTimestamp() < 3600) {
                $needsUpdate = false;
            }
        }
    }



     //udate from API

    if ($needsUpdate) {
        $teamIdForAPI = $teamId; 
        include 'teamMatchesAPI.php';

        // Reload data
        $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
        $stmt->execute(['team_id' => $teamId]);
        $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update standings based on country
        if (!empty($matchesData)) {
            $country = $matchesData[0]['country'];

            $stmt = $pdo->prepare("SELECT * FROM leagues WHERE country = :country LIMIT 1");
            $stmt->execute(['country' => $country]);
            $leagueData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($leagueData) {
                updateStandings($leagueData['code'], $leagueData['id']);
            }
        }
    }



    //Load league info

    if (!empty($matchesData)) {
        $country = $matchesData[0]['country'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM leagues WHERE country = :country LIMIT 1");
        $stmt->execute(['country' => $country]);
        $leagueData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($leagueData) {
            $leagueName = htmlspecialchars($leagueData['name'], ENT_QUOTES, 'UTF-8');
            $leagueCrest = $leagueData['crest'];
            $leagueId = (int)$leagueData['id'];
        }
    }



    //load standings 
    if ($leagueId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = :lid ORDER BY position ASC");
        $stmt->execute(['lid' => $leagueId]);
        $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    //Load team table row

    $stmt = $pdo->prepare("SELECT * FROM standings WHERE team_id = :team_id LIMIT 1");
    $stmt->execute(['team_id' => $teamId]);
    $teamData = $stmt->fetch(PDO::FETCH_ASSOC);


   //grouping  the matches by competion 
   
    foreach ($matchesData as $row) {
        $competition = htmlspecialchars($row['competition'] ?? '', ENT_QUOTES, 'UTF-8');

        if (!isset($groupedMatches[$competition])) {
            $groupedMatches[$competition] = [];
        }

        $groupedMatches[$competition][] = [
            'id' => $row['match_id'],
            'competition' => ['name' => $competition],
            'homeTeam' => ['name' => htmlspecialchars($row['home_team'], ENT_QUOTES, 'UTF-8')],
            'homeTeamCrest' => $row['home_team_crest'],
            'awayTeam' => ['name' => htmlspecialchars($row['away_team'], ENT_QUOTES, 'UTF-8')],
            'awayTeamCrest' => $row['away_team_crest'],
            'score' => [
                'fullTime' => [
                    'home' => (int)$row['home_score'],
                    'away' => (int)$row['away_score']
                ]
            ],
            'minute' => htmlspecialchars($row['minute'] ?? '-', ENT_QUOTES, 'UTF-8'),
            'utcDate' => $row['kickoff']
        ];
    }
}



// Render Twig template

echo $twig->render('teamSearch.html.twig', [
    'groupedMatches' => $groupedMatches,
    'user' => $user,
    'current_page' => 'LiveScore',
    'leagueName' => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings' => $standings,
    'teamData' => $teamData,
]);


// Close PDO connection

$pdo = null;
