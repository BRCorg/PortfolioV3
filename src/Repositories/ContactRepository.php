<?php

namespace App\Repositories;

/**
 * Repository pour les messages de contact
 */
class ContactRepository extends BaseRepository
{
    protected string $table = 'contacts';

    /**
     * Récupérer les messages non lus
     */
    public function getUnread(): array
    {
        return $this->findAllBy('is_read', 0);
    }

    /**
     * Récupérer les messages lus
     */
    public function getRead(): array
    {
        return $this->findAllBy('is_read', 1);
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(int $id): bool
    {
        return $this->update($id, ['is_read' => 1]);
    }

    /**
     * Marquer un message comme non lu
     */
    public function markAsUnread(int $id): bool
    {
        return $this->update($id, ['is_read' => 0]);
    }

    /**
     * Compter les messages non lus
     */
    public function countUnread(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_read = 0";
        $stmt = $this->db->query($sql);
        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
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
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            // Fallback : récupérer tous et limiter avec array_slice
            $allMessages = $this->all('created_at DESC');
            return array_slice($allMessages, 0, $limit);
        }
    }
}
