<?php

namespace config;
use PDO;
use PDOException;

class Database
{
    private string $host;
    private int $port;
    private string $dbname;
    private string $charset = 'utf8';
    private string $username;
    private string $password;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? throw new \RuntimeException('DB_HOST non défini dans .env'));
        $this->port = (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306));
        $this->dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME non défini dans .env'));
        $this->username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? throw new \RuntimeException('DB_USER non défini dans .env'));
        $this->password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
    }

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
