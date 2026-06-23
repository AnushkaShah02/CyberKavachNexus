-- CyberKavach Nexus Phase 3 Migration
-- Adds profile fields to users table (safe ALTER — does not drop existing data)
-- Run once in phpMyAdmin or MySQL CLI

USE `cyber_kavach_db`;

-- Add profile fields to users table
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `full_name`         VARCHAR(120) NULL AFTER `username`,
    ADD COLUMN IF NOT EXISTS `department`        VARCHAR(100) NULL AFTER `full_name`,
    ADD COLUMN IF NOT EXISTS `current_semester`  TINYINT UNSIGNED NULL AFTER `department`,
    ADD COLUMN IF NOT EXISTS `current_year`      TINYINT UNSIGNED NULL AFTER `current_semester`,
    ADD COLUMN IF NOT EXISTS `current_position`  VARCHAR(100) NULL AFTER `current_year`,
    ADD COLUMN IF NOT EXISTS `phone`             VARCHAR(20) NULL AFTER `current_position`,
    ADD COLUMN IF NOT EXISTS `profile_photo`     VARCHAR(255) NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `joined_at`         DATE NULL AFTER `profile_photo`;

-- Back-fill sensible defaults for existing demo users
UPDATE `users` SET
    `full_name`        = 'Dr. Ramesh Prasad',
    `department`       = 'Computer Science & Engineering',
    `current_semester` = NULL,
    `current_year`     = NULL,
    `current_position` = 'Faculty Coordinator',
    `phone`            = '+91-98765-00001',
    `joined_at`        = '2022-07-01'
WHERE `email` = 'ramesh.faculty@cyberkavach.org';

UPDATE `users` SET
    `full_name`        = 'Aarav Mehta',
    `department`       = 'Computer Science & Engineering',
    `current_semester` = 5,
    `current_year`     = 3,
    `current_position` = 'Student Coordinator',
    `phone`            = '+91-98765-00002',
    `joined_at`        = '2023-07-15'
WHERE `email` = 'aarav.student@cyberkavach.org';

UPDATE `users` SET
    `full_name`        = 'Neha Sharma',
    `department`       = 'Information Technology',
    `current_semester` = 3,
    `current_year`     = 2,
    `current_position` = 'Club Member',
    `phone`            = '+91-98765-00003',
    `joined_at`        = '2024-01-10'
WHERE `email` = 'neha.member@cyberkavach.org';
