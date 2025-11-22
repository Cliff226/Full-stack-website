<?php
// Debug — remove in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'almanacDatabaseConnection.php'; // Make sure this sets $pdo = new PDO(...)

session_start();

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

// Get selected league from GET (empty = all leagues)
$selectedLeague = $_GET['league'] ?? '';
$selectedLeague = trim($selectedLeague);

// When the refresh button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
    include 'livescoreAPI.php'; // This should update the livematches table
    // Reload the page
    header("Location: liveScore.php" . ($selectedLeague !== '' ? "?league=$selectedLeague" : ""));
    exit;
}

// Load all leagues for dropdown
$leagues = [];
$stmt = $pdo->query("SELECT DISTINCT competition FROM livematches ORDER BY competition ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $leagues[] = e($row['competition']);
}

// Fetch matches
if ($selectedLeague !== '') {
    // Filter by league
    $stmt = $pdo->prepare("
        SELECT * FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        AND competition = :competition
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
    $stmt->execute(['competition' => $selectedLeague]);
} else {
    // All leagues
    $stmt = $pdo->query("
        SELECT * FROM livematches
        WHERE DATE(kickoff) = CURDATE()
        ORDER BY country ASC, competition ASC, kickoff ASC
    ");
}

$matchesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group matches by country & competition
$groupedMatches = [];
foreach ($matchesData as $row) {
    $country = e($row['country']);
    $competition = e($row['competition']);

    if (!isset($groupedMatches[$country])) $groupedMatches[$country] = [];
    if (!isset($groupedMatches[$country][$competition])) $groupedMatches[$country][$competition] = [];

    $groupedMatches[$country][$competition][] = [
        'id' => e($row['match_id']),
        'competition' => ['name' => $competition],
        'area' => ['name' => $country],
        'homeTeam' => ['name' => htmlspecialchars_decode (e($row['home_team']))],
        'awayTeam' => ['name' => htmlspecialchars_decode(e($row['away_team']))],
        'score' => [
            'fullTime' => [
                'home' => (int)$row['home_score'],
                'away' => (int)$row['away_score']
            ]
        ],
        'minute' => htmlspecialchars_decode($row['minute']),
        'utcDate' => e($row['kickoff'])
    ];
}

// Render Twig template
echo $twig->render('liveScore.html.twig', [
    'groupedMatches' => $groupedMatches,
    'leagues' => $leagues,
    'selectedLeague' => $selectedLeague,
    'user' => $_SESSION['user'] ?? null,
    'current_page' => 'LiveScore'
]);