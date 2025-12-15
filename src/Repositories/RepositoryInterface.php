<?php

namespace App\Repositories;

/**
 * Interface de base pour tous les repositories
 */
interface RepositoryInterface
{
    /**
     * Récupérer tous les enregistrements
     */
    public function all(string $orderBy = 'id ASC'): array;

    /**
     * Trouver un enregistrement par ID
     */
    public function find(int $id): ?array;

    /**
     * Créer un nouvel enregistrement
     */
    public function create(array $data): int;

    /**
     * Mettre à jour un enregistrement
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprimer un enregistrement
     */
    public function delete(int $id): bool;

    /**
     * Compter les enregistrements
     */
    public function count(): int;
}
