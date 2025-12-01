<?php
require_once 'dbConnections/security.php';
require_once 'dbConnections/standingsDatabaseConnection.php';
require_once 'vendor/autoload.php';

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // ensures output is escaped
]);

// Check if user is logged in
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorized.");
}

// Validate comment ID as integer to prevent sql code inputting
$commentId = filter_input(INPUT_GET, 'idComment', FILTER_VALIDATE_INT);
if (!$commentId) die("No valid comment selected.");

// Fetch comment safely
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) die("Comment not found.");

// Check if the logged-in user owns the comment
if ($comment['author_email'] !== $userEmail) {
    die("You cannot edit this comment.");
}

// Initialise error variable
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and sanitize input
    $content = trim($_POST['content'] ?? '');
    $content = filter_var($content, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // prevents HTML injection

    if ($content === '') {
        $error = "Content cannot be empty.";
    } else {
        // Update comment in database safely using prepared statements
        $stmt = $pdo->prepare("
            UPDATE comments
            SET Comment_content = :content
            WHERE id = :id
        ");
        $stmt->execute([
            ':content' => $content,
            ':id' => $commentId
        ]);

        // Redirect after successful edit
        header("Location: profile.php");
        exit;
    }
}

// Render edit form
echo $twig->render('editcomment.html.twig', [
    'comment' => $comment,  // output escaped automatically by Twig
    'error' => $error,
    'user' => $userEmail
]);

// Close PDO connection
$pdo = null;