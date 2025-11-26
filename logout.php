<?php

require_once 'vendor/autoload.php';
session_start(); // only once

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Clear the cookie
setcookie("userData", "", time() - 3600, "/", "", false, true); // HttpOnly = true

// Render login page
echo $twig->render('login.html.twig', [
    'user' => null,
    'current_page' => 'Login'
]);
//Close PDO connection
$pdo = null;


