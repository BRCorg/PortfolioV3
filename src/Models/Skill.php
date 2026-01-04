<?php

namespace App\Models;

/**
 * Modèle Skill
 * Gestion des compétences
 */
class Skill extends BaseModel
{
    protected string $table = 'skills';

    /**
     * Récupérer toutes les compétences ordonnées par display_order
     */
    public function all(?string $orderBy = 'display_order ASC'): array
    {
        return parent::all($orderBy);
    }

    /**
     * Récupérer une compétence par slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Récupérer les compétences par niveau
     */
    public function getByLevel(string $level): array
    {
        return $this->findAllBy('level', $level, 'display_order ASC');
    }

    /**
     * Récupérer les compétences d'un projet
     */
    public function getByProject(int $projectId): array
    {
        $sql = "SELECT s.*
                FROM {$this->table} s
                INNER JOIN project_skills ps ON s.id = ps.skill_id
                WHERE ps.project_id = :project_id
                ORDER BY s.display_order";

        return $this->query($sql, ['project_id' => $projectId]);
    }

    /**
     * Créer une compétence avec génération automatique du slug
     */
    public function createSkill(array $data): int
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        if (!isset($data['display_order'])) {
            $data['display_order'] = $this->getNextDisplayOrder();
        }

        return $this->create($data);
    }

    /**
     * Réorganiser l'ordre d'affichage
     */
    public function reorder(array $skillIds): bool
    {
        try {
            $this->pdo->beginTransaction();

            foreach ($skillIds as $order => $skillId) {
                $this->update($skillId, ['display_order' => $order]);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Obtenir le prochain numéro d'ordre
     */
    private function getNextDisplayOrder(): int
    {
        $sql = "SELECT MAX(display_order) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return ((int) $stmt->fetchColumn()) + 1;
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

    /**
     * Grouper les compétences par niveau
     */
    public function groupByLevel(): array
    {
        $skills = $this->all();
        $grouped = [
            'expert' => [],
            'avancé' => [],
            'intermédiaire' => [],
            'débutant' => []
        ];

        foreach ($skills as $skill) {
            $grouped[$skill['level']][] = $skill;
        }

        return $grouped;
    }
}
