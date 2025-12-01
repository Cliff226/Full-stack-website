<?php
require_once 'dbConnections/security.php' ;
require_once 'dbConnections/standingsDatabaseConnection.php';
require_once 'vendor/autoload.php';

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorized.");
}

$postId = $_GET['id'] ?? null;
if (!$postId) die("No post selected.");

// Fetch post
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) die("Post not found.");

// if post dosent belong to author

if ($post['author_email'] !== $userEmail) {
    die("You cannot edited this post.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = "Title and content cannot be empty.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE blog_posts
            SET title = :title, content = :content
            WHERE id = :id
        ");
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':id' => $postId
        ]);

        header("Location: profile.php");
        exit;
    }
}

// Render edit form
echo $twig->render('editPost.html.twig', [
    'post'  => $post,
    'error' => $error ?? null,
    'user'  => $user
]);
// Close PDO connection
$pdo = null;