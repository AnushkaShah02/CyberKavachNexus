<?php
// views/pages/participants.php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();
$db = Database::getConnection();
$user = $_SESSION['user'];

if (
    $user['role_name'] !== 'Faculty Coordinator' &&
    $user['role_name'] !== 'Student Coordinator' &&
    $user['role_name'] !== 'Tech Coordinator'
) {
    die('Access Denied');
}

$stmt = $db->prepare("
    SELECT
        pr.*,
        e.title AS event_title,
        e.registration_type
    FROM participant_registrations pr
    JOIN events e ON pr.event_id = e.id
    ORDER BY pr.registered_at DESC
");
$stmt->execute();
$participants = $stmt->fetchAll();

$pageTitle = 'Participants Directory — CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<style>
.directory-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.5rem;
}

.directory-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 700;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
}

.directory-row {
    background: rgba(255,255,255,0.02);
    transition: var(--transition-smooth);
}

.directory-row:hover {
    background: rgba(255,255,255,0.05);
    transform: translateX(4px);
}

.directory-row td {
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

.directory-row td:first-child {
    border-top-left-radius: var(--radius-md);
    border-bottom-left-radius: var(--radius-md);
}

.directory-row td:last-child {
    border-top-right-radius: var(--radius-md);
    border-bottom-right-radius: var(--radius-md);
}

.participant-cell {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.participant-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.participant-info {
    display: flex;
    flex-direction: column;
}

.participant-name {
    font-weight: 600;
    color: var(--text-main);
    font-size: 0.95rem;
}

.participant-email {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.event-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.6rem;
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <div class="section-header animate-on-scroll">
            <div>
                <h1 class="page-title" style="font-size: 2rem; margin-bottom: 0.5rem;">Participants Directory</h1>
                <p style="color: var(--text-muted);">Manage event registrations and team formations.</p>
            </div>
        </div>

        <div class="glass-card animate-on-scroll" style="overflow-x: auto; padding: 1rem;">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Enrollment</th>
                        <th>Contact</th>
                        <th>Event</th>
                        <th>Team / Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($participants as $i => $p): 
                        // Generate Avatar Initials & Gradient
                        $initials = strtoupper(substr($p['participant_name'], 0, 1));
                        $gradients = [
                            'linear-gradient(135deg, #3b82f6, #8b5cf6)',
                            'linear-gradient(135deg, #10b981, #059669)',
                            'linear-gradient(135deg, #f59e0b, #d97706)',
                            'linear-gradient(135deg, #ec4899, #be185d)'
                        ];
                        $bg = $gradients[$i % 4];
                    ?>
                    <tr class="directory-row">
                        <td>
                            <div class="participant-cell">
                                <div class="participant-avatar" style="background: <?= $bg ?>;"><?= SecurityHelper::escape($initials) ?></div>
                                <div class="participant-info">
                                    <span class="participant-name"><?= SecurityHelper::escape($p['participant_name']) ?></span>
                                    <span class="participant-email">Reg: <?= date('d M, Y', strtotime($p['registered_at'])) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family: monospace; color: var(--text-muted); background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                <?= SecurityHelper::escape($p['enrollment_no']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="participant-info">
                                <span class="participant-name" style="font-size: 0.85rem; font-weight: 400;"><?= SecurityHelper::escape($p['email']) ?></span>
                                <span class="participant-email"><?= SecurityHelper::escape($p['phone']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="event-pill">
                                <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                                <?= SecurityHelper::escape($p['event_title']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($p['registration_type'] === 'Team'): ?>
                                <span class="chip warning" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                    <i data-lucide="users" style="width: 10px; height: 10px; margin-right: 2px;"></i>
                                    Team: <?= SecurityHelper::escape($p['team_name'] ?? 'N/A') ?>
                                </span>
                            <?php else: ?>
                                <span class="chip info" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                    <i data-lucide="user" style="width: 10px; height: 10px; margin-right: 2px;"></i>
                                    Individual
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['registration_type'] === 'Team'): ?>
                                <a href="<?= SecurityHelper::asset('views/pages/view_team.php?registration_id=' . $p['id']) ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">
                                    View Team
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>