<?php

namespace App\Entities;

/**
 * Contact Entity
 * Représente un message de contact
 */
class Contact
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $subject = null;
    private ?string $message = null;
    private bool $isRead = false;
    private ?string $createdAt = null;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
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

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // ==================== HYDRATION ====================

    /**
     * Créer une entité depuis un tableau (résultat SQL)
     */
    public static function fromArray(array $data): self
    {
        $contact = new self();

        $contact->id = isset($data['id']) ? (int)$data['id'] : null;
        $contact->name = $data['name'] ?? null;
        $contact->email = $data['email'] ?? null;
        $contact->subject = $data['subject'] ?? null;
        $contact->message = $data['message'] ?? null;
        $contact->isRead = isset($data['is_read']) ? (bool)$data['is_read'] : false;
        $contact->createdAt = $data['created_at'] ?? null;

        return $contact;
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
            'subject' => $this->subject,
            'message' => $this->message,
            'is_read' => $this->isRead ? 1 : 0,
            'created_at' => $this->createdAt,
        ];
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Marquer le message comme lu
     */
    public function markAsRead(): self
    {
        $this->isRead = true;
        return $this;
    }

    /**
     * Marquer le message comme non lu
     */
    public function markAsUnread(): self
    {
        $this->isRead = false;
        return $this;
    }

    /**
     * Obtenir le statut en français
     */
    public function getStatusFr(): string
    {
        return $this->isRead ? 'Lu' : 'Non lu';
    }

    /**
     * Obtenir la classe CSS pour le statut
     */
    public function getStatusClass(): string
    {
        return $this->isRead ? 'status-read' : 'status-unread';
    }

    /**
     * Obtenir un extrait du message
     */
    public function getExcerpt(int $length = 100): ?string
    {
        if (!$this->message) {
            return null;
        }

        if (mb_strlen($this->message) <= $length) {
            return $this->message;
        }

        return mb_substr($this->message, 0, $length) . '...';
    }

    /**
     * Obtenir la date formatée
     */
    public function getFormattedDate(): ?string
    {
        if (!$this->createdAt) {
            return null;
        }

        $date = new \DateTime($this->createdAt);
        return $date->format('d/m/Y à H:i');
    }

    /**
     * Obtenir le temps écoulé depuis la création (ex: "il y a 2 jours")
     */
    public function getTimeAgo(): ?string
    {
        if (!$this->createdAt) {
            return null;
        }

        $date = new \DateTime($this->createdAt);
        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->d == 0) {
            if ($diff->h == 0) {
                if ($diff->i == 0) {
                    return "À l'instant";
                }
                return "Il y a " . $diff->i . " minute" . ($diff->i > 1 ? 's' : '');
            }
            return "Il y a " . $diff->h . " heure" . ($diff->h > 1 ? 's' : '');
        }

        if ($diff->d < 7) {
            return "Il y a " . $diff->d . " jour" . ($diff->d > 1 ? 's' : '');
        }

        if ($diff->d < 30) {
            $weeks = floor($diff->d / 7);
            return "Il y a " . $weeks . " semaine" . ($weeks > 1 ? 's' : '');
        }

        return $date->format('d/m/Y');
    }
}
