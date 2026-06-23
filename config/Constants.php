<?php
// config/constants.php

namespace CyberKavach\Nexus\Config;

// Define System Role IDs (aligns with schema.sql seeds)
define('ROLE_FACULTY_COORDINATOR', 1);
define('ROLE_STUDENT_COORDINATOR', 2);
define('ROLE_TECH_COORDINATOR', 3);
define('ROLE_CONTENT_COORDINATOR', 4);
define('ROLE_SOCIAL_MEDIA_COORDINATOR', 5);
define('ROLE_CLUB_MEMBER', 6);
define('ROLE_GUEST_PARTICIPANT', 7);

// Points Allocations
define('POINTS_TASK_COMPLETED', 15);
define('POINTS_EVENT_ATTENDED', 10);
define('POINTS_MEETING_ATTENDED', 5);
define('POINTS_COORDINATOR_BONUS', 25);

// Event Contribution Roles
define('CONTRIBUTION_ROLES', [
    'Technical Team',
    'Content Team',
    'Design Team',
    'Photography',
    'Social Media',
    'Registration Team',
    'Volunteer Team'
]);

// Map Contribution Roles to default channel scopes in workspace
define('CONTRIBUTION_CHANNEL_MAP', [
    'Technical Team'     => ['general', 'technical'],
    'Content Team'       => ['general', 'content'],
    'Design Team'        => ['general', 'design'],
    'Photography'        => ['general', 'design', 'social-media'],
    'Social Media'       => ['general', 'social-media'],
    'Registration Team'  => ['general', 'registration'],
    'Volunteer Team'     => ['general', 'volunteers']
]);

// Path Definitions
define('BASE_DIR', dirname(__DIR__));
define('STORAGE_DIR', BASE_DIR . '/public/assets/storage');
define('QR_STORAGE_DIR', STORAGE_DIR . '/qr_codes');
define('CERTIFICATE_STORAGE_DIR', STORAGE_DIR . '/certificates');
