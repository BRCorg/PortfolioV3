<?php

namespace App\Models;

/**
 * Modèle User
 * Gestion du profil utilisateur (admin)
 */
class User extends BaseModel
{
    protected string $table = 'users';

    /**
     * Récupérer un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Récupérer un utilisateur par username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }

    /**
     * Vérifier les identifiants de connexion
     */
    public function checkCredentials(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Créer un nouvel utilisateur avec mot de passe hashé
     */
    public function createUser(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($id, ['password' => $hashedPassword]);
    }

    /**
     * Mettre à jour le profil (bio, photo, etc.)
     */
    public function updateProfile(int $id, array $profileData): bool
    {
        // Ne pas permettre la modification du mot de passe via cette méthode
        unset($profileData['password']);

        return $this->update($id, $profileData);
    }

    /**
     * Mettre à jour la photo de profil
     */
    public function updateProfilePicture(int $id, string $picturePath): bool
    {
        return $this->update($id, ['profile_picture' => $picturePath]);
    }

    /**
     * Mettre à jour la bio
     */
    public function updateBio(int $id, string $bio): bool
    {
        return $this->update($id, ['bio' => $bio]);
    }

    /**
     * Récupérer les informations publiques du profil
     */
    public function getPublicProfile(int $id): ?array
    {
        $sql = "SELECT id, username, first_name, last_name, bio, profile_picture,
                       github_url, linkedin_url
                FROM {$this->table}
                WHERE id = :id";

        return $this->queryOne($sql, ['id' => $id]);
    }
}
