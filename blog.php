<?php
require_once 'vendor/autoload.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$teamNotfound = $_SESSION['teamNotfound'] ?? false;
$user = $_SESSION['user'] ?? false;

    if (!$user) {
        header("Location: index.php");
        $_SESSION['notLogedIn'] = true;
        exit;
    }
else {
    $_SESSION['notLogedIn'] = false;
}


echo $twig->render('blog.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'blog',
    'teamNotfound' => $teamNotfound,
    
]);