<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/standingsDatabaseConnection.php';// Used to load the database connection

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Check login
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorised.");
}

//inisalise varable
$error = [];

// Retrieve post ID from POST request
$postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$postId) {
    die("Invalid post selected.");
}

// Load the post from database using the numeric ID
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

// Prevent editing posts belonging to other users
if ($post['author_email'] !== $userEmail) {
    die("You cannot edit this post.");
}

// If form submitted, process update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {

    // Clean input
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Basic validation
    if ($title === '' || $content === '') {
        $error = "Title and content cannot be empty.";
    } else {

        // Update the post in the database
        $stmt = $pdo->prepare(" UPDATE blog_posts
            SET title = :title, content = :content
            WHERE id = :id
        ");
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':id' => $postId 
        ]);

        // Redirect back to profile after saving
        header("Location: /profile.php");
        exit;
    }
}

// Show edit form again
echo $twig->render('editPost.html.twig', [
    'post'  => $post,
    'error' => $error,
    'user'  => $user,
    'context'     => 'postCreated'
]);

// Close pdo connection
$pdo = null;