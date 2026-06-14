<?php
namespace App\Models;

use App\Services\Database;

class EnvAlert {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO env_alerts (zone_id, alert_type, severity, value, threshold)
            VALUES (:zone_id, :alert_type, :severity, :value, :threshold)
        ");
        $stmt->execute([
            ':zone_id'    => $data['zone_id'],
            ':alert_type' => $data['alert_type'],
            ':severity'   => $data['severity'],
            ':value'      => $data['value'],
            ':threshold'  => $data['threshold'],
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM env_alerts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getActive(array $filters = []): array {
        $sql    = "SELECT * FROM env_alerts WHERE resolved_at IS NULL";
        $params = [];

        if (!empty($filters['zone_id'])) {
            $sql .= " AND zone_id = :zone_id";
            $params[':zone_id'] = $filters['zone_id'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND severity = :severity";
            $params[':severity'] = $filters['severity'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}