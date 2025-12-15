<?php

namespace App\Middleware;

/**
 * RateLimiter
 * Protection contre le spam et les attaques par force brute
 */
class RateLimiter
{
    /**
     * Vérifier si une action est limitée
     *
     * @param string $key Identifiant unique (ex: 'login_192.168.1.1', 'contact_user@email.com')
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $decayMinutes Durée en minutes avant reset
     * @return bool True si l'action est autorisée, False si limitée
     */
    public static function attempt(string $key, int $maxAttempts = 5, int $decayMinutes = 15): bool
    {
        if (!isset($_SESSION['rate_limiter'])) {
            $_SESSION['rate_limiter'] = [];
        }

        $now = time();
        $rateLimitKey = 'rl_' . $key;

        // Nettoyer les anciennes entrées expirées
        self::cleanup();

        // Vérifier si l'entrée existe
        if (!isset($_SESSION['rate_limiter'][$rateLimitKey])) {
            $_SESSION['rate_limiter'][$rateLimitKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60)
            ];
            return true;
        }

        $data = $_SESSION['rate_limiter'][$rateLimitKey];

        // Si le délai est expiré, réinitialiser
        if ($now > $data['reset_at']) {
            $_SESSION['rate_limiter'][$rateLimitKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60)
            ];
            return true;
        }

        // Incrémenter le compteur
        $_SESSION['rate_limiter'][$rateLimitKey]['attempts']++;

        // Vérifier si limite dépassée
        if ($_SESSION['rate_limiter'][$rateLimitKey]['attempts'] > $maxAttempts) {
            return false;
        }

        return true;
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
