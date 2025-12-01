<?php
require_once 'dbConnections/security.php' ;
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();


// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // can be 'html', 'js', 'css', 'url', false
]);

//Check if user is logged in 
$user = $_SESSION['name'] ?? false; 
$email = $_SESSION['user'] ?? null;

if (!$user || !$email) {
    $_SESSION['notLoggedIn'] = true; 
    header("Location: index.php");
    exit;
}

// Get article ID
$article_id = trim($_GET['articleId'] ?? '');
if (!$article_id) {
    die("No article found.");
}

// Fetch post
$stmt = $pdo->prepare("SELECT id, title, content, author_name, image_path, league_code, created_at
                       FROM blog_posts WHERE id = :article_id LIMIT 1");
$stmt->execute([':article_id' => $article_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Article not found.");
}

// Comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $commentText = trim($_POST['comment_text'] ?? '');

    if ($commentText === '') {
        die("Comment cannot be empty.");
    }

    $stmt = $pdo->prepare("INSERT INTO comments 
        (post_id, author_name, author_email, Comment_content, created_at)
        VALUES (:post_id, :author_name, :author_email, :Comment_content, NOW())");

    $stmt->execute([
        ':post_id'      => $article_id,
        ':author_name'  => $user,
        ':author_email' => $email,
        ':Comment_content' => $commentText
    ]);

    header("Location: fullArticle.php?articleId=" . $article_id);
    exit;
}

// Fetch comments
$stmt = $pdo->prepare("SELECT author_name, Comment_content, created_at
                       FROM comments WHERE post_id = :post_id ORDER BY created_at DESC");
$stmt->execute([':post_id' => $article_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render page
echo $twig->render('fullArticle.html.twig', [
    'comments'   => $comments,
    'user'  => $user,
    'user_email' => $email,
    'post'       => $post
]);
// Close PDO connection
$pdo = null;
