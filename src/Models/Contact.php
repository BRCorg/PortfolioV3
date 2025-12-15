<?php

namespace App\Models;

/**
 * Modèle Contact
 * Gestion des messages de contact
 */
class Contact extends BaseModel
{
    protected string $table = 'contacts';

    /**
     * Récupérer tous les messages triés par date (les plus récents en premier)
     */
    public function all(?string $orderBy = 'created_at DESC'): array
    {
        return parent::all($orderBy);
    }

    /**
     * Récupérer les messages non lus
     */
    public function getUnread(): array
    {
        return $this->findAllBy('is_read', 0, 'created_at DESC');
    }

    /**
     * Récupérer les messages lus
     */
    public function getRead(): array
    {
        return $this->findAllBy('is_read', 1, 'created_at DESC');
    }

    /**
     * Compter les messages non lus
     */
    public function countUnread(): int
    {
        return $this->count('is_read = 0');
    }

    /**
     * Créer un nouveau message de contact
     */
    public function createContact(array $data): int
    {
        // Ajouter l'IP et le user agent si disponibles
        if (!isset($data['ip_address']) && isset($_SERVER['REMOTE_ADDR'])) {
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }

        if (!isset($data['user_agent']) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        return $this->create($data);
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(int $id): bool
    {
        return $this->update($id, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Marquer un message comme non lu
     */
    public function markAsUnread(int $id): bool
    {
        return $this->update($id, [
            'is_read' => 0,
            'read_at' => null
        ]);
    }

    /**
     * Marquer plusieurs messages comme lus
     */
    public function markMultipleAsRead(array $ids): bool
    {
        try {
            $this->pdo->beginTransaction();

            foreach ($ids as $id) {
                $this->markAsRead($id);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Récupérer les messages par email
     */
    public function getByEmail(string $email): array
    {
        return $this->findAllBy('email', $email, 'created_at DESC');
    }

    /**
     * Récupérer les messages récents (dernières 24h)
     */
    public function getRecent(int $hours = 24): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                ORDER BY created_at DESC";

        return $this->query($sql, ['hours' => $hours]);
    }

    /**
     * Récupérer les derniers messages (par nombre)
     */
    public function getLatest(int $limit = 5): array
    {
        $sql = "SELECT * FROM {$this->table}
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            // Fallback : récupérer tous et limiter avec array_slice
            $allMessages = $this->all('created_at DESC');
            return array_slice($allMessages, 0, $limit);
        }
    }

    /**
     * Rechercher dans les messages
     */
    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE first_name LIKE :query
                   OR last_name LIKE :query
                   OR email LIKE :query
                   OR subject LIKE :query
                   OR message LIKE :query
                ORDER BY created_at DESC";

        return $this->query($sql, ['query' => "%{$query}%"]);
    }

    /**
     * Supprimer les anciens messages (plus de X jours)
     */
    public function deleteOld(int $days = 90): int
    {
        $sql = "DELETE FROM {$this->table}
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['days' => $days]);

        return $stmt->rowCount();
    }

    /**
     * Obtenir des statistiques sur les messages
     */
    public function getStats(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as last_7days
                FROM {$this->table}";

        return $this->queryOne($sql) ?? [];
    }
}
