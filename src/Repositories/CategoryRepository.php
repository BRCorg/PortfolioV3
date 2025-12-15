<?php

namespace App\Repositories;

/**
 * Repository pour les catégories
 */
class CategoryRepository extends BaseRepository
{
    protected string $table = 'categories';

    /**
     * Trouver une catégorie par slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Récupérer les catégories avec le nombre de projets
     */
    public function getAllWithProjectCount(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) as project_count
                FROM {$this->table} c
                LEFT JOIN projects p ON c.id = p.category_id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY c.name ASC";

        return $this->query($sql);
    }
}
