<?php
// src/Models/Task.php

namespace CyberKavach\Nexus\Models;

use CyberKavach\Nexus\Config\Database;
use PDO;

class Task {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a task assigned to a team scope.
     */
    public function create(int $workspaceId, string $title, ?string $description, ?string $dueDate, int $pointsValue, string $targetTeam, int $reporterId): int {
        $stmt = $this->db->prepare("INSERT INTO tasks (workspace_id, title, description, due_date, points_value, target_team, reporter_id) 
                                    VALUES (:workspace_id, :title, :description, :due_date, :points_value, :target_team, :reporter_id)");
        $stmt->execute([
            'workspace_id' => $workspaceId,
            'title'        => $title,
            'description'  => $description,
            'due_date'     => $dueDate,
            'points_value' => $pointsValue,
            'target_team'  => $targetTeam,
            'reporter_id'  => $reporterId
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update the task status, and automatically award points if moving to 'Done'.
     */
    public function updateStatus(int $taskId, string $newStatus): bool {
        // Fetch current status, points, and assignee to avoid duplicate point awards
        $stmt = $this->db->prepare("SELECT status, points_value, assignee_id, title FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $taskId]);
        $task = $stmt->fetch();

        if (!$task) {
            return false;
        }

        $oldStatus  = $task['status'];
        $assigneeId = $task['assignee_id'];
        $points     = (int)$task['points_value'];
        $title      = $task['title'];

        $this->db->beginTransaction();
        try {
            // 1. Update task status
            $stmtUpdate = $this->db->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            $stmtUpdate->execute(['status' => $newStatus, 'id' => $taskId]);

            // 2. Award points if status shifts to 'Done' from another status
            if ($newStatus === 'Done' && $oldStatus !== 'Done' && $assigneeId !== null) {
                // Log contribution ledger
                $stmtCont = $this->db->prepare("INSERT INTO task_contributions (task_id, user_id, points_awarded, reason) 
                                                VALUES (:task_id, :user_id, :points_awarded, :reason)");
                $stmtCont->execute([
                    'task_id'        => $taskId,
                    'user_id'        => $assigneeId,
                    'points_awarded' => $points,
                    'reason'         => "Completed task: '{$title}'"
                ]);

                // Update users points ledger
                $stmtPoints = $this->db->prepare("UPDATE users SET reward_points = reward_points + :points WHERE id = :user_id");
                $stmtPoints->execute(['points' => $points, 'user_id' => $assigneeId]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
