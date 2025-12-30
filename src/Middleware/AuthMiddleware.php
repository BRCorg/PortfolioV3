<?php

namespace App\Middleware;

/**
 * Middleware d'authentification
 * Protège les routes admin
 */
class AuthMiddleware
{
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::isAuthenticated()) {
            // Rediriger vers la page de connexion
            $loginUrl = $_ENV['ADMIN_SECRET_URL'] ?? '/admin';
            header('Location: ' . $loginUrl . '/login');
            exit;
        }

        // Vérifier l'IP si configuré
        self::checkAllowedIP();
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_authenticated']);
    }

    /**
     * Connecter un utilisateur
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Régénérer l'ID de session pour éviter la fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Stocker aussi l'utilisateur complet pour compatibilité
        $_SESSION['user'] = $user;

        // Token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Déconnecter l'utilisateur
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire toutes les variables de session
        $_SESSION = [];

        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Détruire la session
        session_destroy();
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public static function user(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'username' => $_SESSION['user_username'] ?? null,
        ];
    }

    /**
     * Vérifier le token CSRF
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Obtenir le token CSRF
     */
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifier l'IP autorisée
     */
    private static function checkAllowedIP(): void
    {
        $allowedIPs = $_ENV['ADMIN_ALLOWED_IPS'] ?? '';

        if (empty($allowedIPs)) {
            return; // Pas de restriction IP
        }

        $allowedIPsArray = array_map('trim', explode(',', $allowedIPs));
        $userIP = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!in_array($userIP, $allowedIPsArray)) {
            http_response_code(403);
            die('Accès refusé : IP non autorisée');
        }
    }

    /**
     * Vérifier l'inactivité de la session
     */
    public static function checkSessionTimeout(int $timeout = 3600): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];

            if ($inactive > $timeout) {
                self::logout();
                return false;
            }
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Vérifier si c'est la bonne URL secrète pour l'admin
     */
    public static function checkSecretUrl(string $requestedUrl): bool
    {
        $secretUrl = $_ENV['ADMIN_SECRET_URL'] ?? '/admin';
        return strpos($requestedUrl, $secretUrl) === 0;
    }

    /**
     * Bloquer l'accès si ce n'est pas l'URL secrète
     */
    public static function requireSecretUrl(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!self::checkSecretUrl($requestUri)) {
            http_response_code(404);
            die('Page non trouvée');
        }
    }
}
