<?php
require_once 'dbConnections/standingsDatabaseConnection.php';

function updateStandings($leagueCode, $leagueId, $season = 2025) {

    global $pdo;

    // Ensure UTF-8 connection
    $pdo->exec("SET NAMES 'utf8mb4'");

    $apiToken = "0c98b44563234432be112138964c7529";
    $url = "https://api.football-data.org/v4/competitions/{$leagueCode}/standings";

    // Fetch data from API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-Auth-Token: $apiToken"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("Standings API error: $err");
        return;
    }

    $data = json_decode($response, true);
    if (!isset($data['standings'][0]['table'])) {
        error_log("No standings found in API response for league: $leagueCode");
        return;
    }

    $standings = $data['standings'][0]['table'];

    // Prepare SQL with ON DUPLICATE KEY UPDATE
    $stmt = $pdo->prepare("INSERT INTO standings 
        (  league_id, season, position, team_id, team_name, short_name, tla, crest,
            played, won, draw_matches, lost, points, goals_for, goals_against, goal_diff, last_updated
        ) VALUES (
            :league_id, :season, :position, :team_id, :team_name, :short_name, :tla, :crest,
            :played, :won, :draw_matches, :lost, :points, :goals_for, :goals_against, :goal_diff, NOW()
        )
        ON DUPLICATE KEY UPDATE
            position = VALUES(position),
            played = VALUES(played),
            won = VALUES(won),
            draw_matches = VALUES(draw_matches),
            lost = VALUES(lost),
            points = VALUES(points),
            goals_for = VALUES(goals_for),
            goals_against = VALUES(goals_against),
            goal_diff = VALUES(goal_diff),
            last_updated = NOW()
    ");

    foreach ($standings as $team) {

        // Sanitize & trim string values
        $teamName   = trim($team['team']['name']);
        $shortName  = isset($team['team']['shortName']) ? trim($team['team']['shortName']) : null;
        $tla        = isset($team['team']['tla']) ? trim($team['team']['tla']) : null;
        $crest      = isset($team['team']['crest']) ? trim($team['team']['crest']) : null;

        // Cast integers to be safe
        $position       = (int)$team['position'];
        $teamId         = (int)$team['team']['id'];
        $played         = (int)$team['playedGames'];
        $won            = (int)$team['won'];
        $drawMatches    = (int)$team['draw'];
        $lost           = (int)$team['lost'];
        $points         = (int)$team['points'];
        $goalsFor       = (int)$team['goalsFor'];
        $goalsAgainst   = (int)$team['goalsAgainst'];
        $goalDiff       = (int)$team['goalDifference'];

        if (!$stmt->execute([
            ':league_id'     => $leagueId,
            ':season'        => $season,
            ':position'      => $position,
            ':team_id'       => $teamId,
            ':team_name'     => $teamName,
            ':short_name'    => $shortName,
            ':tla'           => $tla,
            ':crest'         => $crest,
            ':played'        => $played,
            ':won'           => $won,
            ':draw_matches'  => $drawMatches,
            ':lost'          => $lost,
            ':points'        => $points,
            ':goals_for'     => $goalsFor,
            ':goals_against' => $goalsAgainst,
            ':goal_diff'     => $goalDiff
        ])) {
            error_log("Failed to insert/update standings for team: {$teamName}");
        }
    }
}
