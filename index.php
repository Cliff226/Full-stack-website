<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';

session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

// Read session flags (team not found + login)
$teamNotfound = $_SESSION['teamNotfound'] ?? false;
unset($_SESSION['teamNotfound']);

$notLoggedIn = $_SESSION['notLoggedIn'] ?? false;
unset($_SESSION['notLoggedIn']);

$userData = null;
$user = null;

if (isset($_COOKIE["userData"])) {
    $userData = json_decode($_COOKIE["userData"], true);
}
if(isset($_SESSION['user'])){
    $user =  $_SESSION['user'] ?? null;
}

echo $twig->render('index.html.twig', [
    'current_page' => 'Home',
    'user'          => $user,
    'teamNotfound' => $teamNotfound,
    'notLoggedIn' => $notLoggedIn,
    'userData'=> $userData

]);
