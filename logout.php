<?php

require_once 'vendor/autoload.php';
session_start(); // only once

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Render login page
echo $twig->render('login.html.twig', [
    'user' => null,
    'current_page' => 'Login'
]);
