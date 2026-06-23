<?php
// src/Models/Event.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class Event {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new event draft.
     */
    public function create(string $title, string $description, string $startTime, string $endTime, string $location, int $capacity, int $createdBy): int {
        $stmt = $this->db->prepare("INSERT INTO events (title, description, start_time, end_time, location, capacity, created_by) 
                                    VALUES (:title, :description, :start_time, :end_time, :location, :capacity, :created_by)");
        $stmt->execute([
            'title'       => $title,
            'description' => $description,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'location'    => $location,
            'capacity'    => $capacity,
            'created_by'  => $createdBy
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Log an approval entry and check if we need to auto-provision workspace.
     */
    public function submitApproval(int $eventId, int $approverId, int $approverRoleId, string $status, ?string $comments): bool {
        $this->db->beginTransaction();
        try {
            // Write approval ledger entry
            $stmt = $this->db->prepare("INSERT INTO event_approvals (event_id, approver_id, approver_role_id, status, comments) 
                                        VALUES (:event_id, :approver_id, :approver_role_id, :status, :comments)");
            $stmt->execute([
                'event_id'         => $eventId,
                'approver_id'      => $approverId,
                'approver_role_id' => $approverRoleId,
                'status'           => $status,
                'comments'         => $comments
            ]);

            // Update main status based on coordinator inputs
            // In our system, Faculty Coordinator (id=1) provides final approved status.
            $newStatus = ($status === 'Rejected') ? 'Rejected' : (($approverRoleId == ROLE_FACULTY_COORDINATOR) ? 'Approved' : 'Pending Approval');
            
            $stmtUpdate = $this->db->prepare("UPDATE events SET status = :status WHERE id = :event_id");
            $stmtUpdate->execute(['status' => $newStatus, 'event_id' => $eventId]);

            // Auto-provision workspace if status moves to Approved
            if ($newStatus === 'Approved') {
                $this->provisionWorkspace($eventId);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Set up collaboration workspace, channels, and AI context logs for approved event.
     */
    private function provisionWorkspace(int $eventId): void {
        // Fetch event details to name workspace
        $stmt = $this->db->prepare("SELECT title FROM events WHERE id = :event_id");
        $stmt->execute(['event_id' => $eventId]);
        $event = $stmt->fetch();
        $name = ($event['title'] ?? 'Event') . ' Workspace';

        // 1. Create workspace
        $stmtWork = $this->db->prepare("INSERT IGNORE INTO event_workspaces (event_id, name) VALUES (:event_id, :name)");
        $stmtWork->execute(['event_id' => $eventId, 'name' => $name]);
        $workspaceId = (int)$this->db->lastInsertId();

        if ($workspaceId === 0) {
            // Workspace already exists, retrieve ID
            $stmtGet = $this->db->prepare("SELECT id FROM event_workspaces WHERE event_id = :event_id");
            $stmtGet->execute(['event_id' => $eventId]);
            $workspaceId = (int)($stmtGet->fetch()['id'] ?? 0);
        }

        if ($workspaceId > 0) {
            // 2. Pre-populate channels representing default teams
            $channels = ['general', 'technical', 'content', 'design', 'social-media', 'registration', 'volunteers'];
            $stmtChan = $this->db->prepare("INSERT IGNORE INTO workspace_channels (workspace_id, name) VALUES (:workspace_id, :name)");
            foreach ($channels as $chan) {
                $stmtChan->execute(['workspace_id' => $workspaceId, 'name' => $chan]);
            }
        }
    }

    /**
     * Register a user to join an event with a specific contribution role.
     */
    public function registerUser(int $eventId, int $userId, string $contributionRole, string $qrHash): bool {
        $stmt = $this->db->prepare("INSERT INTO event_registrations (event_id, user_id, contribution_role, qr_code_hash) 
                                    VALUES (:event_id, :user_id, :contribution_role, :qr_code_hash)");
        return $stmt->execute([
            'event_id'          => $eventId,
            'user_id'           => $userId,
            'contribution_role' => $contributionRole,
            'qr_code_hash'      => $qrHash
        ]);
    }
}
