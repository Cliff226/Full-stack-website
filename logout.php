<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);
// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Clear the cookie
setcookie(
    "userData", 
    "",
    time() - 3600,
    "/", "", 
    false, 
    true); // HttpOnly = true

// Render login page
echo $twig->render('/index.html.twig', [
    'user' => null,
    'current_page' => 'Login'
]);
//Close PDO connection
$pdo = null;


