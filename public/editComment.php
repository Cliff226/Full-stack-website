<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'dbConnections/standingsDatabaseConnection.php';// Used to load the database connection
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

//inisalise varable
$error = [];

// Check logged-in user
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) die("Not authorised.");

// Get comment ID via POST
$commentId = filter_input(INPUT_POST, 'idComment', FILTER_VALIDATE_INT);
if (!$commentId) die("Invalid comment selected.");

// Fetch comment
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) die("Comment not found.");
if ($comment['author_email'] !== $userEmail) die("You cannot edit this comment.");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    if ($content === '') {
        $error = "Comment cannot be empty.";
    } else {
        $stmt = $pdo->prepare("UPDATE comments SET Comment_content = :content WHERE id = :id");
        $stmt->execute([
            ':content' => $content,
            ':id' => $commentId
        ]);

        header("Location: /profile.php");
        exit;
    }
}

// Render Twig template
echo $twig->render('editComment.html.twig', [
    'comment'     => $comment,
    'error'       => $error,
    'context'     => 'postCreated'

]);