<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'standingsDatabaseConnection.php';
require_once 'standingsApi.php';

// Get league code from URL
$leagueCode = $_GET['league'] ?? null;

if (!$leagueCode) {
    die("No league selected.");
}

// Get league ID and name
$stmt = $pdo->prepare("SELECT id, name, crest FROM leagues WHERE code = :code");
$stmt->execute(['code' => $leagueCode]);
$league = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$league) {
    die("Invalid league code.");
}

$leagueId = $league['id'];
$leagueName = $league['name'];
$leagueCrest = $league['crest'];

// Update standings from standingsApi.php

updateStandings($leagueCode, $leagueId);

// Fetch updated standings from DB
$stmt2 = $pdo->prepare("
    SELECT * FROM standings
    WHERE league_id = :league_id
    ORDER BY position ASC
");
$stmt2->execute(['league_id' => $leagueId]);
$standings = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Render template
echo $twig->render('standings.html.twig', [
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'standings'   => $standings,
    'user'        => $_SESSION['user'] ?? null,
]);