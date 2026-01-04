<?php

namespace App\Middleware;

/**
 * RateLimiter
 * Protection contre le spam et les attaques par force brute
 */
class RateLimiter
{
    /**
     * Vérifier si une action est limitée (Rate Limiting)
     *
     * Système de protection contre :
     * - Le spam automatisé (bots)
     * - Les attaques par force brute (brute force)
     * - Les abus (flooding)
     *
     * Fonctionnement :
     * - Utilise un compteur par clé unique (IP, email, etc.)
     * - Le compteur s'incrémente à chaque tentative
     * - Si le compteur dépasse maxAttempts, l'action est bloquée
     * - Le compteur se réinitialise automatiquement après decayMinutes
     *
     * Exemples d'utilisation :
     * - attempt('login_192.168.1.1', 5, 15) : max 5 tentatives de login par IP toutes les 15min
     * - attempt('contact_user@email.com', 3, 60) : max 3 messages par email par heure
     *
     * @param string $key Identifiant unique de l'action (ex: 'login_192.168.1.1', 'contact_user@email.com')
     * @param int $maxAttempts Nombre maximum de tentatives autorisées
     * @param int $decayMinutes Durée en minutes avant réinitialisation du compteur
     * @return bool True si l'action est autorisée, False si la limite est atteinte
     */
    public static function attempt(string $key, int $maxAttempts = 5, int $decayMinutes = 15): bool
    {
        // Initialiser le tableau de rate limiting en session si nécessaire
        if (!isset($_SESSION['rate_limiter'])) {
            $_SESSION['rate_limiter'] = [];
        }

        $now = time();
        $rateLimitKey = 'rl_' . $key; // Préfixe pour éviter les collisions de clés

        // ÉTAPE 1 : Nettoyage des anciennes entrées expirées
        // Évite l'accumulation de données en session
        self::cleanup();

        // ÉTAPE 2 : Première tentative pour cette clé
        if (!isset($_SESSION['rate_limiter'][$rateLimitKey])) {
            $_SESSION['rate_limiter'][$rateLimitKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60) // Timestamp de fin du délai
            ];
            return true; // Première tentative toujours autorisée
        }

        $data = $_SESSION['rate_limiter'][$rateLimitKey];

        // ÉTAPE 3 : Vérifier si le délai de reset est dépassé
        // Si oui, on réinitialise le compteur et on autorise l'action
        if ($now > $data['reset_at']) {
            $_SESSION['rate_limiter'][$rateLimitKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60)
            ];
            return true; // Compteur réinitialisé, action autorisée
        }

        // ÉTAPE 4 : Incrémenter le compteur de tentatives
        $_SESSION['rate_limiter'][$rateLimitKey]['attempts']++;

        // ÉTAPE 5 : Vérifier si la limite est dépassée
        if ($_SESSION['rate_limiter'][$rateLimitKey]['attempts'] > $maxAttempts) {
            return false; // BLOQUÉ : Trop de tentatives
        }

        return true; // Autorisé : Encore des tentatives disponibles
    }

    /**
     * Récupérer le nombre de tentatives restantes
     */
    public static function remaining(string $key, int $maxAttempts = 5): int
    {
        if (!isset($_SESSION['rate_limiter'])) {
            return $maxAttempts;
        }

        $rateLimitKey = 'rl_' . $key;

        if (!isset($_SESSION['rate_limiter'][$rateLimitKey])) {
            return $maxAttempts;
        }

        $attempts = $_SESSION['rate_limiter'][$rateLimitKey]['attempts'];
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Récupérer le temps restant avant reset (en secondes)
     */
    public static function availableIn(string $key): int
    {
        if (!isset($_SESSION['rate_limiter'])) {
            return 0;
        }

        $rateLimitKey = 'rl_' . $key;

        if (!isset($_SESSION['rate_limiter'][$rateLimitKey])) {
            return 0;
        }

        $resetAt = $_SESSION['rate_limiter'][$rateLimitKey]['reset_at'];
        $remaining = max(0, $resetAt - time());

        return $remaining;
    }

    /**
     * Réinitialiser les tentatives pour une clé
     */
    public static function clear(string $key): void
    {
        $rateLimitKey = 'rl_' . $key;

        if (isset($_SESSION['rate_limiter'][$rateLimitKey])) {
            unset($_SESSION['rate_limiter'][$rateLimitKey]);
        }
    }

    /**
     * Nettoyer les anciennes entrées expirées
     */
    private static function cleanup(): void
    {
        if (!isset($_SESSION['rate_limiter'])) {
            return;
        }

        $now = time();

        foreach ($_SESSION['rate_limiter'] as $key => $data) {
            if ($now > $data['reset_at']) {
                unset($_SESSION['rate_limiter'][$key]);
            }
        }
    }

    /**
     * Obtenir l'IP du client
     */
    public static function getClientIp(): string
    {
        // Vérifier si derrière un proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Nettoyer l'IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
