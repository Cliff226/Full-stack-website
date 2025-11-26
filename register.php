<?php
require_once 'vendor/autoload.php';
require_once 'dbConnections/usersDatabaseConnection.php'; // Must set $pdo = new PDO(...)

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

// Initialize variables
$errors = [];
$status = '';
$username = '';
$surname = '';
$favorite_league = '';
$user = null;


// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Trim inputs
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $password = $_POST['passwordLogin'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $league = trim($_POST['league'] ?? '');

    // Validation
    if (!$name || !$surname || !$email || !$dateOfBirth || !$password || !$confirm_password || !$league) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } elseif ($dateOfBirth > date('Y-m-d', strtotime('-10 years'))) {
        $errors[] = "You must be at least 10 years old to register.";
    }


    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }

    // Insert new user
    if (empty($errors)) {

        // Hash password securely
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

            $_SESSION['user'] = $email;
            $_SESSION['name'] = $name;

            // Success — show success modal
            $status = 'success';
            $username = $name;

            // Create cookie for UI personalization (same as login)
            $userData = [
                'name' => $name,
                'surname' => $surname,
                'favorite_league' => $favorite_league
            ];

            setcookie(
                "userData",
                json_encode($userData),
                time() + 3600,  // 1 hour
                "/",            // available everywhere
                "",             // domain
                false,          // secure=false for localhost
                true            // HttpOnly
            );

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
    'status' => $status,               // success / error for modal
    'errors' => $errors,               // display register errors
    'username' => $username,     
    'surname' => $surname,           
    'favorite_league' => $favorite_league, 
    'context' => 'register' // for modal display
]);

//Close PDO connection
$pdo = null;

