<?php
session_start();
require_once 'vendor/autoload.php';

require_once 'standingsDatabaseConnection.php'; // DB with Standings

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);


// Check if search form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clubNameSearch'])) {

    $clubName = trim($_POST['clubNameSearch']);

    // Get team ID from Standings table
    $stmt = $pdo->prepare("SELECT team_id FROM standings WHERE team_name = :club LIMIT 1");
    $stmt->execute(['club' => $clubName]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        header( "Location: index.php");
        exit;   
    }

    $teamId = $team['team_id'];

    // Check team_matches table for existing data
    $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
    $stmt->execute(['team_id' => $teamId]);
    $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $needsUpdate = true; // Default: fetch from API
    if (!empty($matchesData)) {
        // Check last_updated
        $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_update FROM team_matches WHERE team_id = :team_id");
        $stmt->execute(['team_id' => $teamId]);
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

    // If data absent or stale, call API
    if ($needsUpdate) {
        // Make sure $teamId is available in liveScoreAPI.php
        include 'TeamMatchesAPI.php';

        // Reload the matches from DB after API update
        $stmt = $pdo->prepare("SELECT * FROM team_matches WHERE team_id = :team_id ORDER BY kickoff ASC");
        $stmt->execute(['team_id' => $teamId]);
        $matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
    }

    $groupedMatches = [];
        foreach ($matchesData as $row) {
        $country = e($row['country']);
        $competition = e($row['competition']);

        if (!isset($groupedMatches[$competition])) $groupedMatches[$competition] = [];

        $groupedMatches[$country][$competition][] = [
            'id' => e($row['match_id']),
            'competition' => ['name' => $competition],
            'area' => ['name' => $country],
            'homeTeam' => ['name' => e($row['home_team'])],
            'awayTeam' => ['name' => e($row['away_team'])],
            'score' => [
                'fullTime' => [
                    'home' => (int)$row['home_score'],
                    'away' => (int)$row['away_score']
                ]
            ],
            'minute' => htmlspecialchars_decode($row['minute']),
            'utcDate' => e($row['kickoff'])
        ];
}

// Render Twig template
    echo $twig->render('teamSearch.html.twig', [
        'groupedMatches' => $groupedMatches,
        'user' => $_SESSION['user'] ?? null,
        'current_page' => 'LiveScore'
]);
