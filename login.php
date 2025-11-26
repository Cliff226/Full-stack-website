<?php
require_once 'vendor/autoload.php';
require_once 'dbConnections/usersDatabaseConnection.php'; // Must set $pdo = new PDO(...)

session_start();

// Redirect if already logged in

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);


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

    if (!$email || !$password) {
        $errors[] = "Please enter both email and password.";
        $status = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT name, surname, email, dob, password, favorite_league FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // password_ve
        if ($user && password_verify($password, $user['password'])) {

            // Login success — save session
            $_SESSION['user'] = $user['email'];
            $_SESSION['name'] = $user['name'];

            $status = 'success';
            $username = $user['name'] ?? '';
            $surname = $user['surname'] ?? '';
            $favorite_league = $user['favorite_league'] ?? '';


            // Secure cookie
            $userData = [
                'name' => $username,
                'surname' => $surname,
                'favorite_league' => $favorite_league 
            ];
            setcookie(
                "userData",
                json_encode($userData),
                time() + 7200 ,  // 2 hours 
                "/",            // available site-wide
                "",             // default domain canh be used on all subdomains
                false,          // secure=false for HTTP, true for HTTPS only 
                true            // HttpOnly is true to prevent JS access
            );

        } else {
            $errors[] = "Invalid email or password.";
            $status = 'error';
        }
    }
}

// Render Twig template

echo $twig->render('login.html.twig', [
    'status' => $status,               // success / error for modal
    'errors' => $errors,               // display login errors
    'username' => $username,     
    'surname' => $surname,           
    'favorite_league' => $favorite_league, 
    'context' => 'login' // for modal display
]);

//Close PDO connection
$pdo = null;