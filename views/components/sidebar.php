<?php
// views/components/sidebar.php

use CyberKavach\Nexus\Helpers\SecurityHelper;

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

$user = $_SESSION['user'] ?? [];

$activePage = '';

if (strpos($currentUri, 'dashboard') !== false) {
    $activePage = 'dashboard';
} elseif (strpos($currentUri, 'events') !== false) {
    $activePage = 'events';
} elseif (strpos($currentUri, 'workspace') !== false) {
    $activePage = 'workspace';
} elseif (strpos($currentUri, 'event_attendance') !== false) {
    $activePage = 'event_attendance';
} elseif (strpos($currentUri, 'participants') !== false) {
    $activePage = 'participants';
} elseif (strpos($currentUri, 'verify') !== false) {
    $activePage = 'verify';
}elseif (strpos($currentUri, 'certificates') !== false) {
    $activePage = 'certificates';
}


// Helper to assert active styles on matching pages
function getMenuClass(string $targetPage, string $activePage): string {
    return ($targetPage === $activePage) ? 'menu-item active' : 'menu-item';
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i data-lucide="shield-check" style="color: var(--color-primary); width: 28px; height: 28px;"></i>
        <span>Kavach Nexus</span>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?= getMenuClass('dashboard', $activePage) ?>">
            <a href="<?= SecurityHelper::escape(SecurityHelper::asset('views/pages/dashboard.php')) ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?= getMenuClass('events', $activePage) ?>">
            <a href="<?= SecurityHelper::asset('views/pages/events.php') ?>">
                <i data-lucide="calendar"></i>
                <span>Events</span>
            </a>
        </li>
        <li class="<?= getMenuClass('workspace', $activePage) ?>">
            <a href="<?= SecurityHelper::asset('views/pages/workspace.php') ?>">
                <i data-lucide="folder-kanban"></i>
                <span>Workspaces</span>
            </a>
        </li>

        <?php if($user['role_name'] !== 'Faculty Coordinator'): ?>
        
        <li class="<?= getMenuClass('my_tasks', $activePage) ?>">
    <a href="<?= SecurityHelper::asset('views/pages/my_tasks.php') ?>">
        <i data-lucide="users"></i>
        <span>My Tasks</span>
    </a>
</li>

<?php endif; ?>

<?php if(
    $user['role_name'] === 'Faculty Coordinator'
    ||
    $user['role_name'] === 'Student Coordinator'
    ||
    $user['role_name'] === 'Tech Coordinator'
): ?>

<li class="<?= getMenuClass('participants', $activePage) ?>">
    <a href="<?= SecurityHelper::asset('views/pages/participants.php') ?>">
        <i data-lucide="user-check"></i>
        <span>Participants</span>
    </a>
</li>

<?php endif; ?>

<?php if(
    $user['role_name'] === 'Faculty Coordinator'
    ||
    $user['role_name'] === 'Student Coordinator'
    ||
    $user['role_name'] === 'Tech Coordinator'
): ?>

<li class="<?= getMenuClass('event_attendance', $activePage) ?>">
    <a href="<?= SecurityHelper::asset('views/pages/event_attendance.php') ?>">
        <i data-lucide="clipboard-check"></i>
        <span>Event Attendance</span>
    </a>
</li>

<?php endif; ?>

<?php if(
    $user['role_name'] === 'Faculty Coordinator'
    ||
    $user['role_name'] === 'Student Coordinator'
    ||
    $user['role_name'] === 'Tech Coordinator'
): ?>

<li class="<?= getMenuClass('certificates', $activePage) ?>">
    <a href="<?= SecurityHelper::asset('views/pages/certificates.php') ?>">
        <i data-lucide="award"></i>
        <span>Certificates</span>
    </a>
</li>

<?php endif; ?>



        <li class="<?= getMenuClass('verify', $activePage) ?>">
            <a href="<?= SecurityHelper::asset('views/pages/club_meetings.php') ?>">
                <i data-lucide="award"></i>
                <span>Club Meetings</span>
            </a>
        </li>
    </ul>

    <!-- Bottom Actions / Collapse & Logout -->
    <ul class="sidebar-menu" style="margin-top: auto; border-top: 1px solid var(--color-border); padding-top: 1rem;">
        <li class="menu-item">
            <a href="javascript:void(0);" id="sidebarToggle">
                <i data-lucide="chevron-left" id="toggleIcon"></i>
                <span>Collapse Sidebar</span>
            </a>
        </li>
        <li class="menu-item" style="margin-bottom: 1rem;">
            <a href="javascript:void(0);" id="logoutBtn" style="color: var(--color-danger);">
                <i data-lucide="log-out"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const toggleIcon = document.getElementById('toggleIcon');
    const mainContent = document.querySelector('.main-content');

    // Sidebar collapse state handling
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        if (mainContent) {
            mainContent.classList.toggle('collapsed');
        }

        // Toggle Icons
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.setAttribute('data-lucide', 'chevron-right');
            toggleBtn.querySelector('span').style.display = 'none';
        } else {
            toggleIcon.setAttribute('data-lucide', 'chevron-left');
            toggleBtn.querySelector('span').style.display = 'inline';
        }
        if (window.lucide) {
            lucide.createIcons();
        }
    });

    // AJAX logout handling
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('<?= SecurityHelper::escape(SecurityHelper::asset("api/auth.php?action=logout")) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = '<?= SecurityHelper::escape(SecurityHelper::asset("views/pages/login.php")) ?>';
                } else {
                    alert('Logout failed: ' + result.message);
                }
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '<?= SecurityHelper::escape(SecurityHelper::asset("views/pages/login.php")) ?>';
            }
        });
    }
});
</script>
