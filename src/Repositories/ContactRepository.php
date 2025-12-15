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
}
