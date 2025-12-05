<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/almanacDatabaseConnection.php'; // Used to load the database connection

// API SETUP
$apiToken = "0c98b44563234432be112138964c7529";  // Football-data.org API token

$competitions = 'PL,PD,SA,BL1,FL1,CL,EL,ECL,WC,QCCF,QUFA,ESC'; // League codes to get

// API URL to fetch all matches for selected competitions
$url = "https://api.football-data.org/v4/matches?competitions=$competitions";

// Inisialise CURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-Auth-Token: $apiToken"],
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0
]);

$response = curl_exec($curl);   // Execute request
$err = curl_error($curl);       // Capture any errors
curl_close($curl);

// JSON Decode response

$data = json_decode($response, true);
$matches = $data['matches'] ?? [];

// Exit if no matches found
if (empty($matches)) {
    exit; // Nothing to insert
}

//Prepered sql queary 
//  Insert or update match data using ON DUPLICATE KEY for match_id
$sql = "INSERT INTO livematches (match_id, competition, country, home_team, away_team, home_team_crest, away_team_crest, home_score, away_score, minute, kickoff)
VALUES (:match_id, :competition, :country, :home_team, :away_team, :home_team_crest, :away_team_crest, :home_score, :away_score, :minute, :kickoff)
ON DUPLICATE KEY UPDATE
    competition = VALUES(competition),
    country = VALUES(country),
    home_team = VALUES(home_team),
    away_team = VALUES(away_team),
    home_team_crest = VALUES(home_team_crest),
    away_team_crest = VALUES(away_team_crest),
    home_score = VALUES(home_score),
    away_score = VALUES(away_score),
    minute = VALUES(minute),
    kickoff = VALUES(kickoff),
    last_updated = NOW()
";

$stmt = $pdo->prepare($sql);

//  Loop through the found matches

foreach ($matches as $match) {

    // Safely extract values 
    $matchId    = (int)($match['id'] ?? 0);
    $competition = htmlspecialchars($match['competition']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $country     = htmlspecialchars($match['area']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $home        = htmlspecialchars($match['homeTeam']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $away        = htmlspecialchars($match['awayTeam']['name'] ?? '', ENT_QUOTES, 'UTF-8');
    
    $homeCrest   = filter_var($match['homeTeam']['crest'] ?? '', FILTER_SANITIZE_URL);
    $awayCrest   = filter_var($match['awayTeam']['crest'] ?? '', FILTER_SANITIZE_URL);

    $homeScore   = (int)($match['score']['fullTime']['home'] ?? 0);
    $awayScore   = (int)($match['score']['fullTime']['away'] ?? 0);

    // Convert date to MY sql DATETIME
    $utcDate = $match['utcDate'] ?? null;
    $kickoff = $utcDate ? date('Y-m-d H:i:s', strtotime($utcDate)) : null;

    // Minute calculation
    if ($utcDate) {
        $elapsed = round((time() - strtotime($utcDate)) / 60);

        if     ($elapsed < 0)       $minute = "-";
        elseif ($elapsed <= 45)     $minute = $elapsed . "'";
        elseif ($elapsed <= 60)     $minute = "HT";
        elseif ($elapsed <= 105)    $minute = ($elapsed - 15) . "'";
        else                         $minute = "FT";
    } else {
        $minute = "-";// Unknown
    }


    // Execute secure insert
    $stmt->execute([
        ':match_id'       => $matchId,
        ':competition'    => $competition,
        ':country'        => $country,
        ':home_team'      => $home,
        ':away_team'      => $away,
        ':home_team_crest'=> $homeCrest,
        ':away_team_crest'=> $awayCrest,
        ':home_score'     => $homeScore,
        ':away_score'     => $awayScore,
        ':minute'         => $minute,
        ':kickoff'        => $kickoff
    ]);
}

