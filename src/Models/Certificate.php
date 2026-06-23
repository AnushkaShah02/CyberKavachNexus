<?php
// src/Models/Certificate.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class Certificate {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Store new certificate log.
     */
    public function create(int $userId, int $eventId, string $uuid, string $type, string $verificationHash, string $downloadPath): bool {
        $stmt = $this->db->prepare("INSERT INTO certificates (user_id, event_id, uuid, type, verification_hash, download_path) 
                                    VALUES (:user_id, :event_id, :uuid, :type, :verification_hash, :download_path)");
        return $stmt->execute([
            'user_id'           => $userId,
            'event_id'          => $eventId,
            'uuid'              => $uuid,
            'type'              => $type,
            'verification_hash' => $verificationHash,
            'download_path'     => $downloadPath
        ]);
    }

    /**
     * Public search verification logic.
     * Checks if certificate is real, returns user/event details.
     *
     * @param string $query SHA256 Hash or UUID string.
     * @return array|null
     */
    public function verify(string $query): ?array {
        $stmt = $this->db->prepare("SELECT c.*, u.username, u.email, e.title as event_title, e.end_time as event_date 
                                    FROM certificates c 
                                    JOIN users u ON c.user_id = u.id 
                                    JOIN events e ON c.event_id = e.id 
                                    WHERE c.uuid = :query OR c.verification_hash = :hash");
        $stmt->execute([
            'query' => $query,
            'hash'  => $query
        ]);
        $cert = $stmt->fetch();
        return $cert ?: null;
    }
}
