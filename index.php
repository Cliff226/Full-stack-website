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

echo $twig->render('index.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'Home',
    'teamNotfound' => $teamNotfound,
    'notLoggedIn' => $notLoggedIn
]);


// Setup Twig (Twing)
// $loader = new \Twig\Loader\FilesystemLoader('templates');
// $twig = new \Twig\Environment($loader);

// $text = null;

// // Handle form submission
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $text = htmlspecialchars($_POST['user_text'] ?? '');

// }

// // Render the template
// echo $twig->render('liveScore.html.twig', ['text' => $text]);