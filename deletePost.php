<?php
require_once 'dbConnections/security.php';
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();

// Twig not needed here since no output is rendered
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorized.");
}

// Validate post ID
$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$postId) {
    die("No valid post selected.");
}

// Fetch the post to verify ownership
$stmt = $pdo->prepare("SELECT author_email FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

// Ownership check
if ($post['author_email'] !== $userEmail) {
    die("You cannot delete this post.");
}

// Delete the post
$stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
$stmt->execute([':id' => $postId]);

// Delete associated comments
$stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = :id");
$stmt->execute([':id' => $postId]);

// Redirect after deletion
header("Location: profile.php");
$pdo = null;
exit;
