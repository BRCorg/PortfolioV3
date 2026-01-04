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
     * Connecter un utilisateur et créer une session sécurisée
     *
     * Processus de connexion sécurisé :
     * 1. Régénération de l'ID de session (protection contre session fixation)
     * 2. Stockage des informations utilisateur en session
     * 3. Génération d'un token CSRF unique pour protéger les formulaires
     * 4. Tracking du temps de connexion et d'activité
     *
     * @param array $user Tableau contenant les informations de l'utilisateur (id, email, etc.)
     * @return void
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // SÉCURITÉ CRITIQUE : Régénérer l'ID de session
        // Cela empêche les attaques de session fixation où un attaquant
        // force un utilisateur à utiliser un ID de session connu
        // Le paramètre 'true' supprime également l'ancien fichier de session
        session_regenerate_id(true);

        // Stocker les informations essentielles de l'utilisateur
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_authenticated'] = true; // Flag d'authentification
        $_SESSION['login_time'] = time(); // Timestamp de connexion initiale
        $_SESSION['last_activity'] = time(); // Timestamp de dernière activité (pour timeout)

        // Stocker l'objet utilisateur complet pour accès facile
        // (informations 2FA, profil, etc.)
        $_SESSION['user'] = $user;

        // Générer un nouveau token CSRF pour protéger tous les formulaires
        // Token de 32 bytes (64 caractères hexadécimaux) cryptographiquement sécurisé
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
     * Vérifier le token CSRF (Cross-Site Request Forgery)
     *
     * Le token CSRF protège contre les attaques où un site malveillant
     * tente d'effectuer des actions au nom de l'utilisateur connecté.
     *
     * Utilise hash_equals() au lieu de === pour éviter les timing attacks :
     * - hash_equals() prend toujours le même temps quelle que soit la différence
     * - === peut révéler la longueur de la chaîne via le temps d'exécution
     *
     * @param string $token Le token soumis par le formulaire
     * @return bool True si le token est valide, False sinon
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Comparaison sécurisée contre les timing attacks
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
