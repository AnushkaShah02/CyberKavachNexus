<?php
// src/Models/User.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Retrieve a user by email address.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT u.*, r.name as role_name 
                                    FROM users u 
                                    JOIN roles r ON u.role_id = r.id 
                                    WHERE u.email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new user with hashed credentials.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param int    $roleId
     * @return bool
     */
    public function create(string $username, string $email, string $password, int $roleId): bool {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role_id) 
                                    VALUES (:username, :email, :password_hash, :role_id)");
        return $stmt->execute([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => $hash,
            'role_id'       => $roleId
        ]);
    }

    /**
     * Increase a user's reward points ledger.
     *
     * @param int $userId
     * @param int $points
     * @return bool
     */
    public function addPoints(int $userId, int $points): bool {
        $stmt = $this->db->prepare("UPDATE users SET reward_points = reward_points + :points WHERE id = :userId");
        return $stmt->execute([
            'points' => $points,
            'userId' => $userId
        ]);
    }
}
