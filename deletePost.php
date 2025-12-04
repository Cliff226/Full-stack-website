<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'dbConnections/standingsDatabaseConnection.php';//Used to load the database connection 

session_start();// Start new or resume existing session

// Must be logged in
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) die("Not authorised.");

// Get post ID from POST
$postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$postId) die("Invalid post selected.");

// Verify post belongs to user
$stmt = $pdo->prepare("SELECT author_email FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch();

if (!$post) die("Post not found.");
if ($post['author_email'] !== $userEmail) die("You cannot delete this post.");

// Delete post
$stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $postId]);

header("Location: /profile.php");
exit;