<?php

namespace App\Core;

/**
 * SecurityLogger
 * Gère les logs de sécurité (tentatives de connexion, activités suspectes)
 */
class SecurityLogger
{
    private string $logFile;
    private string $securityLogFile;

    public function __construct()
    {
        $logDir = __DIR__ . '/../../logs';
        
        // Créer le dossier logs s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logFile = $logDir . '/security.log';
        $this->securityLogFile = $logDir . '/security_alerts.log';
    }

    /**
     * Logger une tentative de connexion réussie
     */
    public function logLoginSuccess(string $email, string $ip): void
    {
        $this->log('LOGIN_SUCCESS', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }

    /**
     * Logger une tentative de connexion échouée
     */
    public function logLoginFailed(string $email, string $ip, string $reason = 'Invalid credentials'): void
    {
        $this->log('LOGIN_FAILED', [
            'email' => $email,
            'ip' => $ip,
            'reason' => $reason,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], 'warning');
    }

    /**
     * Logger un blocage par rate limiting
     */
    public function logRateLimitBlock(string $type, string $identifier, string $ip): void
    {
        $this->log('RATE_LIMIT_BLOCK', [
            'type' => $type,
            'identifier' => $identifier,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], 'alert');
    }

    /**
     * Logger une tentative de spam
     */
    public function logSpamDetection(string $ip, array $data): void
    {
        $this->log('SPAM_DETECTED', [
            'ip' => $ip,
            'email' => $data['email'] ?? 'N/A',
            'name' => $data['name'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], 'alert');
    }

    /**
     * Logger une tentative CSRF
     */
    public function logCsrfAttempt(string $ip): void
    {
        $this->log('CSRF_ATTEMPT', [
            'ip' => $ip,
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], 'critical');
    }

    /**
     * Logger une déconnexion
     */
    public function logLogout(string $email, string $ip): void
    {
        $this->log('LOGOUT', [
            'email' => $email,
            'ip' => $ip
        ]);
    }

    /**
     * Logger une activité suspecte (tentative d'accès non autorisé)
     */
    public function logSuspiciousActivity(string $action, string $ip, array $details = []): void
    {
        $this->log('SUSPICIOUS_ACTIVITY', array_merge([
            'action' => $action,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], $details), 'alert');
    }

    /**
     * Méthode centrale de logging
     */
    private function log(string $event, array $data, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'event' => $event,
            'data' => $data
        ];

        $formattedLog = sprintf(
            "[%s] [%s] %s - %s\n",
            $timestamp,
            strtoupper($level),
            $event,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Écrire dans le fichier principal
        file_put_contents($this->logFile, $formattedLog, FILE_APPEND | LOCK_EX);

        // Si c'est une alerte critique, logger aussi dans le fichier d'alertes
        if (in_array($level, ['warning', 'alert', 'critical'])) {
            file_put_contents($this->securityLogFile, $formattedLog, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Récupérer les derniers logs (pour affichage admin)
     */
    public function getRecentLogs(int $lines = 50): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = file($this->logFile);
        $logs = array_slice($file, -$lines);
        
        return array_map(function($line) {
            // Parser chaque ligne de log
            if (preg_match('/\[(.*?)\] \[(.*?)\] (.*?) - (.*)/', $line, $matches)) {
                return [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'event' => $matches[3],
                    'data' => json_decode($matches[4], true) ?? []
                ];
            }
            return null;
        }, $logs);
    }

    /**
     * Compter les tentatives de connexion échouées pour une IP
     */
    public function countFailedAttempts(string $ip, int $minutesAgo = 60): int
    {
        if (!file_exists($this->logFile)) {
            return 0;
        }

        $cutoffTime = time() - ($minutesAgo * 60);
        $count = 0;

        $handle = fopen($this->logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, 'LOGIN_FAILED') !== false && strpos($line, $ip) !== false) {
                    // Extraire le timestamp
                    if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                        $logTime = strtotime($matches[1]);
                        if ($logTime >= $cutoffTime) {
                            $count++;
                        }
                    }
                }
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Nettoyer les vieux logs (garder X jours)
     */
    public function cleanOldLogs(int $daysToKeep = 30): void
    {
        $files = [$this->logFile, $this->securityLogFile];
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $lines = file($file);
            $newLines = [];

            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $cutoffTime) {
                        $newLines[] = $line;
                    }
                }
            }

            file_put_contents($file, implode('', $newLines), LOCK_EX);
        }
    }
}
