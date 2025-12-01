<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/usersDatabaseConnection.php';

session_start(); // Required for both user sessions and CAPTCHA

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // output escaping
]);

$errors = [];
$status = '';
$username = '';
$surname = '';
$favorite_league = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and collect input
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $password = $_POST['passwordLogin'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $favorite_league = trim($_POST['league'] ?? '');
    $captcha_input = trim($_POST['captcha_input'] ?? '');

    // Validation

    // All fields required
    if (!$name || !$surname || !$email || !$dateOfBirth || !$password || !$confirm_password || !$favorite_league || !$captcha_input) {
        $errors[] = "All fields are required.";
    }

    // Email format check and filtering 
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password checks
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    //Password lenght 
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    $minAgeDate = date('Y-m-d', strtotime('-10 years'));  // minimum age 10 years ago
    $maxAgeDate = date('Y-m-d', strtotime('-110 years')); // maximum age 110 years ago

    if ($dateOfBirth > $minAgeDate) {
        $errors[] = "You must be at least 10 years old to register.";
    }

    if ($dateOfBirth < $maxAgeDate) {
        $errors[] = "You cannot be more than 110 years old to register.";
    }

    // Validate the CAPTCHA
    if (!isset($_SESSION['captcha']) || $captcha_input !== $_SESSION['captcha']) {
        $errors[] = "Incorrect CAPTCHA.";
    }

    // Unset the CAPTCHA after use to prevent it from being reused
    unset($_SESSION['captcha']);

    // Check the inputed email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }

    // Inserting new user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, surname, email, dob, password, favorite_league)
            VALUES (:name, :surname, :email, :dob, :password, :favorite_league)
        ");

        $success = $stmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'dob' => $dateOfBirth,
            'password' => $hashedPassword,
            'favorite_league' => $favorite_league
        ]);

        if ($success) {
            // Store safe info in session
            $_SESSION['user'] = $email;
            $_SESSION['name'] = $name;

            //Cookie for UI personalisation
            $userData = [
                'name' => $name,
                'surname' => $surname,
                'favorite_league' => $favorite_league
            ];
            setcookie(
                "userData",
                json_encode($userData),
                time() + 3600,
                "/",
                "",
                false,
                true
            );

            $status = 'success';
            $username = $name;
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
    'username' => $username,
    'surname' => $surname,
    'favorite_league' => $favorite_league,
    'context' => 'register'
]);

$pdo = null; // Close database connection
