<?php

namespace App\Models;

// We still need to require the database configuration if it's not autoloaded yet.
require_once __DIR__ . '/../../config/database.php';

class City
{
    private $conn;
    private $table = 'cities';

    public function __construct()
    {
        // Assuming 'Database' class is still global (no namespace yet),
        // we use '\Database' to refer to it from within this namespace.
        $database = new \Database();
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