<?php
require_once 'vendor/autoload.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$user = $_SESSION['user'] ?? false;

// If not logged in store flag THEN redirect
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: index.php");
    exit;
}
    
// Load login modal flag from session
$notLoggedIn = $_SESSION['notLoggedIn'] ?? false;
unset($_SESSION['notLoggedIn']); 

echo $twig->render('createPost.html.twig', [
    'user' => $user,
    'current_page' => 'createPost'
]);