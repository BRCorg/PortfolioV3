<?php

namespace App\Models;

/**
 * Modèle Category
 * Gestion des catégories de projets
 */
class Category extends BaseModel
{
    protected string $table = 'categories';

    /**
     * Récupérer une catégorie par slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Récupérer toutes les catégories avec le nombre de projets
     */
    public function getAllWithProjectCount(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) as project_count
                FROM {$this->table} c
                LEFT JOIN projects p ON c.id = p.category_id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY c.name";

        return $this->query($sql);
    }

    /**
     * Récupérer les catégories qui ont au moins un projet publié
     */
    public function getWithPublishedProjects(): array
    {
        $sql = "SELECT DISTINCT c.*
                FROM {$this->table} c
                INNER JOIN projects p ON c.id = p.category_id
                WHERE p.status = 'published'
                ORDER BY c.name";

        return $this->query($sql);
    }

    /**
     * Créer une catégorie avec génération automatique du slug
     */
    public function createCategory(array $data): int
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->create($data);
    }

    /**
     * Générer un slug à partir d'un nom
     */
    private function generateSlug(string $name): string
    {
        // Utilise la fonction helper globale pour la génération de base
        $slug = generateSlug($name);

        // Vérifier l'unicité du slug dans la base de données
        $baseSlug = $slug;
        $counter = 1;
        while ($this->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
