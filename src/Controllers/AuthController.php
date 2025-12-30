<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\UserRepository;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimiter;
use App\Core\SecurityLogger;

/**
 * AuthController
 * Gère l'authentification (login/logout)
 */
class AuthController
{
    private UserRepository $userRepository;
    private SecurityLogger $securityLogger;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();

        $this->userRepository = new UserRepository($db);
        $this->securityLogger = new SecurityLogger();
    }

    /**
     * Afficher la page de login
     */
    public function showLogin(): void
    {
        // Si déjà connecté, rediriger vers dashboard
        if (AuthMiddleware::isAuthenticated()) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard');
            exit;
        }

        $template = 'login';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Traiter le login
     */
    public function login(): void
    {
        // 1. RATE LIMITING - Bloquer les attaques brute force par IP
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::attempt("login_ip_{$clientIP}", 5, 15)) {
            $this->securityLogger->logRateLimitBlock('login_ip', $clientIP, $clientIP);
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=blocked&time=15');
            exit;
        }

        // 2. Vérifier le honeypot (protection anti-bot)
        if (!empty($_POST['website'])) {
            // Un bot a rempli le champ honeypot
            // On fait comme si tout était normal pour ne pas alerter le bot
            sleep(2); // Simulation d'un traitement
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=invalid_credentials');
            exit;
        }

        // 3. Récupérer les données du formulaire
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=missing_fields');
            exit;
        }

        // 4. RATE LIMITING - Par email (plus strict)
        if (!RateLimiter::attempt("login_email_{$email}", 3, 30)) {
            $this->securityLogger->logRateLimitBlock('login_email', $email, $clientIP);
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=blocked&time=30');
            exit;
        }

        // 5. Vérifier les credentials
        $user = $this->userRepository->findByEmail($email);

        // Vérifier que l'utilisateur existe et que le mot de passe est correct
        if (!$user || !password_verify($password, $user['password'])) {
            // Échec - attendre pour éviter le brute force
            $this->securityLogger->logLoginFailed($email, $clientIP);
            sleep(2);
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=invalid_credentials');
            exit;
        }

        // 6. SUCCÈS - Connexion réussie
        $this->securityLogger->logLoginSuccess($email, $clientIP);
        
        // Vérifier si le 2FA est activé
        if ($user['two_factor_enabled']) {
            // Stocker temporairement l'ID de l'utilisateur pour la vérification 2FA
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/verify');
            exit;
        }

        AuthMiddleware::login($user);

        // Rediriger vers le dashboard
        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard');
        exit;
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        // Logger la déconnexion si l'utilisateur est connecté
        if (isset($_SESSION['user'])) {
            $email = $_SESSION['user']['email'] ?? 'unknown';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $this->securityLogger->logLogout($email, $ip);
        }

        AuthMiddleware::logout();
        header('Location: /');
        exit;
    }
}
