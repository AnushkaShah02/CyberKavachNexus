-- CyberKavach Nexus Database Initialization Script
-- Target Database: cyber_kavach_db

-- CREATE DATABASE IF NOT EXISTS `cyber_kavach_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `cyber_kavach_db`;

-- Drop tables if they exist in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `task_contributions`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `chats`;
DROP TABLE IF EXISTS `workspace_channels`;
DROP TABLE IF EXISTS `event_workspaces`;
DROP TABLE IF EXISTS `leaves`;
DROP TABLE IF EXISTS `meeting_attendance`;
DROP TABLE IF EXISTS `meetings`;
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `team_members`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `event_approvals`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Roles table
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `status` ENUM('Active', 'Suspended', 'Pending') DEFAULT 'Active',
    `reward_points` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Events table
CREATE TABLE `events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `capacity` INT UNSIGNED DEFAULT 0,
    `status` ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected', 'Completed') DEFAULT 'Draft',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Event Approvals table
CREATE TABLE `event_approvals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `approver_id` INT UNSIGNED NOT NULL,
    `approver_role_id` INT UNSIGNED NOT NULL,
    `status` ENUM('Approved', 'Rejected') NOT NULL,
    `comments` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approver_role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Teams table
CREATE TABLE `teams` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Team Members table
CREATE TABLE `team_members` (
    `team_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` ENUM('Leader', 'Member') DEFAULT 'Member',
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`team_id`, `user_id`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Event Registrations (Contribution Role Map)
CREATE TABLE `event_registrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `contribution_role` ENUM('Technical Team', 'Content Team', 'Design Team', 'Photography', 'Social Media', 'Registration Team', 'Volunteer Team') NOT NULL,
    `qr_code_hash` VARCHAR(100) NOT NULL UNIQUE,
    `attendance_status` ENUM('Registered', 'Attended', 'Absent') DEFAULT 'Registered',
    `attended_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_event_user` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Certificates table
CREATE TABLE `certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `issue_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `type` ENUM('Participation', 'Winner', 'Runner-up', 'Coordinator') DEFAULT 'Participation',
    `verification_hash` VARCHAR(64) NOT NULL UNIQUE,
    `download_path` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Meetings table
CREATE TABLE `meetings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `duration_minutes` INT UNSIGNED DEFAULT 60,
    `meeting_link` VARCHAR(255) NULL,
    `type` ENUM('General Meetup', 'Core Committee', 'Departmental') NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Meeting Attendance table
CREATE TABLE `meeting_attendance` (
    `meeting_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('Present', 'Absent', 'Excused') DEFAULT 'Absent',
    `excuse_reason` TEXT NULL,
    PRIMARY KEY (`meeting_id`, `user_id`),
    FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Leaves table
CREATE TABLE `leaves` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `approved_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Event Workspaces table
CREATE TABLE `event_workspaces` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Workspace Channels table
CREATE TABLE `workspace_channels` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`workspace_id`) REFERENCES `event_workspaces`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_workspace_channel` (`workspace_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Chats table
CREATE TABLE `chats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `channel_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NULL,
    `message` TEXT NOT NULL,
    `is_ai` ENUM('0', '1') DEFAULT '0',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`channel_id`) REFERENCES `workspace_channels`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Tasks table
CREATE TABLE `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATETIME NULL,
    `status` ENUM('Todo', 'In Progress', 'In Review', 'Done') DEFAULT 'Todo',
    `assignee_id` INT UNSIGNED NULL,
    `reporter_id` INT UNSIGNED NOT NULL,
    `points_value` INT UNSIGNED DEFAULT 10,
    `target_team` ENUM('Technical Team', 'Content Team', 'Design Team', 'Photography', 'Social Media', 'Registration Team', 'Volunteer Team') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`workspace_id`) REFERENCES `event_workspaces`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assignee_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Task Contributions table
CREATE TABLE `task_contributions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `points_awarded` INT UNSIGNED NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `awarded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Notifications table
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` ENUM('0', '1') DEFAULT '0',
    `type` VARCHAR(50) DEFAULT 'general',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Audit Logs table
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default Roles
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Faculty Coordinator', 'Authorized faculty overseeing club events, financials, and major decisions.'),
(2, 'Student Coordinator', 'Student leader running operations, organizing core schedules, pre-approving events.'),
(3, 'Tech Coordinator', 'Directs technical challenges, server infrastructure, and code setups.'),
(4, 'Content Coordinator', 'Manages event writing, document drafts, and rule configurations.'),
(5, 'Social Media Coordinator', 'Directs club promotions, public outreach, and event banners.'),
(6, 'Club Member', 'Standard club member with permissions to join events and complete task queues.'),
(7, 'Guest Participant', 'External registered users taking part in single public events.');

-- Seed default Users (Password for all: Kavach@2026)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role_id`, `status`, `reward_points`) VALUES
('Dr. Ramesh Prasad', 'ramesh.faculty@cyberkavach.org', '$2y$10$nA2koX0JciDuR595//eYKO/uAMHLZdtJFdxMkefjJ3e8qexJpfCJm', 1, 'Active', 0),
('Aarav Mehta', 'aarav.student@cyberkavach.org', '$2y$10$nA2koX0JciDuR595//eYKO/uAMHLZdtJFdxMkefjJ3e8qexJpfCJm', 2, 'Active', 120),
('Neha Sharma', 'neha.member@cyberkavach.org', '$2y$10$nA2koX0JciDuR595//eYKO/uAMHLZdtJFdxMkefjJ3e8qexJpfCJm', 6, 'Active', 45);

