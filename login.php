<?php
require_once 'dbConnections/security.php';
require_once 'vendor/autoload.php';
require_once 'dbConnections/usersDatabaseConnection.php';

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Initialise variables
$errors = [];
$status = '';
$username = '';
$surname = '';
$favorite_league = '';
$user = null;

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['emailLogin'] ?? '');
    $password = $_POST['passwordLogin'] ?? '';
    $captcha_input = trim($_POST['captcha_input'] ?? '');

    // CAPTCHA validation
    if (!isset($_SESSION['captcha']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha'])) {
        $errors[] = "CAPTCHA is incorrect.";
        $status = 'error';
    }

    // Clear CAPTCHA
    unset($_SESSION['captcha']);

    // If no CAPTCHA errors
    if (empty($errors)) {

        if (!$email || !$password) {
            $errors[] = "Please enter both email and password.";
            $status = 'error';

        } else {

            $stmt = $pdo->prepare("
                SELECT name, surname, email, dob, password, favorite_league 
                FROM users 
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Validate password
            if ($user && password_verify($password, $user['password'])) {

                // Store session
                $_SESSION['user'] = $user['email'];
                $_SESSION['name'] = $user['name'];

                $status = 'success';
                $username = $user['name'];
                $surname = $user['surname'];
                $favorite_league = $user['favorite_league'];

                // Store cookie
                $userData = [
                    'name' => $username,
                    'surname' => $surname,
                    'favorite_league' => $favorite_league
                ];

                setcookie(
                    "userData",
                    json_encode($userData),
                    time() + 7200,
                    "/",
                    "",
                    false,
                    true
                );

            } else {
                $errors[] = "Invalid email or password.";
                $status = 'error';
            }
        }
    }
}

// Render Twig template
echo $twig->render('login.html.twig', [
    'status' => $status,
    'errors' => $errors,
    'username' => $username,
    'surname' => $surname,
    'favorite_league' => $favorite_league,
    'context' => 'login'
]);

$pdo = null; // Close DB
