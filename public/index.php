<?php
// Affichage des erreurs pour debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Forcer l'encodage UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// ==========================================
// HEADERS DE SÉCURITÉ HTTP
// ==========================================

// Content Security Policy (protection XSS)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.googletagmanager.com https://www.google-analytics.com https://ssl.google-analytics.com https://chart.googleapis.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; img-src 'self' data: https: http:; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com data:; connect-src 'self' https://cdn.jsdelivr.net https://www.google-analytics.com https://analytics.google.com https://stats.g.doubleclick.net; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");

// Protection contre les attaques XSS et injections
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Protection HTTPS (optionnelle en développement)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Désactiver l'exposition de la version PHP
header_remove('X-Powered-By');

// Bootstrap l'application
require_once __DIR__ . '/../bootstrap.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier le timeout de session automatiquement
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
    if (!App\Middleware\AuthMiddleware::checkSessionTimeout(1800)) { // 30 minutes
        session_destroy();
        header('Location: /admin/login?expired=1');
        exit;
    }
}

// Charger les routes depuis le fichier de configuration
$router = require_once __DIR__ . '/../config/routes.php';

// ==========================================
// DISPATCHER
// ==========================================

try {
    $router->dispatch();
} catch (Exception $e) {
    // Logger l'erreur (sécurisé - ne jamais afficher la stack trace)
    error_log('[ERREUR CRITIQUE] ' . $e->getMessage());
    error_log('[STACK TRACE] ' . $e->getTraceAsString());

    // Réponse utilisateur sécurisée
    http_response_code(500);

    // En mode debug, afficher uniquement le message (pas la stack trace)
    if ($_ENV['DEBUG_MODE'] === 'true') {
        echo "<h1>Erreur</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><em>Consultez les logs pour plus de détails</em></p>";
    } else {
        echo "<h1>500 - Erreur serveur</h1>";
        echo "<p>Une erreur s'est produite. Veuillez réessayer plus tard.</p>";
    }
}
