<?php
namespace App\Models;

use App\Services\Database;

class Report {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO citizen_reports
                (citizen_id, category, description, zone_id, status)
            VALUES
                (:citizen_id, :category, :description, :zone_id, 'pending')
        ");
        $stmt->execute([
            ':citizen_id'  => $data['citizen_id'],
            ':category'    => $data['category'],
            ':description' => $data['description'] ?? null,
            ':zone_id'     => $data['zone_id']     ?? null,
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM citizen_reports WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(array $filters = []): array {
        $sql    = "SELECT * FROM citizen_reports WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['zone_id'])) {
            $sql .= " AND zone_id = :zone_id";
            $params[':zone_id'] = $filters['zone_id'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): ?array {
        $stmt = $this->db->prepare("UPDATE citizen_reports SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0 ? $this->findById($id) : null;
    }
}