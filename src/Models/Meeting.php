<?php
// src/Models/Meeting.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class Meeting {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a general or committee meeting.
     */
    public function create(string $title, ?string $description, string $scheduledAt, int $durationMinutes, ?string $meetingLink, string $type, int $createdBy): int {
        $stmt = $this->db->prepare("INSERT INTO meetings (title, description, scheduled_at, duration_minutes, meeting_link, type, created_by) 
                                    VALUES (:title, :description, :scheduled_at, :duration_minutes, :meeting_link, :type, :created_by)");
        $stmt->execute([
            'title'            => $title,
            'description'      => $description,
            'scheduled_at'     => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'meeting_link'     => $meetingLink,
            'type'             => $type,
            'created_by'       => $createdBy
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Mark participant attendance status and award points for attendance.
     */
    public function recordAttendance(int $meetingId, int $userId, string $status, ?string $excuseReason = null): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO meeting_attendance (meeting_id, user_id, status, excuse_reason) 
                                        VALUES (:meeting_id, :user_id, :status, :excuse_reason) 
                                        ON DUPLICATE KEY UPDATE status = :status_up, excuse_reason = :excuse_up");
            $stmt->execute([
                'meeting_id'    => $meetingId,
                'user_id'       => $userId,
                'status'        => $status,
                'excuse_reason' => $excuseReason,
                'status_up'     => $status,
                'excuse_up'     => $excuseReason
            ]);

            // Add point triggers on attendance
            if ($status === 'Present') {
                $stmtPoints = $this->db->prepare("UPDATE users SET reward_points = reward_points + :points WHERE id = :user_id");
                $stmtPoints->execute([
                    'points'  => POINTS_MEETING_ATTENDED,
                    'user_id' => $userId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
