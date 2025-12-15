<?php

namespace App\Repositories;

use PDO;

/**
 * Repository de base avec les opérations CRUD communes
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $db;
    protected string $table;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les enregistrements
     */
    public function all(string $orderBy = 'id ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouver un enregistrement par ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Trouver par un champ spécifique
     */
    public function findBy(string $field, mixed $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Trouver tous les enregistrements correspondant à un critère
     */
    public function findAllBy(string $field, mixed $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un nouvel enregistrement
     */
    public function create(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Mettre à jour un enregistrement
     */
    public function update(int $id, array $data): bool
    {
        $fields = array_keys($data);
        $set = implode(', ', array_map(fn($field) => "{$field} = :{$field}", $fields));

        $sql = "UPDATE {$this->table} SET {$set} WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([...$data, 'id' => $id]);
    }

    /**
     * Supprimer un enregistrement
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Compter les enregistrements
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Vérifier si un enregistrement existe
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Exécuter une requête personnalisée
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exécuter une requête et retourner un seul résultat
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
