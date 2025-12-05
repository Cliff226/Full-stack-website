<?php
//require files
require_once 'dbConnections/security.php'; // Used to load the database connection
require_once 'dbConnections/standingsDatabaseConnection.php';//Used to load the database connection 
require_once '../vendor/autoload.php'; //Loads Composer autoload needed for Twig and other libraries

session_start(); // Start new or resume existing session

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates'); //Twig will load .twig files from the templates/ folder
$twig = new \Twig\Environment($loader, [
    'cache' => false, //Twig will not cache templates
    'autoescape' => 'html', // Automatically escapes output to prevent XSS attacks.
]);

// Check if user is logged in
$user = $_SESSION['name'] ?? false;
$email = $_SESSION['user'] ?? null;

// If not logged in store for modal and redirect to index
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: /index.php");
    exit;
}

// Get league code from POST or GET and filterig 
$leagueCode = filter_input(INPUT_POST, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
           ?? filter_input(INPUT_GET, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// if empty redirect
if (!$leagueCode) { 
    header("Location: /index.php");
    exit;
}

// Load leagues from cookie and decode
$leagues = json_decode($_COOKIE['leaguesData'] ?? '{}', true);

//check if the league exits 
if (!isset($leagues[$leagueCode])) { 
    header("Location: /index.php");
    exit;
}

//Initialise selected league variables
$selectedLeague = $leagues[$leagueCode];
$leagueName = $selectedLeague['name'];
$leagueCrest = $selectedLeague['crest'];
// Initialise variables for errors, status, and uploaded image
$errors = [];
$status = '';
$imagePath = null;

// Handle form submission for method post
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // filter the inputs 
    $title   = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    //  Validate inputs check if empty
    if ($title === '') $errors[] = "Title cannot be empty.";
    if ($content === '') $errors[] = "Content cannot be empty.";

    // Handle image upload if a file was submitted
    if (!empty($_FILES['image']['tmp_name'])) {//checks if the user actually selected a file.
        //tmp_name is the temporary file path on the server.
        $tmpName = $_FILES['image']['tmp_name']; //Stores the temporary file location
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));//extracts the file extension
        //Ensuring it is lowercase for consistent validation.

        $allowedExts = ['jpg','jpeg','png','gif']; //Validates the file extension

         // Validate image extension
        if (in_array($ext, $allowedExts)) {//Checks if the uploaded file’s extension is in the allowed list.
            $newFile = 'img_' . uniqid() . '.' . $ext;//Generate a unique filename
            $uploadDir = 'uploads/';//Where the images will be stored
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);//checks if the folder exists
            // Move uploaded file to uploads folder
            if (move_uploaded_file($tmpName, $uploadDir . $newFile)) {
                $imagePath = 'uploads/' . $newFile;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image type.";
        }
    }
     // Insert post into database if no errors are found
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, content, author_name, author_email, image_path, league_code, created_at)
                               VALUES (:title, :content, :author_name, :author_email, :image_path, :league_code, NOW())");
        $stmt->execute([
            ':title' => $title,//Content title
            ':content' => $content,//Content text
            ':author_name' => $user, //Name of the author
            ':author_email' => $email,//email of teh author
            ':image_path' => $imagePath, //New image path
            ':league_code' => $leagueCode//league code
        ]);

        // Redirect to specific league blog articles page
        header("Location: /blogArticles.php?league=" . urlencode($leagueCode));
        exit;

    } else {
        // Set error status for Twig templat
        $status = 'error';
    }
}

// Render Twig template
echo $twig->render('createPost.html.twig', [
    'user'        => $user, //Current user
    'leagueName'  => $leagueName,//Used for redireting
    'leagueCrest' => $leagueCrest,//Used for redireting
    'leagueCode'  => $leagueCode,//Used for redireting and to store
    'status'      => $status,//For modal
    'errors'      => $errors,//For modal
    'context'     => 'postCreated'//For modal
]);

//Close the Pdo connection
$pdo = null;
