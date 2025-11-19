<?php
require_once 'vendor/autoload.php';
require_once 'almanacDatabaseConnection.php'; 
session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Load leagues dynamically
$stored_leagues = [];
$league_names = [];

$stmt = $pdo->query("SELECT league_key, trophy_table, league_id, league_name FROM league_setup ORDER BY league_name ASC");
$leagues = $stmt->fetchAll();

if (!$leagues) die("No leagues found in league_setup table.");

foreach ($leagues as $row) {
    $stored_leagues[$row['league_key']] = [
        'club_table' => $row['league_key'],
        'trophy_table' => $row['trophy_table'],
        'league_id' => $row['league_id']
    ];
    $league_names[$row['league_key']] = $row['league_name'];
}

// Handle GET parameters
$league_select = $_GET['League_select'] ?? null;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;

$club_table = $trophy_table = $league_id = $league_name = null;
$clubs_list = $trophies_list = [];
$selected_club = null;

// Get selected league info
if ($league_select && isset($stored_leagues[$league_select])) {
    $league_info = $stored_leagues[$league_select];
    $club_table = $league_info['club_table'];
    $trophy_table = $league_info['trophy_table'];
    $league_id = $league_info['league_id'];
    $league_name = $league_names[$league_select];
}

// Fetch all clubs
if ($club_table) {
    $stmt = $pdo->query("SELECT club_id, club_name, primary_color, secondary_color, club_logo, founded_year, stadium, city 
                         FROM `$club_table` ORDER BY club_name ASC");
    $clubs = $stmt->fetchAll();
    foreach ($clubs as $row) {
        $clubs_list[] = [
            'id' => $row['club_id'],
            'name' => e($row['club_name']),
            'primary_color' => e($row['primary_color']),
            'secondary_color' => e($row['secondary_color']),
            'logo' => !empty($row['club_logo']) ? base64_encode($row['club_logo']) : null,
            'founded_year' => e($row['founded_year']),
            'stadium' => e($row['stadium']),
            'city' => e($row['city']),
        ];
    }
}

// Fetch selected club
if ($club_id && $club_table) {
    $stmt = $pdo->prepare("SELECT * FROM `$club_table` WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $row = $stmt->fetch();
    if ($row) {
        $selected_club = [
            'id' => $row['club_id'],
            'name' => e($row['club_name']),
            'primary_color' => e($row['primary_color']),
            'secondary_color' => e($row['secondary_color']),
            'logo' => !empty($row['club_logo']) ? base64_encode($row['club_logo']) : null,
            'founded_year' => e($row['founded_year']),
            'stadium' => e($row['stadium']),
            'city' => e($row['city']),
        ];
    }
}

// Fetch trophies for selected club
if ($club_id && $trophy_table && $league_id) {
    $query =  " SELECT 
            club_trophies.titles_won, 
            club_trophies.last_won, 
            trophies.trophy_name, 
            trophies.trophy_type
        FROM $trophy_table AS club_trophies
        INNER JOIN trophies ON club_trophies.trophy_id = trophies.trophy_id
        WHERE club_trophies.club_id = ? 
          AND club_trophies.league_id = ?
        ORDER BY trophies.trophy_type, trophies.trophy_name
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$club_id, $league_id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $trophies_list[] = [
            'trophy_name' => e($row['trophy_name']),
            'trophy_type' => e($row['trophy_type']),
            'titles_won' => e($row['titles_won']),
            'last_won' => e($row['last_won']),
        ];
    }
}

// Render Twig template
echo $twig->render('palmares.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'stored_leagues' => $stored_leagues,
    'league_names' => $league_names,
    'league_select' => $league_select,
    'clubs_list' => $clubs_list,
    'selected_club' => $selected_club,
    'trophies_list' => $trophies_list,
    'current_page' => 'Palmares'
]);