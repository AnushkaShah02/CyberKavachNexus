<?php
// src/Models/ChatMessage.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class ChatMessage {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Write message to database.
     */
    public function send(int $channelId, ?int $senderId, string $message, string $isAi = '0'): bool {
        $stmt = $this->db->prepare("INSERT INTO chats (channel_id, sender_id, message, is_ai) 
                                    VALUES (:channel_id, :sender_id, :message, :is_ai)");
        return $stmt->execute([
            'channel_id' => $channelId,
            'sender_id'  => $senderId,
            'message'    => $message,
            'is_ai'      => $isAi
        ]);
    }

    /**
     * Retrieve messages for a channel, support fetching only newer logs via lastId.
     */
    public function getMessages(int $channelId, int $lastId = 0): array {
        $stmt = $this->db->prepare("SELECT c.*, u.username, r.name as role_name 
                                    FROM chats c 
                                    LEFT JOIN users u ON c.sender_id = u.id 
                                    LEFT JOIN roles r ON u.role_id = r.id
                                    WHERE c.channel_id = :channel_id AND c.id > :last_id 
                                    ORDER BY c.id ASC");
        $stmt->execute([
            'channel_id' => $channelId,
            'last_id'    => $lastId
        ]);
        return $stmt->fetchAll();
    }
}
