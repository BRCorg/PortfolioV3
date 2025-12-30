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

// Protection contre les attaques XSS et injections
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Protection HTTPS (optionnelle en développement)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Désactiver l'exposition de la version PHP
header_remove('X-Powered-By');

// Bootstrap l'application
require_once __DIR__ . '/../bootstrap.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger les routes depuis le fichier de configuration
$router = require_once __DIR__ . '/../config/routes.php';

// ==========================================
// DISPATCHER
// ==========================================

try {
    $router->dispatch();
} catch (Exception $e) {
    // En mode debug, afficher l'erreur
    if ($_ENV['DEBUG_MODE'] === 'true') {
        echo "<h1>Erreur</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>500 - Erreur serveur</h1>";
    }
}
