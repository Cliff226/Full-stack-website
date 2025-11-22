<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'standingsDatabaseConnection.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$matchesData = [];
$groupedMatches = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clubNameSearch'])) {

    $clubName = trim($_POST['clubNameSearch']);

    // Fetch the team ID
    $stmt = $pdo->prepare("SELECT team_id FROM standings WHERE team_name = :club LIMIT 1");
    $stmt->execute(['club' => $clubName]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        header("Location: index.php");
        $_SESSION['teamNotfound'] = true;
        exit;
    }

    $teamId = $team['team_id'];

    // Load existing matches
    $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
    $stmt->execute(['team_id' => $teamId]);
    $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $needsUpdate = true;

    if (!empty($matchesData)) {
        $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM team_matches WHERE team_id = :team_id");
        $stmt->execute(['team_id' => $teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['last_update']) {
            $lastDate = new DateTime($row['last_update']);
            $today = new DateTime();
            if ($today->diff($lastDate)->days < 1) {
                $needsUpdate = false; 
            }
        }
    }

    // API refresh if needed
    if ($needsUpdate) {
        include 'TeamMatchesAPI.php';

        $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
        $stmt->execute(['team_id' => $teamId]);
        $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Build grouped array
    foreach ($matchesData as $row) {

        $competition = $row['competition'];
        $country = $row['country'];

        if (!isset($groupedMatches[$country])) {
            $groupedMatches[$country] = [];
        }
        if (!isset($groupedMatches[$country][$competition])) {
            $groupedMatches[$country][$competition] = [];
        }

        $groupedMatches[$country][$competition][] = [
            'id' => $row['match_id'],
            'competition' => ['name' => $competition],
            'area' => ['name' => $country],
            'homeTeam' => [
                'name' => $row['home_team']
            ],
            'homeTeamCrest' => $row['home_team_crest'],
            'awayTeam' => [
                'name' => $row['away_team']
            ],
            'awayTeamCrest' => $row['away_team_crest'],
            'score' => [
                'fullTime' => [
                    'home' => (int)$row['home_score'],
                    'away' => (int)$row['away_score']
                ]
            ],
            'minute' => htmlspecialchars_decode($row['minute']),
            'utcDate' => $row['kickoff']
        ];
    }

}

// Render page
echo $twig->render('teamSearch.html.twig', [
    'groupedMatches' => $groupedMatches,
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'LiveScore',
    'teamNotfound' => $teamNotfound
]);