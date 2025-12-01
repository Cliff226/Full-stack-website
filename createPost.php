<?php
require_once 'dbConnections/security.php';
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html', // Twig will escape outputs automatically
]);

// Check if user is logged in
$user = $_SESSION['name'] ?? false;
$email = $_SESSION['user'] ?? null;
if (!$user || !$email) {
    die("Not authorized.");
}

// Get league code from POST or GET
$leagueCode = filter_input(INPUT_POST, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
           ?? filter_input(INPUT_GET, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$leagueCode) die("No league selected.");

// Load leagues from cookie and decode
$leagues = json_decode($_COOKIE['leaguesData'] ?? '{}', true);
if (!isset($leagues[$leagueCode])) die("League not found.");

$selectedLeague = $leagues[$leagueCode];
$leagueName = $selectedLeague['name'];
$leagueCrest = $selectedLeague['crest'];

$errors = [];
$status = '';
$imagePath = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filter and sanitise user inputs
    $title   = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    if ($title === '') $errors[] = "Title cannot be empty.";
    if ($content === '') $errors[] = "Content cannot be empty.";

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName  = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg','jpeg','png','gif'];

        if (in_array($fileExt, $allowedExts)) {
            $newFileName = uniqid('img_', true) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $destination)) {
                $imagePath = 'public/uploads/' . $newFileName;
            } else {
                $errors[] = "Failed to upload the image.";
            }
        } else {
            $errors[] = "Only JPG, PNG, GIF files are allowed.";
        }
    }

    // Insert blog post if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO blog_posts 
            (title, content, author_name, author_email, image_path, league_code, created_at)
            VALUES (:title, :content, :author_name, :author_email, :image_path, :league_code, NOW())");

        $stmt->execute([
            ':title'        => $title,
            ':content'      => $content,
            ':author_name'  => $user,
            ':author_email' => $email,
            ':image_path'   => $imagePath,
            ':league_code'  => $leagueCode
        ]);

        $status = 'success';
        header("Location: blogArticles.php?league=" . urlencode($leagueCode));
        exit;
    } else {
        $status = 'error';
    }
}

// Render Twig template
echo $twig->render('createPost.html.twig', [
    'user'        => $user,
    'leagueName'  => $leagueName,
    'leagueCrest' => $leagueCrest,
    'leagueCode'  => $leagueCode,
    'status'      => $status,
    'errors'      => $errors
]);

$pdo = null;
