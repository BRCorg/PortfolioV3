<?php

namespace App\Entities;

/**
 * Category Entity
 * Représente une catégorie de projet
 */
class Category
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $slug = null;
    private ?string $description = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private int $projectCount = 0; // Pour les statistiques

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getProjectCount(): int
    {
        return $this->projectCount;
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

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    public function setProjectCount(int $projectCount): self
    {
        $this->projectCount = $projectCount;
        return $this;
    }

    // ==================== HYDRATION ====================

    /**
     * Créer une entité depuis un tableau (résultat SQL)
     */
    public static function fromArray(array $data): self
    {
        $category = new self();

        $category->id = isset($data['id']) ? (int)$data['id'] : null;
        $category->name = $data['name'] ?? null;
        $category->slug = $data['slug'] ?? null;
        $category->description = $data['description'] ?? null;
        $category->createdAt = $data['created_at'] ?? null;
        $category->updatedAt = $data['updated_at'] ?? null;
        $category->projectCount = isset($data['project_count']) ? (int)$data['project_count'] : 0;

        return $category;
    }

    /**
     * Convertir l'entité en tableau (pour la persistance)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Vérifie si la catégorie a des projets
     */
    public function hasProjects(): bool
    {
        return $this->projectCount > 0;
    }

    /**
     * Obtenir un message pour le nombre de projets
     */
    public function getProjectCountMessage(): string
    {
        if ($this->projectCount === 0) {
            return 'Aucun projet';
        }

        if ($this->projectCount === 1) {
            return '1 projet';
        }

        return $this->projectCount . ' projets';
    }
}
