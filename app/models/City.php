<?php

require_once __DIR__ . '/../../config/database.php';

class City
{
    private $conn;
    private $table = 'cities';

    public function __construct()
    {
        $database   = new \Database();
        $this->conn = $database->connect();
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, name, country FROM {$this->table} WHERE name = :name LIMIT 1");
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $name, ?string $country = null): int
    {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, country) VALUES (:name, :country)");
        $stmt->execute([
            'name'    => $name,
            'country' => $country,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query("SELECT id, name FROM {$this->table} ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
