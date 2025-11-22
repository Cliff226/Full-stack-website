<?php

require_once 'vendor/autoload.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$teamNotfound = $_SESSION['teamNotfound'] ?? false;
unset($_SESSION['teamNotfound']);

echo $twig->render('index.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'Home',
    'teamNotfound' => $teamNotfound,
    
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