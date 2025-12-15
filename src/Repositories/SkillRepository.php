<?php

namespace App\Repositories;

/**
 * Repository pour les compétences
 */
class SkillRepository extends BaseRepository
{
    protected string $table = 'skills';

    /**
     * Récupérer les compétences par catégorie
     */
    public function getByCategory(string $category): array
    {
        return $this->findAllBy('category', $category);
    }

    /**
     * Récupérer les compétences par niveau
     */
    public function getByLevel(string $level): array
    {
        return $this->findAllBy('level', $level);
    }

    /**
     * Récupérer toutes les compétences groupées par catégorie
     */
    public function getAllGroupedByCategory(): array
    {
        $skills = $this->all('category ASC, display_order ASC');
        $grouped = [];

        foreach ($skills as $skill) {
            $category = $skill['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $skill;
        }

        return $grouped;
    }
}
