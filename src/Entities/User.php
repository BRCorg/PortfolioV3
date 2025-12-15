<?php

namespace App\Entities;

/**
 * User Entity
 * Représente un utilisateur administrateur
 */
class User
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $password = null; // Hash du mot de passe
    private ?string $src = null; // Photo de profil
    private ?string $description = null;
    private ?string $birthdate = null;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBirthdate(): ?string
    {
        return $this->birthdate;
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

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setSrc(?string $src): self
    {
        $this->src = $src;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setBirthdate(?string $birthdate): self
    {
        $this->birthdate = $birthdate;
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
        $user = new self();

        $user->id = isset($data['id']) ? (int)$data['id'] : null;
        $user->name = $data['name'] ?? null;
        $user->email = $data['email'] ?? null;
        $user->password = $data['password'] ?? null;
        $user->src = $data['src'] ?? null;
        $user->description = $data['description'] ?? null;
        $user->birthdate = $data['birthdate'] ?? null;
        $user->createdAt = $data['created_at'] ?? null;
        $user->updatedAt = $data['updated_at'] ?? null;

        return $user;
    }

    /**
     * Convertir l'entité en tableau (pour la persistance)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'src' => $this->src,
            'description' => $this->description,
            'birthdate' => $this->birthdate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Vérifier si le mot de passe correspond
     */
    public function verifyPassword(string $password): bool
    {
        if (!$this->password) {
            return false;
        }

        return password_verify($password, $this->password);
    }

    /**
     * Hasher et définir le mot de passe
     */
    public function hashAndSetPassword(string $plainPassword): self
    {
        $this->password = password_hash($plainPassword, PASSWORD_DEFAULT);
        return $this;
    }

    /**
     * Obtenir l'URL de la photo de profil
     */
    public function getProfilePictureUrl(): string
    {
        return $this->src ?? '/assets/img/profile.jpg';
    }

    /**
     * Obtenir les initiales du nom (pour avatar par défaut)
     */
    public function getInitials(): string
    {
        if (!$this->name) {
            return 'GB';
        }

        $parts = explode(' ', $this->name);

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Calculer l'âge à partir de la date de naissance
     */
    public function getAge(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }

        $birthDate = new \DateTime($this->birthdate);
        $today = new \DateTime('today');

        return $birthDate->diff($today)->y;
    }
}
