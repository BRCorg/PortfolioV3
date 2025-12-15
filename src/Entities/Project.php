<?php

namespace App\Entities;

/**
 * Project Entity
 * Représente un projet du portfolio
 */
class Project
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $slug = null;
    private ?string $image = null;
    private ?int $categoryId = null;
    private ?string $categoryName = null; // Pour les relations
    private string $status = 'draft';
    private bool $isFeatured = false;
    private int $displayOrder = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private array $skills = []; // Pour la relation many-to-many

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
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

    public function getSkills(): array
    {
        return $this->skills;
    }

    // ==================== SETTERS (Fluent Interface) ====================

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function setCategoryName(?string $categoryName): self
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;
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

    public function setSkills(array $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    // ==================== HYDRATION ====================

    /**
     * Créer une entité depuis un tableau (résultat SQL)
     */
    public static function fromArray(array $data): self
    {
        $project = new self();

        $project->id = isset($data['id']) ? (int)$data['id'] : null;
        $project->title = $data['title'] ?? null;
        $project->description = $data['description'] ?? null;
        $project->slug = $data['slug'] ?? null;
        $project->image = $data['image'] ?? null;
        $project->categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $project->categoryName = $data['category_name'] ?? null;
        $project->status = $data['status'] ?? 'draft';
        $project->isFeatured = isset($data['is_featured']) ? (bool)$data['is_featured'] : false;
        $project->displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        $project->createdAt = $data['created_at'] ?? null;
        $project->updatedAt = $data['updated_at'] ?? null;
        $project->skills = $data['skills'] ?? [];

        return $project;
    }

    /**
     * Convertir l'entité en tableau (pour la persistance)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image,
            'category_id' => $this->categoryId,
            'status' => $this->status,
            'is_featured' => $this->isFeatured ? 1 : 0,
            'display_order' => $this->displayOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Vérifie si le projet est publié
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Vérifie si le projet est en brouillon
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Obtenir l'URL de l'image (avec chemin complet)
     */
    public function getImageUrl(): ?string
    {
        return $this->image ? '/img/projects/' . $this->image : null;
    }

    /**
     * Obtenir un extrait de la description
     */
    public function getExcerpt(int $length = 150): ?string
    {
        if (!$this->description) {
            return null;
        }

        $plainText = strip_tags($this->description);

        if (mb_strlen($plainText) <= $length) {
            return $plainText;
        }

        return mb_substr($plainText, 0, $length) . '...';
    }
}
