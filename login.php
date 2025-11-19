<?php
require_once 'vendor/autoload.php';
session_start();
include_once 'usersDatabaseConnection.php'; // contains $pdo

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$errors = [];
$status = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['emailLogin'] ?? '';
    $password = $_POST['passwordLogin'] ?? '';

    // Prepare query with PDO
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Save session
        $_SESSION['user'] = $user['email'];
        $_SESSION['name'] = $user['name'];

        $status = 'success';

    } else {
        $errors[] = 'Invalid email or password.';
        $status = 'error';
    }
}

// Render Twig template
echo $twig->render('login.html.twig', [
    'status' => $status,
    'errors' => $errors,
    'username' => $user['name'] ?? null,
    'context' => 'login',
    'favorite_league' => $user['favorite_league'] ?? null,
]);