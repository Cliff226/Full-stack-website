<?php
// Start output buffering
ob_start();

// Anti-clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME-type sniffing
header('X-Content-Type-Options: nosniff');


// Get the full requested URL path
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string (everything after ?)
$uriPath = parse_url($requestUri, PHP_URL_PATH);

// Normalize path: lowercase and remove trailing slash
$uriPath = strtolower(rtrim($uriPath, '/'));

// List of all valid PHP pages (lowercase)
$validPages = [
    '/index.php',
    '/blog.php',
    '/blogarticles.php',
    '/captcha.php',
    '/createpost.php',
    '/deletecomment.php',
    '/deletepost.php',
    '/editcomment.php',
    '/editpost.php',
    '/fullarticle.php',
    '/info.php',
    '/livescore.php',
    '/livescoreapi.php',
    '/login.php',
    '/logout.php',
    '/palmares.php',
    '/profile.php',
    '/register.php',
    '/search.php',
    '/standings.php',
    '/standingsapi.php',
    '/teammatches.php',
    '/teammatchesapi.php'
];

// Check if requested page is valid
if (!in_array($uriPath, $validPages)) {
    // Page is invalid send 404 and redirect to logout or index
    http_response_code(404);
    header("Location: /logout.php");
    exit;
}
