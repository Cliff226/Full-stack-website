<?php

require_once 'vendor/autoload.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('News.html.twig', [
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'News'
]);