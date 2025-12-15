<?php

namespace App\Repositories;

/**
 * Repository pour les utilisateurs
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Trouver un utilisateur par username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }

    /**
     * Vérifier si un email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";

        if ($excludeId) {
            $sql .= " AND id != :id";
        }

        $stmt = $this->db->prepare($sql);
        $params = ['email' => $email];

        if ($excludeId) {
            $params['id'] = $excludeId;
        }

        $stmt->execute($params);
        return ((int) $stmt->fetch(\PDO::FETCH_ASSOC)['count']) > 0;
    }

    /**
     * Activer le 2FA pour un utilisateur
     */
    public function enable2FA(int $userId, string $secret, string $backupCodes): bool
    {
        $sql = "UPDATE {$this->table} 
                SET two_factor_enabled = 1,
                    two_factor_secret = :secret,
                    two_factor_backup_codes = :backup_codes,
                    two_factor_enabled_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $userId,
            'secret' => $secret,
            'backup_codes' => $backupCodes
        ]);
    }

    /**
     * Désactiver le 2FA pour un utilisateur
     */
    public function disable2FA(int $userId): bool
    {
        $sql = "UPDATE {$this->table} 
                SET two_factor_enabled = 0,
                    two_factor_secret = NULL,
                    two_factor_backup_codes = NULL,
                    two_factor_enabled_at = NULL
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Mettre à jour les codes de backup
     */
    public function updateBackupCodes(int $userId, string $backupCodes): bool
    {
        $sql = "UPDATE {$this->table} 
                SET two_factor_backup_codes = :backup_codes
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $userId,
            'backup_codes' => $backupCodes
        ]);
    }
}
