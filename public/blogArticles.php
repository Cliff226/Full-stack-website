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
$user = $_SESSION['name'] ?? false;//Store the name of the loged in user
$email = $_SESSION['user'] ?? null; //Store the email of the loged in user
$status = $_SESSION['status'] ?? ''; // For modal 
$context = $_SESSION['context'] ?? ''; //for modal output
unset($_SESSION['status'], $_SESSION['context']); // Remove from the session not needed anymore

//User not logged in 
if (!$user || !$email) {
    $_SESSION['notLoggedIn'] = true; //Sets a session show the in the login modal
    header("Location: /index.php"); //Redirects to index.php.
    exit;
}

// Get selected league code from GET with filtering
$leagueCode = trim(filter_input(INPUT_GET, 'league', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
if (!$leagueCode) {
    // If the league code is not found relocate to index 
    header("Location: /index.php");//Redirects to index.php.
    exit;
}

// Load League Data from Cookie
if (isset($_COOKIE['leaguesData'])) {
    // Reads previously stored league data from a JSON cookie.
    $leagues = json_decode($_COOKIE['leaguesData'], true); //Decodes it into an associative array.
} else {
    // If the cookie is empty relocate to index 
    header("Location: /index.php");//Redirects to index.php.
    exit;
}

// Check if the selected league exists in the cookie
if (isset($leagues[$leagueCode])) {
    $selectedLeague = $leagues[$leagueCode];
} else {
        // if the league dose not exist relocate to index 
    header("Location: /index.php");//Redirects to index.php (invalid or tampered URL).
    exit;
}

// Extract league details
$leagueName  = $selectedLeague['name'];
$leagueCrest = $selectedLeague['crest'];
$leagueId    = $selectedLeague['id'];

// Fetch posts from database using prepared statement
$stmt = $pdo->prepare(" SELECT id, title, content, author_name, image_path, created_at, league_code
    FROM blog_posts
    WHERE league_code = :league_code
    ORDER BY created_at DESC
");
$stmt->execute([':league_code' => $leagueCode]);

// Store the posts
$posts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Decode all fields
    foreach ($row as $key => $value) {
        $row[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $posts[] = $row;
}

// Render template
echo $twig->render('blogArticles.html.twig', [
    'user' => $user, //Current user
    'current_page' => 'blogArticles',//Active page
    'leagueName'  => $leagueName,//League info
    'leagueCrest' => $leagueCrest,//League info
    'leagueCode'  => $leagueCode,//League info
    'leagueId'    => $leagueId,//League info
    'posts'       => $posts,//List of posts 
    'status'      => $status, //Modal status messages
    'context'     => $context//Modal status messages
]);

//Close the Pdo connection
$pdo = null;
