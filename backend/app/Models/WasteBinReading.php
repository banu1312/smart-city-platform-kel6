<?php
namespace App\Models;

use App\Services\Database;

class WasteBinReading {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO waste_bin_readings
                (zone_id, fill_level, gas_level, temperature, incident_flag)
            VALUES
                (:zone_id, :fill_level, :gas_level, :temperature, :incident_flag)
        ");
        $stmt->execute([
            ':zone_id'       => $data['zone_id'],
            ':fill_level'    => $data['fill_level'],
            ':gas_level'     => $data['gas_level']   ?? null,
            ':temperature'   => $data['temperature'] ?? null,
            ':incident_flag' => (float)$data['fill_level'] > 90 ? 1 : 0,
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM waste_bin_readings WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getLatestPerZone(): array {
        $stmt = $this->db->query("
            SELECT w.* FROM waste_bin_readings w
            INNER JOIN (
                SELECT zone_id, MAX(recorded_at) AS max_time
                FROM waste_bin_readings GROUP BY zone_id
            ) latest ON w.zone_id = latest.zone_id
                     AND w.recorded_at = latest.max_time
            ORDER BY w.zone_id
        ");
        return $stmt->fetchAll();
    }

    public function getHistory(array $filters = []): array {
        $sql    = "SELECT * FROM waste_bin_readings WHERE 1=1";
        $params = [];

        if (!empty($filters['zone_id'])) {
            $sql .= " AND zone_id = :zone_id";
            $params[':zone_id'] = $filters['zone_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND recorded_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND recorded_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY recorded_at DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}