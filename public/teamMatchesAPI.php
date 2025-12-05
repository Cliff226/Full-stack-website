<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

// Input filtering
// Ensure $teamId is a valid integer to prevent SQL injection
$teamId = filter_var($teamId ?? 0, FILTER_VALIDATE_INT);
if (!$teamId) die("Invalid teamId."); // Stop execution if invalid

require_once 'dbConnections/standingsDatabaseConnection.php'; // Database connection


// API setup

$apiToken = '0c98b44563234432be112138964c7529';

// Example API URL (fetch matches for a specific team)
$apiUrl = "https://api.football-data.org/v4/teams/$teamId/matches/";

// Initialize cURL request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-Auth-Token: $apiToken"],
    CURLOPT_SSL_VERIFYHOST => 0, // Set to 2 in production for SSL security
    CURLOPT_SSL_VERIFYPEER => 0  // Set to true in production
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// Check for cURL errors
if ($err) {
    echo "<p>Error fetching matches: " . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</p>";
    return;
}

if ($err) {
    echo "<p>Error fetching matches: " . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</p>";
    exit;
}

//decodde the api respones

$data = json_decode($response, true);

// Use null-coalescing operator for safety
$matches = $data['matches'] ?? [];

if (empty($matches)) {
    exit; // No matches found
}



//Prepare ther SQL queary

$sql = "INSERT INTO team_matches(
    match_id, team_id, competition, country,
    home_team, home_team_crest,
    away_team, away_team_crest,
    home_score, away_score,
    minute, kickoff, last_updated
) VALUES (
    :match_id, :team_id, :competition, :country,
    :home_team, :home_team_crest,
    :away_team, :away_team_crest,
    :home_score, :away_score,
    :minute, :kickoff, NOW()
)
ON DUPLICATE KEY UPDATE
    competition     = VALUES(competition),
    country         = VALUES(country),
    home_team       = VALUES(home_team),
    home_team_crest = VALUES(home_team_crest),
    away_team       = VALUES(away_team),
    away_team_crest = VALUES(away_team_crest),
    home_score      = VALUES(home_score),
    away_score      = VALUES(away_score),
    minute          = VALUES(minute),
    kickoff         = VALUES(kickoff),
    last_updated    = NOW()
";

$stmt = $pdo->prepare($sql);


//processing each match

foreach ($matches as $match) {

    // Always safe-cast numeric fields
    $matchId   = (int)($match['id'] ?? 0);
    $homeScore = (int)($match['score']['fullTime']['home'] ?? 0);
    $awayScore = (int)($match['score']['fullTime']['away'] ?? 0);

    // Escape all strings to prevent broken HTML / DB issues
    $competition = htmlspecialchars($match['competition']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $country     = htmlspecialchars($match['area']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $home        = htmlspecialchars($match['homeTeam']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $away        = htmlspecialchars($match['awayTeam']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $homeCrest   = htmlspecialchars($match['homeTeam']['crest'] ?? '', ENT_QUOTES, 'UTF-8');
    $awayCrest   = htmlspecialchars($match['awayTeam']['crest'] ?? '', ENT_QUOTES, 'UTF-8');

    // Convert kickoff time
    $utcDate = $match['utcDate'] ?? null;
    $kickoff = $utcDate ? date('Y-m-d H:i:s', strtotime($utcDate)) : null;

    // Calculate match minute safely
    if ($utcDate) {
        $elapsed = round((time() - strtotime($utcDate)) / 60);

        if ($elapsed < 0) $minute = "-";
        elseif ($elapsed <= 45) $minute = $elapsed . "'";
        elseif ($elapsed <= 60) $minute = "HT";
        elseif ($elapsed <= 105) $minute = ($elapsed - 15) . "'";
        else $minute = "FT";

    } else {
        $minute = "-";
    }

    // Save to database
    $stmt->execute([
        ':match_id'        => $matchId,
        ':team_id'         => $teamId,
        ':competition'     => $competition,
        ':country'         => $country,
        ':home_team_crest' => $homeCrest,
        ':home_team'       => $home,
        ':away_team_crest' => $awayCrest,
        ':away_team'       => $away,
        ':home_score'      => $homeScore,
        ':away_score'      => $awayScore,
        ':minute'          => $minute,
        ':kickoff'         => $kickoff
    ]);
}
?>