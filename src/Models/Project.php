<?php

namespace App\Models;

/**
 * Modèle Project
 * Gestion des projets du portfolio
 */
class Project extends BaseModel
{
    protected string $table = 'projects';

    /**
     * Récupérer tous les projets publiés
     */
    public function getPublished(string $orderBy = 'display_order ASC'): array
    {
        return $this->findAllBy('status', 'published', $orderBy);
    }

    /**
     * Récupérer les projets mis en avant
     */
    public function getFeatured(): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_featured = 1 AND status = 'published'
                ORDER BY display_order ASC";

        return $this->query($sql);
    }

    /**
     * Récupérer un projet par slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Récupérer un projet avec sa catégorie
     */
    public function findWithCategory(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id";

        return $this->queryOne($sql, ['id' => $id]);
    }

    /**
     * Récupérer un projet par slug avec sa catégorie
     */
    public function findBySlugWithCategory(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug";

        return $this->queryOne($sql, ['slug' => $slug]);
    }

    /**
     * Récupérer un projet avec toutes ses relations (catégorie + compétences)
     */
    public function findComplete(int $id): ?array
    {
        $project = $this->findWithCategory($id);

        if ($project) {
            $project['skills'] = $this->getProjectSkills($id);
        }

        return $project;
    }

    /**
     * Récupérer un projet par slug avec toutes ses relations
     */
    public function findCompleteBySlug(string $slug): ?array
    {
        $project = $this->findBySlugWithCategory($slug);

        if ($project) {
            $project['skills'] = $this->getProjectSkills($project['id']);
        }

        return $project;
    }

    /**
     * Récupérer les compétences d'un projet
     */
    public function getProjectSkills(int $projectId): array
    {
        $sql = "SELECT s.*
                FROM skills s
                INNER JOIN project_skills ps ON s.id = ps.skill_id
                WHERE ps.project_id = :project_id
                ORDER BY s.display_order";

        return $this->query($sql, ['project_id' => $projectId]);
    }

    /**
     * Récupérer les projets par catégorie
     */
    public function getByCategory(int $categoryId, bool $publishedOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE category_id = :category_id";

        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }

        $sql .= " ORDER BY display_order ASC";

        return $this->query($sql, ['category_id' => $categoryId]);
    }

    /**
     * Créer un projet avec génération automatique du slug
     */
    public function createProject(array $data, array $skillIds = []): int
    {
        if (!isset($data['slug']) && isset($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        if (!isset($data['display_order'])) {
            $data['display_order'] = $this->getNextDisplayOrder();
        }

        $projectId = $this->create($data);

        if (!empty($skillIds)) {
            $this->attachSkills($projectId, $skillIds);
        }

        return $projectId;
    }

    /**
     * Mettre à jour un projet
     */
    public function updateProject(int $id, array $data, array $skillIds = null): bool
    {
        $updated = $this->update($id, $data);

        if ($skillIds !== null) {
            $this->syncSkills($id, $skillIds);
        }

        return $updated;
    }

    /**
     * Associer des compétences à un projet
     */
    public function attachSkills(int $projectId, array $skillIds): bool
    {
        try {
            foreach ($skillIds as $skillId) {
                $sql = "INSERT INTO project_skills (project_id, skill_id)
                        VALUES (:project_id, :skill_id)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'project_id' => $projectId,
                    'skill_id' => $skillId
                ]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Synchroniser les compétences d'un projet (remplace toutes les anciennes)
     */
    public function syncSkills(int $projectId, array $skillIds): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Supprimer les anciennes associations
            $sql = "DELETE FROM project_skills WHERE project_id = :project_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['project_id' => $projectId]);

            // Ajouter les nouvelles
            if (!empty($skillIds)) {
                $this->attachSkills($projectId, $skillIds);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Détacher une compétence d'un projet
     */
    public function detachSkill(int $projectId, int $skillId): bool
    {
        $sql = "DELETE FROM project_skills
                WHERE project_id = :project_id AND skill_id = :skill_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'project_id' => $projectId,
            'skill_id' => $skillId
        ]);
    }

    /**
     * Réorganiser l'ordre d'affichage
     */
    public function reorder(array $projectIds): bool
    {
        try {
            $this->pdo->beginTransaction();

            foreach ($projectIds as $order => $projectId) {
                $this->update($projectId, ['display_order' => $order]);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Rechercher des projets
     */
    public function search(string $query, bool $publishedOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (title LIKE :query OR description LIKE :query)";

        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }

        $sql .= " ORDER BY display_order ASC";

        return $this->query($sql, ['query' => "%{$query}%"]);
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
     * Générer un slug à partir d'un titre
     */
    private function generateSlug(string $title): string
    {
        // Utilise la fonction helper globale pour la génération de base
        $slug = generateSlug($title);

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
     * Supprimer un projet (supprime aussi les associations avec les compétences)
     */
    public function delete(int $id): bool
    {
        // Les associations sont supprimées automatiquement grâce à ON DELETE CASCADE
        return parent::delete($id);
    }
}
