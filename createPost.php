<?php
session_start();

// Include dependencies
require_once 'vendor/autoload.php';
require_once 'dbConnections/standingsDatabaseConnection.php';

//Setup Twig template engine 
$loader = new \Twig\Loader\FilesystemLoader('templates'); // Where your Twig templates are stored
$twig = new \Twig\Environment($loader); // Initialize Twig

//Check if user is logged in 
$user = $_SESSION['name'] ?? false; // Get username from session if exists
$email = $_SESSION['user'] ?? null; // Get email/username from session

if (!$user || !$email) {
    // If user is not logged in, redirect to login page
    $_SESSION['notLoggedIn'] = true; 
    header("Location: index.php");
    exit;
}

// Get the league code 
$leagueCode = trim($_POST['league'] ?? $_GET['league'] ?? '');
if (!$leagueCode) {
    die("No league selected."); // Stop if no league is selected
}

// Load leagues from cookie
if (!isset($_COOKIE['leaguesData'])) {
    die("No league data in cookie."); // Stop if cookie is missing
}

$leagues = json_decode($_COOKIE['leaguesData'], true); // Convert JSON cookie to PHP array

// Check if the selected league exists in cookie data
if (!isset($leagues[$leagueCode])) {
    die("League not found in cookie."); // Stop if invalid league
}

// Get league details for display
$selectedLeague = $leagues[$leagueCode];
$leagueName  = $selectedLeague['name'];
$leagueCrest = $selectedLeague['crest'];

//Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Sanitize user inputs
    $title   = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars(trim($_POST['content'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Handle image upload
    $imagePath = null; // Default to null (no image)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name']; // Temporary uploaded file path
        $fileName    = basename($_FILES['image']['name']); // Original file name
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); // Get extension
        $allowedExts = ['jpg','jpeg','png','gif']; // Allowed image types taht can be uploded

        if (in_array($fileExt, $allowedExts)) {
            // Create unique file name to prevent overwriting
            $newFileName = uniqid('img_', true) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/public/uploads/'; // Upload directory where the picture will be stored

            $destination = $uploadDir . $newFileName; // Full destination path

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imagePath = 'public/uploads/' . $newFileName; // Save the relative path of the image for DB
            } else {
                $_SESSION['error'] = "Failed to move uploaded file.";
            }
        } else {
            $_SESSION['error'] = "Only JPG, PNG, GIF files are allowed.";
        }
    }

    //Insert blog post into database
    $stmt = $pdo->prepare("
        INSERT INTO blog_posts 
        (title, content, author_name, author_email, image_path, league_code, created_at)
        VALUES
        (:title, :content, :author_name, :author_email, :image_path, :league_code, NOW())
    ");

    $stmt->execute([
        ':title'        => $title,
        ':content'      => $content,
        ':author_name'  => $user,
        ':author_email' => $email,
        ':image_path'   => $imagePath,
        ':league_code'  => $leagueCode
    ]);

    // Redirect to blog home after successful post
    header("Location: blogArticles.php?league=$leagueCode");
    exit;
}

//Render the create post page (GET request) ---
echo $twig->render('createPost.html.twig', [
    'user'        => $user,        
    'leagueName'  => $leagueName,  
    'leagueCrest' => $leagueCrest, 
    'leagueCode'  => $leagueCode,   // League code for form hidden value
]);

// Close PDO connection
$pdo = null;

