<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/usersDatabaseConnection.php';// Used to load the database connection

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: /index.php");
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

            $stmt = $pdo->prepare("SELECT name, surname, email, dob, password, favorite_league 
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
                    [
                        'expires'  => time() + 7200,
                        'path'     => '/',
                        'domain'   => '',        // usually leave empty
                        'secure'   => true,      // only send over HTTPS
                        'httponly' => true,      // JS can't read it
                        'samesite' => 'Strict'   // prevents CSRF
                    ]
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
