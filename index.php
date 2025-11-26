<?php

require_once 'vendor/autoload.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

// Read session flags (team not found + login)
$teamNotfound = $_SESSION['teamNotfound'] ?? false;
unset($_SESSION['teamNotfound']);

$notLoggedIn = $_SESSION['notLoggedIn'] ?? false;
unset($_SESSION['notLoggedIn']);

$userData = null;

if (isset($_COOKIE["userData"])) {
    $userData = json_decode($_COOKIE["userData"], true);
}


echo $twig->render('index.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'Home',
    'teamNotfound' => $teamNotfound,
    'notLoggedIn' => $notLoggedIn,
    'userData'=> $userData

]);
