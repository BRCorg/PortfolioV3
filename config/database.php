<?php

namespace config;
use PDO;
use PDOException;

/**
 * Classe Database - Singleton Pattern
 *
 * Garantit une seule instance de connexion à la base de données
 * pour toute l'application, évitant ainsi les connexions multiples.
 */
class Database
{
    private static ?Database $instance = null;

    private string $host;
    private int $port;
    private string $dbname;
    private string $charset = 'utf8mb4';
    private string $username;
    private string $password;
    private ?PDO $pdo = null;

    /**
     * Constructeur privé - empêche l'instanciation directe
     */
    private function __construct()
    {
        $this->host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? throw new \RuntimeException('DB_HOST non défini dans .env'));
        $this->port = (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306));
        $this->dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME non défini dans .env'));
        $this->username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? throw new \RuntimeException('DB_USER non défini dans .env'));
        $this->password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Retourne l'instance unique de Database (Singleton)
     *
     * @return Database Instance unique
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion PDO
     *
     * @return PDO Connexion à la base de données
     */
    public function connect(): PDO
    {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
                $this->pdo = new PDO($dsn, $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die('Erreur de connexion : ' . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
