<?php
require_once 'dbConnections/security.php';
require_once 'dbConnections/almanacDatabaseConnection.php';
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);
//Fetch the user from the session if logged
$user = $_SESSION['user'] ?? null;

// Fetch all from league_setup to get teh name of the tables.

$stmt = $pdo->query("SELECT league_key, trophy_table, league_id, league_name 
    FROM league_setup
    ORDER BY league_name ASC"
    );
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Builds a keyed array of leagues using league_key as key (name of the tables)
$leagues = [];
foreach ($rows as $row) {
    $leagues[$row['league_key']] = [
        'name' => htmlspecialchars($row['league_name'], ENT_QUOTES, 'UTF-8'),
        'club_table' => $row['league_key'],     //table name
        'trophy_table' => $row['trophy_table'], //trophy table
        'league_id' => (int)$row['league_id']   //league ID
    ];
}

// Get GET parameters safely
$league_select = isset($_GET['League_select']) ? trim($_GET['League_select']) : null;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;

// Validate league if invaled redirect
if ($league_select) {
    if (!isset($leagues[$league_select]) || !preg_match('/^[a-zA-Z0-9_]+$/', $league_select)) {
        header("Location: /palmares.php");
        exit;
    }
}


// Initialise club/league variables
$clubs_list = [];
$selected_club = null;
$trophies_list = [];


if ($league_select) {
    $league = $leagues[$league_select];
    $club_table = $league['club_table'];       //table name for that league 
    $trophy_table = $league['trophy_table'];  //trophy table for that league
    $league_id = $league['league_id'];        //league ID for that league

    // Fetch all clubs using prepared statement (even though table name is trusted)
    $stmt = $pdo->prepare("SELECT club_id, club_name FROM `$club_table` ORDER BY club_name ASC");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $clubs_list[] = [
            'id' => (int)$row['club_id'],
            'name' => htmlspecialchars($row['club_name'], ENT_QUOTES, 'UTF-8'),
        ];
    }

    // Fetch selected club details
    if ($club_id) {
        $stmt = $pdo->prepare("SELECT * FROM `$club_table` WHERE club_id = ?");
        $stmt->execute([$club_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $selected_club = [
                'id' => (int)$row['club_id'],
                'name' => htmlspecialchars($row['club_name'], ENT_QUOTES, 'UTF-8'),
                'primary_color' => htmlspecialchars($row['primary_color'], ENT_QUOTES, 'UTF-8'),
                'secondary_color' => htmlspecialchars($row['secondary_color'], ENT_QUOTES, 'UTF-8'),
                'logo' => !empty($row['club_logo']) ? base64_encode($row['club_logo']) : null,
                'founded_year' => (int)$row['founded_year'],
                'stadium' => htmlspecialchars($row['stadium'], ENT_QUOTES, 'UTF-8'),
                'city' => htmlspecialchars($row['city'], ENT_QUOTES, 'UTF-8'),
            ];
        }

        // Fetch trophies for club selected 
       $query = "SELECT 
                  club_trophies.titles_won, 
                  club_trophies.last_won, 
                  trophies.trophy_name, 
                  trophies.trophy_type
              FROM $trophy_table AS club_trophies
              INNER JOIN trophies ON club_trophies.trophy_id = trophies.trophy_id
              WHERE club_trophies.club_id = ? 
                AND club_trophies.league_id = ?
                AND club_trophies.last_won IS NOT NULL 
              ORDER BY trophies.trophy_type, trophies.trophy_name";

        //get the number of titles and sanitise if club never won trophy dont show 
        $stmt = $pdo->prepare($query);
        $stmt->execute([$club_id, $league_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $trophies_list[] = [
                'trophy_name' => htmlspecialchars($row['trophy_name'], ENT_QUOTES, 'UTF-8'),
                'trophy_type' => htmlspecialchars($row['trophy_type'], ENT_QUOTES, 'UTF-8'),
                'titles_won' => (int)$row['titles_won'],
                'last_won' => htmlspecialchars($row['last_won'], ENT_QUOTES, 'UTF-8'),
            ];
        }
    }
}

// Render template
echo $twig->render('palmares.html.twig', [
    'user' => $user,
    'leagues' => $leagues,              //List of leagues 
    'league_select' => $league_select,  //Selected league
    'clubs_list' => $clubs_list,        //The list of clubs in the selected league 
    'selected_club' => $selected_club,  //Selected club
    'trophies_list' => $trophies_list,  //List of thropies 
]);