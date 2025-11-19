<?php
session_start();
require_once 'vendor/autoload.php';
include_once 'usersDatabaseConnection.php'; // must provide $conn = new PDO(...)

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$errors = [];
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dateOfBirth = $_POST['dateOfBirth'] ?? '';
    $password = $_POST['passwordLogin'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $league = $_POST['league'] ?? '';

    //Input velidation
    if (!$name || !$surname || !$email || !$dateOfBirth || !$password || !$confirm_password || !$league) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif ($dateOfBirth > date('Y-m-d', strtotime('-10 years'))) {
        $errors[] = "You must be at least 10 years old to register.";
    }

    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }

    // Insert new user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, surname, email, dob, password, favorite_league)
            VALUES (:name, :surname, :email, :dob, :password, :league)
        ");

        $success = $stmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'dob' => $dateOfBirth,
            'password' => $hashedPassword,
            'league' => $league
        ]);

        if ($success) {
            $_SESSION['user'] = $email;
            $_SESSION['name'] = $name;
            $_SESSION['league'] = $surname;
            $status = 'success';
        } else {
            $errors[] = "Database error — could not create user.";
            $status = 'error';
        }
    } else {
        $status = 'error';
    }
}

// Render Twig template
echo $twig->render('register.html.twig', [
    'status' => $status,
    'errors' => $errors,
    'context' => 'register'
]);