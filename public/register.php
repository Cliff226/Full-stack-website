<?php
// Load required files
require_once 'dbConnections/security.php'; // Database connection security
require_once '../vendor/autoload.php';        // Composer autoload for Twig and other libraries
require_once 'dbConnections/usersDatabaseConnection.php'; // Database connection for users
require_once  "recaptcha.php";

session_start(); // Start new or resume existing session

// captcha keys
$site_key = "6LcqeCEsAAAAAD_XttzOvnf6j-t2V4WHVaNxzB8r";      // Public Key

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);


// Initialise variables
$errors = [];
$status = '';
$username = '';
$surname = '';
$favorite_league = '';
$maxDate = date('Y-m-d', strtotime('-10 years'));
$minDate = date('Y-m-d', strtotime('-110 years'));


// Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CAPTCHA validation
    // Safe CAPTCHA access
    $token = $_POST['g-recaptcha-response'] ?? '';

    if (!$token || !verifyRecaptcha($token)) {
        $errors[] = 'reCAPTCHA failed. Try again.';
        $status = 'error';
    }


    // Input filtration
    $name = htmlspecialchars(trim($_POST['name']));
    $surname = htmlspecialchars(trim($_POST['surname']));
    $favorite_league = htmlspecialchars(trim($_POST['league'] ?? ''));
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    
    // Email sanitization and validation
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Passwords (no sanitisation needed)
    $password = trim($_POST['passwordLogin'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate required fields
    if (!$name || !$surname || !$email || !$dateOfBirth || !$password || !$confirm_password || !$favorite_league) {
        $errors[] = "All fields are required.";
    }

    // Validate password match and length
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Validate age
    if ($dateOfBirth > $maxDate) {
        $errors[] = "You must be at least 10 years old to register.";
    }
    if ($dateOfBirth < $minDate) {
        $errors[] = "You cannot be more than 110 years old to register.";
    }

    if ($dateOfBirth > date('Y-m-d')) {
    $errors[] = "Date of birth cannot be in the future.";
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
    $errors[] = "Invalid date input";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }

    // Insert new user into database
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, dob, password, favorite_league)
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

            // Cookie for UI personalisation (HTTPS required)
            $userData = [
                'name' => $name,
                'surname' => $surname,
                'favorite_league' => $favorite_league
            ];
            setcookie(
                "userData",
                json_encode($userData),
                [
                    'expires'  => time() + 7200,   // 2 hours
                    'path'     => '/',
                    'domain'   => '',              // leave empty
                    'secure'   => true,            // only HTTPS
                    'httponly' => true,            // JS cannot access
                    'samesite' => 'Strict'         // CSRF protection
                ]
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
    'maxDate'           => $maxDate,
    'minDate'           => $minDate,
    'site_key'          => $site_key, 
    'status'            => $status,
    'errors'            => $errors,
    'username'          => $username,
    'surname'           => $surname,
    'favorite_league'   => $favorite_league,
    'context'           => 'register'
]);


// Close database connection
$pdo = null;
