<?php
// standingsApi.php
require_once 'standingsDatabaseConnection.php';

function updateStandings($leagueCode, $leagueId) {

    $apiToken = "0c98b44563234432be112138964c7529";  // <--- put your API token here
    $season = 2025;

    $url = "https://api.football-data.org/v4/competitions/{$leagueCode}/standings";

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
        return "API error: $err";
    }

    $data = json_decode($response, true);

    if (!isset($data['standings'][0]['table'])) {
        return "No standings found in API response.";
    }

    $standings = $data['standings'][0]['table'];

    // SQL insert/update
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO standings (
            league_id, season, position, team_id, team_name, short_name, tla, crest,
            played, won, draw_matches, lost, points, goals_for, goals_against, goal_diff, last_updated
        )
        VALUES (
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
        $stmt->execute([
            ':league_id'     => $leagueId,
            ':season'        => $season,
            ':position'      => $team['position'],
            ':team_id'       => $team['team']['id'],
            ':team_name'     => $team['team']['name'],
            ':short_name'    => $team['team']['shortName'] ?? null,
            ':tla'           => $team['team']['tla'] ?? null,
            ':crest'         => $team['team']['crest'] ?? null,
            ':played'        => $team['playedGames'],
            ':won'           => $team['won'],
            ':draw_matches'  => $team['draw'],
            ':lost'          => $team['lost'],
            ':points'        => $team['points'],
            ':goals_for'     => $team['goalsFor'],
            ':goals_against' => $team['goalsAgainst'],
            ':goal_diff'     => $team['goalDifference']
        ]);
    }

    return "Standings successfully updated!";
}