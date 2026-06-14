<?php
namespace App\Models;

use App\Services\Database;

class Notification {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare("
            INSERT INTO citizen_notifications (citizen_id, title, body)
            VALUES (:citizen_id, :title, :body)
        ");
        $stmt->execute([
            ':citizen_id' => $data['citizen_id'],
            ':title'      => $data['title'],
            ':body'       => $data['body'],
        ]);
        return $this->findById((int)$this->db->lastInsertId());
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM citizen_notifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByCitizen(int $citizenId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM citizen_notifications
            WHERE citizen_id = ?
            ORDER BY created_at DESC LIMIT 50
        ");
        $stmt->execute([$citizenId]);
        return $stmt->fetchAll();
    }
}