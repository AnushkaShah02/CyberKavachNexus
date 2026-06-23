<?php
// views/components/navbar.php

use CyberKavach\Nexus\Helpers\SecurityHelper;

$user = $_SESSION['user'] ?? null;
$username = $user['username'] ?? 'User';
$roleName = $user['role_name'] ?? 'Guest Participant';
$points = $user['reward_points'] ?? 0;

// Dynamic Initials generator
$initials = '';
$parts = explode(' ', $username);
foreach ($parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
    }
}
$initials = substr($initials, 0, 2) ?: 'U';
?>
<nav class="navbar">
    <div class="navbar-greeting">
        <span style="color: var(--text-muted); font-size: 0.9rem;">Welcome Back,</span>
        <h3 style="font-size: 1.15rem; font-weight: 600;"><?= SecurityHelper::escape($username) ?></h3>
    </div>

    <div class="navbar-actions">
        <!-- Reward Points Balance -->
        <div class="reward-points-badge" title="My Reward Points Balance">
            <i data-lucide="trophy" style="width: 18px; height: 18px;"></i>
            <span><?= (int)$points ?> Points</span>
        </div>

        <!-- Role Pill Indicator -->
        <div class="user-badge" style="gap: 0.5rem; font-size: 0.85rem; padding: 0.4rem 0.8rem; background: var(--bg-surface-hover);">
            <i data-lucide="shield" style="width: 14px; height: 14px; color: var(--color-primary);"></i>
            <span><?= SecurityHelper::escape($roleName) ?></span>
        </div>

        <!-- Avatar Initials -->
        <div class="user-avatar" title="<?= SecurityHelper::escape($username) ?> (<?= SecurityHelper::escape($roleName) ?>)">
            <?= SecurityHelper::escape($initials) ?>
        </div>
    </div>
</nav>
