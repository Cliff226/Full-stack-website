<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries
require_once 'dbConnections/standingsDatabaseConnection.php'; //Used to load the database connection 

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

//Check if user is logged in 
$user = $_SESSION['name'] ?? false; 
$email = $_SESSION['user'] ?? null;

//If not logged in redirect to index
if (!$user || !$email) {
    $_SESSION['notLoggedIn'] = true; 
    header("Location: /index.php");
    exit;
}

// Get article ID and santise it 
$article_id = trim($_GET['articleId'] ?? '');
if (!$article_id) { 
    header("Location: /index.php");
    exit;
}

// Fetch post from database using the ID
//prepared statements to prevent SQL injection
$stmt = $pdo->prepare("SELECT id, title, content, author_name, image_path, league_code, created_at
    FROM blog_posts WHERE id = :article_id LIMIT 1");
$stmt->execute([':article_id' => $article_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);//Fetches the post as an associative array.

//If post dose not exits redirect 
if (!$post) { 
    header("Location: /index.php");
    exit;
}

// Comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Sanitises comment text by trimming whitespace
    $commentText = trim($_POST['comment_text'] ?? '');

    // insert the comment in the database linking it to the post with post_id
    $stmt = $pdo->prepare("INSERT INTO comments 
        (post_id, author_name, author_email, Comment_content, created_at)
        VALUES (:post_id, :author_name, :author_email, :Comment_content, NOW())");

    $stmt->execute([
        ':post_id'      => $article_id,
        ':author_name'  => $user,
        ':author_email' => $email,
        ':Comment_content' => $commentText
    ]);

    header("Location: /fullArticle.php?articleId=" . $article_id);
    exit;
}

// Fetch all comments linked to the post
$stmt = $pdo->prepare("SELECT author_name, Comment_content, created_at
                       FROM comments WHERE post_id = :post_id ORDER BY created_at DESC");
$stmt->execute([':post_id' => $article_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render page
echo $twig->render('fullArticle.html.twig', [
    'comments'   => $comments, //all comments for that article
    'user'       => $user,
    'user_email' => $email,
    'post'       => $post //article details
]);
// Close PDO connection
$pdo = null;
