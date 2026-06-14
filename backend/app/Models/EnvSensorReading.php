<?php
namespace App\Models;

use App\Services\Database;

class EnvSensorReading {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO env_sensor_readings
                (zone_id, pm25, pm10, no2, co, o3, temperature, humidity)
            VALUES
                (:zone_id, :pm25, :pm10, :no2, :co, :o3, :temperature, :humidity)
        ");
        $stmt->execute([
            ':zone_id'     => $data['zone_id'],
            ':pm25'        => $data['pm25']        ?? null,
            ':pm10'        => $data['pm10']        ?? null,
            ':no2'         => $data['no2']         ?? null,
            ':co'          => $data['co']          ?? null,
            ':o3'          => $data['o3']          ?? null,
            ':temperature' => $data['temperature'] ?? null,
            ':humidity'    => $data['humidity']    ?? null,
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM env_sensor_readings WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getLatestPerZone(): array {
        $stmt = $this->db->query("
            SELECT e.* FROM env_sensor_readings e
            INNER JOIN (
                SELECT zone_id, MAX(recorded_at) AS max_time
                FROM env_sensor_readings GROUP BY zone_id
            ) latest ON e.zone_id = latest.zone_id
                     AND e.recorded_at = latest.max_time
            ORDER BY e.zone_id
        ");
        return $stmt->fetchAll();
    }
}