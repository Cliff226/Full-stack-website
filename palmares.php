<?php
require_once 'dbConnections/security.php' ;

require_once 'vendor/autoload.php';
require_once 'dbConnections/almanacDatabaseConnection.php'; 

// Start PHP session to track logged-in user
session_start();

//Twig Setup 
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

//User Info
// Check if a user is logged in via session
$user = $_SESSION['user'] ?? null;

//Load All Leagues
$stored_leagues = []; // holds league data for PHP logic
$league_names = [];   // maps league key to human-readable name

$stmt = $pdo->query("SELECT league_key, trophy_table, league_id, league_name FROM league_setup ORDER BY league_name ASC");
$leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$leagues) die("No leagues found in league_setup table.");

// Prepare arrays for later use
foreach ($leagues as $row) {
    $stored_leagues[$row['league_key']] = [
        'club_table' => $row['league_key'], // name of the table holding clubs
        'trophy_table' => $row['trophy_table'], // table holding trophies
        'league_id' => $row['league_id']
    ];
    $league_names[$row['league_key']] = $row['league_name'];
}

//Handle GET parameters
$league_select = $_GET['League_select'] ?? null; // selected league
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null; // selected club (cast to int for safety)

//Initialise variables
$club_table = $trophy_table = $league_id = $league_name = null;
$clubs_list = $trophies_list = [];
$selected_club = null;

//Get selected league info
if ($league_select && isset($stored_leagues[$league_select])) {
    $league_info = $stored_leagues[$league_select];
    $club_table = $league_info['club_table'];
    $trophy_table = $league_info['trophy_table'];
    $league_id = $league_info['league_id'];
    $league_name = $league_names[$league_select];
}

//Fetch clubs for the selected league
if ($club_table) {
    $stmt = $pdo->query("SELECT club_id, club_name, primary_color, secondary_color, club_logo, founded_year, stadium, city 
                         FROM `$club_table` ORDER BY club_name ASC");
    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($clubs as $row) {
        $clubs_list[] = [
            'id' => (int)$row['club_id'], // type cast for safety
            'name' => $row['club_name'],
            'primary_color' => $row['primary_color'],
            'secondary_color' => $row['secondary_color'],
            'logo' => !empty($row['club_logo']) ? base64_encode($row['club_logo']) : null, // encode binary for HTML img
            'founded_year' => $row['founded_year'],
            'stadium' => $row['stadium'],
            'city' => $row['city'],
        ];
    }
}

//Fetch selected club details
if ($club_id && $club_table) {
    $stmt = $pdo->prepare("SELECT * FROM `$club_table` WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $selected_club = [
            'id' => (int)$row['club_id'],
            'name' => $row['club_name'],
            'primary_color' => $row['primary_color'],
            'secondary_color' => $row['secondary_color'],
            'logo' => !empty($row['club_logo']) ? base64_encode($row['club_logo']) : null,
            'founded_year' => $row['founded_year'],
            'stadium' => $row['stadium'],
            'city' => $row['city'],
        ];
    }
}

// Fetch trophies for selected club 
if ($club_id && $trophy_table && $league_id) {
    $query = "SELECT 
                  club_trophies.titles_won, 
                  club_trophies.last_won, 
                  trophies.trophy_name, 
                  trophies.trophy_type
              FROM $trophy_table AS club_trophies
              INNER JOIN trophies ON club_trophies.trophy_id = trophies.trophy_id
              WHERE club_trophies.club_id = ? 
                AND club_trophies.league_id = ?
              ORDER BY trophies.trophy_type, trophies.trophy_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$club_id, $league_id]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $trophies_list[] = [
            'trophy_name' => $row['trophy_name'],
            'trophy_type' => $row['trophy_type'],
            'titles_won' => $row['titles_won'],
            'last_won' => $row['last_won'],
        ];
    }
}

//  Render Twig template 
echo $twig->render('palmares.html.twig', [
    'user' => $user,
    'stored_leagues' => $stored_leagues,
    'league_names' => $league_names,
    'league_select' => $league_select,
    'clubs_list' => $clubs_list,
    'selected_club' => $selected_club,
    'trophies_list' => $trophies_list,
    'current_page' => 'Palmares'
]);

//Close PDO connection
$pdo = null;
