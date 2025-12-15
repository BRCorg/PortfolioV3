<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Classe de base pour tous les modèles
 * Fournit les méthodes CRUD de base
 */
abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer tous les enregistrements
     */
    public function all(?string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un enregistrement par ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupérer un enregistrement par un champ spécifique
     */
    public function findBy(string $field, mixed $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['value' => $value]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupérer plusieurs enregistrements par un champ
     */
    public function findAllBy(string $field, mixed $value, string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->pdo->prepare($sql);
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

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un enregistrement
     */
    public function update(int $id, array $data): bool
    {
        $fields = array_map(fn($field) => "{$field} = :{$field}", array_keys($data));

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . "
                WHERE {$this->primaryKey} = :id";

        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }

    /**
     * Supprimer un enregistrement
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Compter le nombre d'enregistrements
     */
    public function count(string $where = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Vérifier si un enregistrement existe
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Exécuter une requête SQL personnalisée
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exécuter une requête et retourner un seul résultat
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
