<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/usersDatabaseConnection.php';// Used to load the database connection
require_once "recaptcha.php";

session_start(); // Start new or resume existing session
// captcha key

$site_key = "6LcqeCEsAAAAAD_XttzOvnf6j-t2V4WHVaNxzB8r";      // Public Key

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
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

    // filter inputs 
    $email = filter_var(trim($_POST['emailLogin'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['passwordLogin'] ?? '');
    $token = $_POST['g-recaptcha-response'] ?? '';

    // verify email
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // CAPTCHA validation
   // Safe CAPTCHA access
    $token = $_POST['g-recaptcha-response'] ?? '';

    if (!$token || !verifyRecaptcha($token)) {
        $errors[] = 'reCAPTCHA failed. Try again.';
        $status = 'error';
    }


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
    'site_key'          => $site_key, 
    'status'            => $status,
    'errors'            => $errors,
    'username'          => $username,
    'surname'           => $surname,
    'favorite_league'   => $favorite_league,
    'context'           => 'login'
]);

$pdo = null; // Close DB
