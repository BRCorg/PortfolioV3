<?php

namespace App\Entities;

/**
 * Skill Entity
 * Représente une compétence technique
 */
class Skill
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $category = null;
    private ?string $level = null;
    private ?string $icon = null;
    private int $displayOrder = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // ==================== SETTERS (Fluent Interface) ====================

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ==================== HYDRATION ====================

    /**
     * Créer une entité depuis un tableau (résultat SQL)
     */
    public static function fromArray(array $data): self
    {
        $skill = new self();

        $skill->id = isset($data['id']) ? (int)$data['id'] : null;
        $skill->name = $data['name'] ?? null;
        $skill->category = $data['category'] ?? null;
        $skill->level = $data['level'] ?? null;
        $skill->icon = $data['icon'] ?? null;
        $skill->displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        $skill->createdAt = $data['created_at'] ?? null;
        $skill->updatedAt = $data['updated_at'] ?? null;

        return $skill;
    }

    /**
     * Convertir l'entité en tableau (pour la persistance)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'level' => $this->level,
            'icon' => $this->icon,
            'display_order' => $this->displayOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Obtenir le niveau traduit en français
     */
    public function getLevelFr(): string
    {
        return match($this->level) {
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
            'expert' => 'Expert',
            default => $this->level ?? 'Non défini'
        };
    }

    /**
     * Obtenir la catégorie traduite en français
     */
    public function getCategoryFr(): string
    {
        return match($this->category) {
            'frontend' => 'Frontend',
            'backend' => 'Backend',
            'devops' => 'DevOps',
            'database' => 'Base de données',
            'tools' => 'Outils',
            default => $this->category ?? 'Autre'
        };
    }

    /**
     * Obtenir la classe CSS pour le niveau
     */
    public function getLevelClass(): string
    {
        return match($this->level) {
            'beginner' => 'level-beginner',
            'intermediate' => 'level-intermediate',
            'advanced' => 'level-advanced',
            'expert' => 'level-expert',
            default => 'level-default'
        };
    }
}
