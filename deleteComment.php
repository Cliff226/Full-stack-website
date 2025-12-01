<?php
require_once 'dbConnections/security.php';
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();

$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    die("Not authorized.");
}

// Validate comment ID
$commentId = filter_input(INPUT_GET, 'idComment', FILTER_VALIDATE_INT);
if (!$commentId) {
    die("No valid comment selected.");
}

// Fetch the comment to verify if comment belonges to user
$stmt = $pdo->prepare("SELECT author_email FROM comments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    die("Comment not found.");
}

// Check if comment belonges to the user 
if ($comment['author_email'] !== $userEmail) {
    die("You cannot delete this comment.");
}

// Delete the comment
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id");
$stmt->execute([':id' => $commentId]);

header("Location: profile.php");

// Close PDO connection
$pdo = null;
exit;
