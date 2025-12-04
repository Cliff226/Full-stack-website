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

// Get and sanitise session user
$user = htmlspecialchars($_SESSION['user'] ?? '', ENT_QUOTES, 'UTF-8');

// If not logged in store for modal and redirect to index
if (!$user) {
    $_SESSION['notLoggedIn'] = true;  
    header("Location: /index.php");
    exit;
}

// Load login modal flag from session
$notLoggedIn = !empty($_SESSION['notLoggedIn']);
unset($_SESSION['notLoggedIn']); 

// Fetch all leagues from database
$stmt = $pdo->prepare("SELECT id, name, code, country, crest FROM leagues");
$stmt->execute();
$leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$leagues) {
    header("Location: /index.php");
    exit;
}

// Inistalise the arrays to store data
$stored_leagues = [];
$leaguesForCookie = [];

foreach ($leagues as $row) {
    // Sanitise DB output
    $leagueName    = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
    $leagueCountry = htmlspecialchars($row['country'], ENT_QUOTES, 'UTF-8');
    $leagueCrest   = htmlspecialchars($row['crest'], ENT_QUOTES, 'UTF-8');
    $leagueCode    = htmlspecialchars($row['code'], ENT_QUOTES, 'UTF-8');

    //Bulid the array for twig 
    $stored_leagues[$row['id']] = [
        'name'    => $leagueName,
        'code'    => $leagueCode,
        'country' => $leagueCountry,
        'crest'   => $leagueCrest
    ];
    //Bulid the array for cookie
    $leaguesForCookie[$leagueCode] = [
        'id'      => $row['id'],
        'name'    => $leagueName,
        'country' => $leagueCountry,
        'crest'   => $leagueCrest,
        'code'    => $leagueCode
    ];
}

// Saves all leagues as JSON into the browser
setcookie(
    "leaguesData",
    json_encode($leaguesForCookie),
    time() + 7200, // 2 hours
    "/",
    "",
    false,      // because using localhost
    true   // JavaScript cannot access cookie
);


// Render the blog template
echo $twig->render('blog.html.twig', [
    'user'         => $user, //Current logged in user
    'current_page' => 'blog', //Used for active menu highlighting
    'notLoggedIn'  => $notLoggedIn, //Used for modal if user not logged in
    'leagues'      => $stored_leagues //All sanitised league data for display
]);

// Close PDO connection
$pdo = null;
