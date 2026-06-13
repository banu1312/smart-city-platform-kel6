<?php
namespace App\Models;

use App\Services\Database;

class Citizen {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO citizen_citizens
                (nik, name, email, phone, zone_id, role, password)
            VALUES
                (:nik, :name, :email, :phone, :zone_id, :role, :password)
        ");
        $stmt->execute([
            ':nik'      => $data['nik'],
            ':name'     => $data['name'],
            ':email'    => $data['email'],
            ':phone'    => $data['phone']   ?? null,
            ':zone_id'  => $data['zone_id'] ?? null,
            ':role'     => $data['role']    ?? 'citizen',
            ':password' => $data['password'],
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM citizen_citizens WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}