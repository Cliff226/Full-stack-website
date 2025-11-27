<?php
session_start();
require_once 'dbConnections/standingsDatabaseConnection.php';

$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorized.");
}

$postId = $_GET['id'] ?? null;
if (!$postId) {
    die("No post selected.");
}

// Fetch the post to verify ownership
$stmt = $pdo->prepare("SELECT author_email FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

if ($post['author_email'] !== $userEmail) {
    die("You cannot delete this post.");
}

// Delete the post
$stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
$stmt->execute([':id' => $postId]);

//delete associated comments
$stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = :id");
$stmt->execute([':id' => $postId]);

header("Location: profile.php");
// Close PDO connection
$pdo = null;
exit;