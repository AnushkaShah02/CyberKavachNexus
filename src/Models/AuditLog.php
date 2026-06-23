<?php
// src/Models/AuditLog.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class AuditLog {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Log a system/user event.
     *
     * @param int|null    $userId
     * @param string      $action
     * @param string|null $details
     * @return bool
     */
    public function log(?int $userId, string $action, ?string $details = null): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent';

        // Anonymize IP address slightly if desired or save raw for security verification
        $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) 
                                    VALUES (:user_id, :action, :details, :ip_address, :user_agent)");
        return $stmt->execute([
            'user_id'    => $userId,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => $ip,
            'user_agent' => $ua
        ]);
    }
}
