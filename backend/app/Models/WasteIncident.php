<?php
namespace App\Models;

use App\Services\Database;

class WasteIncident {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO waste_incidents (zone_id, type, severity, description)
            VALUES (:zone_id, :type, :severity, :description)
        ");
        $stmt->execute([
            ':zone_id'     => $data['zone_id'],
            ':type'        => $data['type'],
            ':severity'    => $data['severity'],
            ':description' => $data['description'] ?? null,
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM waste_incidents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(array $filters = []): array {
        $sql    = "SELECT * FROM waste_incidents WHERE resolved_at IS NULL";
        $params = [];

        if (!empty($filters['zone_id'])) {
            $sql .= " AND zone_id = :zone_id";
            $params[':zone_id'] = $filters['zone_id'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND severity = :severity";
            $params[':severity'] = $filters['severity'];
        }

        $sql .= " ORDER BY reported_at DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}