<?php
require_once 'almanacDatabaseConnection.php';

$apiToken = '0c98b44563234432be112138964c7529';

$competitions = 'PL,PD,SA,BL1,FL1,CL,EL,ECL,WC,QCCF,QUFA,ESC'; 
$status = 'LIVE'; 
$date = date('Y-m-d'); 

$url = "https://api.football-data.org/v4/matches?competitions=$competitions&status=$status&dateFrom=$date&dateTo=$date";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-Auth-Token: $apiToken"],
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "<p>Error fetching live matches: $err</p>";
    return;
}

$data = json_decode($response, true);
$matches = $data['matches'] ?? [];

if (empty($matches)) return;

$sql = "
INSERT INTO livematches 
(match_id, competition, country, home_team, away_team, home_score, away_score, minute, kickoff)
VALUES (:match_id, :competition, :country, :home_team, :away_team, :home_score, :away_score, :minute, :kickoff)
ON DUPLICATE KEY UPDATE
    competition = VALUES(competition),
    country = VALUES(country),
    home_team = VALUES(home_team),
    away_team = VALUES(away_team),
    home_score = VALUES(home_score),
    away_score = VALUES(away_score),
    minute = VALUES(minute),
    kickoff = VALUES(kickoff),
    last_updated = NOW()
";

$stmt = $pdo->prepare($sql);

foreach ($matches as $match) {
    $matchId = (int)($match['id'] ?? 0);
    $competition = $match['competition']['name'] ?? '';
    $country = $match['area']['name'] ?? '';
    $home = $match['homeTeam']['name'] ?? '';
    $away = $match['awayTeam']['name'] ?? '';
    $homeScore = (int)($match['score']['fullTime']['home'] ?? 0);
    $awayScore = (int)($match['score']['fullTime']['away'] ?? 0);
    $utcDate = $match['utcDate'] ?? null;
    $kickoff = $utcDate ? date('Y-m-d H:i:s', strtotime($utcDate . ' +1 hour')) : null;

    // Minute calculation
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

    $stmt->execute([
        ':match_id' => $matchId,
        ':competition' => $competition,
        ':country' => $country,
        ':home_team' => $home,
        ':away_team' => $away,
        ':home_score' => $homeScore,
        ':away_score' => $awayScore,
        ':minute' => $minute,
        ':kickoff' => $kickoff
    ]);
}
