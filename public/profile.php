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

//Check login

$userName  = $_SESSION['name'] ?? null;
$userEmail = $_SESSION['user'] ?? null;

if (!$userEmail) {
    $_SESSION['notLoggedIn'] = true;
    header("Location: index.php");
    exit;
}

// Fetch user's posts
$stmt = $pdo->prepare("SELECT id, title, created_at, league_code
    FROM blog_posts
    WHERE author_email = :email
    ORDER BY created_at DESC
");
$stmt->execute([':email' => $userEmail]);
$userPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($userPosts as &$post) {
    $post['title'] = html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
unset($post);

//Fetch user's comments
$stmt = $pdo->prepare("SELECT 
           c.id,
        c.Comment_content,
        c.created_at,
        p.title AS post_title,
        p.id AS post_id
    FROM comments AS c
    INNER JOIN blog_posts AS p 
        ON p.id = c.post_id
    WHERE c.author_email = :email
    ORDER BY c.created_at DESC
");
$stmt->execute([':email' => $userEmail]);
$userComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($userComments as &$comment) {
    $comment['Comment_content'] = html_entity_decode($comment['Comment_content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $comment['post_title'] = html_entity_decode($comment['post_title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
unset($comment);

//Render profile page
echo $twig->render('profile.html.twig', [
    'user_name'    => $userName,
    'user_email'   => $userEmail,
    'posts'        => $userPosts,
    'comments'     => $userComments,
    'user'         => $user
]);
// Close PDO connection
$pdo = null;
