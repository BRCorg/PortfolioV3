<?php

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\TwoFactorController;
use App\Controllers\DashboardController;
use App\Controllers\ProjectController;
use App\Controllers\SkillController;
use App\Controllers\CategoryController;
use App\Controllers\ContactController;
use App\Controllers\SitemapController;

// Créer le router
$router = new Router();

// URL admin depuis .env (getenv pour compatibilité)
$adminUrl = getenv('ADMIN_SECRET_URL') ?: ($_ENV['ADMIN_SECRET_URL'] ?? throw new \RuntimeException('ADMIN_SECRET_URL non défini dans .env'));
if (is_array($adminUrl)) {
    throw new \RuntimeException('ADMIN_SECRET_URL doit être une chaîne de caractères');
}

// ==========================================
// ROUTES PUBLIQUES
// ==========================================

// Page d'accueil
$router->get('/', HomeController::class, 'index');

// Détails d'un projet
$router->get('/project/{id}', HomeController::class, 'projectDetails');

// Formulaire de contact
$router->post('/contact/submit', ContactController::class, 'submit');

// SEO - Sitemap XML
$router->get('/sitemap.xml', SitemapController::class, 'generate');

// ==========================================
// ROUTES ADMIN - Authentification
// ==========================================

// Login
$router->get($adminUrl, AuthController::class, 'showLogin');
$router->post($adminUrl . '/login', AuthController::class, 'login');
$router->get($adminUrl . '/logout', AuthController::class, 'logout');

// Dashboard
$router->get($adminUrl . '/dashboard', DashboardController::class, 'index');

// ==========================================
// ROUTES ADMIN - 2FA (Two-Factor Authentication)
// ==========================================

// Configuration 2FA
$router->get($adminUrl . '/2fa/setup', TwoFactorController::class, 'showSetup');
$router->post($adminUrl . '/2fa/enable', TwoFactorController::class, 'enable');
$router->get($adminUrl . '/2fa/backup-codes', TwoFactorController::class, 'showBackupCodes');
$router->post($adminUrl . '/2fa/confirm-backup', TwoFactorController::class, 'confirmBackupCodes');

// Vérification 2FA (login)
$router->get($adminUrl . '/2fa/verify', TwoFactorController::class, 'showVerify');
$router->post($adminUrl . '/2fa/verify', TwoFactorController::class, 'verify');
$router->post($adminUrl . '/2fa/verify-backup', TwoFactorController::class, 'verifyBackup');

// Désactiver 2FA
$router->post($adminUrl . '/2fa/disable', TwoFactorController::class, 'disable');

// ==========================================
// ROUTES ADMIN - Projets
// ==========================================

$router->get($adminUrl . '/projects', ProjectController::class, 'list');
$router->post($adminUrl . '/projects/create', ProjectController::class, 'create');
$router->post($adminUrl . '/projects/{id}/update', ProjectController::class, 'update');
$router->get($adminUrl . '/projects/{id}/delete', ProjectController::class, 'delete');

// ==========================================
// ROUTES ADMIN - Skills
// ==========================================

$router->get($adminUrl . '/skills', SkillController::class, 'list');
$router->post($adminUrl . '/skills/create', SkillController::class, 'create');
$router->post($adminUrl . '/skills/{id}/update', SkillController::class, 'update');
$router->post($adminUrl . '/skills/{id}/delete', SkillController::class, 'delete');

// ==========================================
// ROUTES ADMIN - Catégories
// ==========================================

$router->get($adminUrl . '/categories', CategoryController::class, 'list');
$router->post($adminUrl . '/categories/create', CategoryController::class, 'create');
$router->get($adminUrl . '/categories/{id}/edit', CategoryController::class, 'edit');
$router->post($adminUrl . '/categories/{id}/update', CategoryController::class, 'update');
$router->post($adminUrl . '/categories/{id}/delete', CategoryController::class, 'delete');

// ==========================================
// ROUTES ADMIN - Messages de contact
// ==========================================

$router->get($adminUrl . '/contacts', ContactController::class, 'list');
$router->post($adminUrl . '/contacts/{id}/mark-read', ContactController::class, 'markAsRead');
$router->post($adminUrl . '/contacts/{id}/delete', ContactController::class, 'delete');

// Retourner le router
return $router;
