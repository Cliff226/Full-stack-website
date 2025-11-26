<?php
require_once 'dbConnections/standingsDatabaseConnection.php';

    $stmt = $pdo->query('SELECT team_name, team_id FROM standings;');
    $clubs = $stmt->fetchAll();
    foreach ($clubs as $row) {
        $clubs_list[] = [
            'id' => $row['team_id'],
            'name' => e($row['team_name'])
        ];
    }


// get the q parameter from URL

$q = $_REQUEST["q"];

$hint = "";

if ($q !== "") {
    $q = strtolower($q);
    $len = strlen($q);

    foreach ($clubs_list as $club) {
        $name = strtolower($club['name']);

        if (stristr($name, substr($q, 0, $len))) {
            if ($hint === "") {
                $hint = $club['name']; // original case
            } else {
                $hint .= ", " . $club['name'];
            }
        }
    }
}

echo $hint === "" ? "no suggestion" : $hint;
//Close PDO connection
$pdo = null;
