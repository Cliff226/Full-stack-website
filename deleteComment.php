<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'dbConnections/standingsDatabaseConnection.php';//Used to load the database connection 

session_start(); // Start new or resume existing session

// Check if user is logged in
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) die("Not authorised.");

// Get ID from the methed post 
$commentId = filter_input(INPUT_POST, 'idComment', FILTER_VALIDATE_INT);
if (!$commentId) die("Invalid comment selected.");

// Check ownership 
$stmt = $pdo->prepare("SELECT author_email FROM comments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch();

if (!$comment) die("Comment not found.");
if ($comment['author_email'] !== $userEmail) die("You cannot delete this comment.");

// Delete comment
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $commentId]);

header("Location: /profile.php");
exit;
