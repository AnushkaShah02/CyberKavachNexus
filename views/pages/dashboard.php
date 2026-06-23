<?php
// views/pages/dashboard.php — Phase 3: Member Profile Hub

use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// ── Auth guard ────────────────────────────────────────────────
$userSession = AuthMiddleware::handle();
$userId      = (int)$userSession['id'];

$db = Database::getConnection();

$stmtMeeting = $db->prepare("
SELECT *
FROM club_meetings
WHERE
    meeting_date >= NOW()
ORDER BY meeting_date ASC
LIMIT 1
");

$stmtMeeting->execute();

$upcomingMeeting =
    $stmtMeeting->fetch();

// ── 1. Full user profile row ──────────────────────────────────
$stmtProfile = $db->prepare(
    "SELECT u.*, r.name AS role_name
     FROM users u JOIN roles r ON u.role_id = r.id
     WHERE u.id = :id"
);
$stmtProfile->execute(['id' => $userId]);
$profile = $stmtProfile->fetch();

// Update session points if they drifted
$_SESSION['user']['reward_points'] = (int)($profile['reward_points'] ?? 0);

// Convenience vars
$fullName    = $profile['full_name'] ?: $profile['username'];
$department  = $profile['department'] ?: '—';
$semester    = $profile['current_semester'] ? 'Sem ' . $profile['current_semester'] : null;
$year        = $profile['current_year']     ? 'Year ' . $profile['current_year']    : null;
$position    = $profile['current_position'] ?: ($profile['role_name'] ?? '—');
$phone       = $profile['phone'] ?: '—';
$email       = $profile['email'];
$xp          = (int)$profile['reward_points'];
$joinedAt    = $profile['joined_at'] ?? null;
$photoPath   = $profile['profile_photo'] ?? null;

// Initials avatar fallback
$initials = '';
foreach (explode(' ', $fullName) as $p) {
    if ($p !== '') $initials .= strtoupper($p[0]);
}
$initials = substr($initials, 0, 2) ?: 'CK';

// ── 2. Stats ──────────────────────────────────────────────────
$stmtEvents = $db->prepare(
    "SELECT COUNT(*) AS c FROM event_registrations WHERE user_id = :uid"
);
$stmtEvents->execute(['uid' => $userId]);
$eventsCount = (int)($stmtEvents->fetch()['c'] ?? 0);

$stmtMeetings = $db->prepare(
    "SELECT COUNT(*) AS c FROM meeting_attendance WHERE user_id = :uid AND status = 'Present'"
);
$stmtMeetings->execute(['uid' => $userId]);
$meetingsCount = (int)($stmtMeetings->fetch()['c'] ?? 0);

$certsCount = 0;

$wsCount = 0;

$stmtTasks = $db->prepare(
    "SELECT COUNT(*) AS c FROM tasks WHERE assignee_id = :uid AND status = 'Done'"
);
$stmtTasks->execute(['uid' => $userId]);
$tasksCount = (int)($stmtTasks->fetch()['c'] ?? 0);

// ── 3. Event history (portfolio) ──────────────────────────────
$eventHistory = [];

// ── 4. Pending tasks (smart queue) ────────────────────────────
$stmtQueue = $db->prepare(
    "SELECT t.*, e.title AS event_title
     FROM tasks t
     JOIN event_workspaces w ON t.workspace_id = w.id
     JOIN events e ON w.event_id = e.id
     WHERE t.assignee_id = :uid AND t.status != 'Done'
     ORDER BY t.due_date ASC LIMIT 5"
);
$stmtQueue->execute(['uid' => $userId]);
$tasksQueue = $stmtQueue->fetchAll();

// ── 5. Upcoming events ────────────────────────────────────────
$stmtUpcoming = $db->prepare(
    "SELECT * FROM events
     WHERE start_time > NOW() AND status = 'Approved'
     ORDER BY start_time ASC LIMIT 4"
);
$stmtUpcoming->execute();
$upcomingEvents = $stmtUpcoming->fetchAll();

// ── 6. Upcoming meetings ──────────────────────────────────────
$stmtMtgs = $db->prepare("
    SELECT
    title,
    agenda,
    meeting_date
FROM club_meetings
WHERE meeting_date > NOW()
ORDER BY meeting_date ASC
    LIMIT 4
");

$stmtMtgs->execute();

$upcomingMeetings = $stmtMtgs->fetchAll();

// ── 7. XP tier logic ─────────────────────────────────────────
$tiers = [
    ['label' => 'Recruit',    'min' => 0,    'max' => 99],
    ['label' => 'Member',     'min' => 100,  'max' => 299],
    ['label' => 'Contributor','min' => 300,  'max' => 599],
    ['label' => 'Specialist', 'min' => 600,  'max' => 999],
    ['label' => 'Expert',     'min' => 1000, 'max' => 1999],
    ['label' => 'Champion',   'min' => 2000, 'max' => PHP_INT_MAX],
];
$currentTier = $tiers[0]; $nextTier = $tiers[1];
foreach ($tiers as $i => $t) {
    if ($xp >= $t['min']) {
        $currentTier = $t;
        $nextTier    = $tiers[$i + 1] ?? null;
    }
}
$xpInTier  = $xp - $currentTier['min'];
$xpNeeded  = $nextTier ? ($nextTier['min'] - $currentTier['min']) : 1;
$xpPercent = $nextTier ? min(100, round($xpInTier / $xpNeeded * 100)) : 100;

// ── 8. Achievement badges ─────────────────────────────────────
$badges = [
    [
        'name'   => 'Top Volunteer',
        'desc'   => 'Participated in 3+ events as Volunteer Team',
        'icon'   => '🤝',
        'color'  => 'green',
        'earned' => $eventsCount >= 3,
    ],
    [
        'name'   => 'Attendance Champion',
        'desc'   => 'Attended 5+ club meetings',
        'icon'   => '🏅',
        'color'  => 'gold',
        'earned' => $meetingsCount >= 5,
    ],
    [
        'name'   => 'Event Leader',
        'desc'   => 'Led or coordinated a club event',
        'icon'   => '⚡',
        'color'  => 'indigo',
        'earned' => false, // extend with coordinator check
    ],
    [
        'name'   => 'XP Pioneer',
        'desc'   => 'Earned 100+ reward points',
        'icon'   => '🌟',
        'color'  => 'gold',
        'earned' => $xp >= 100,
    ],
    [
        'name'   => 'Certificate Collector',
        'desc'   => 'Received 2+ certificates',
        'icon'   => '🎓',
        'color'  => 'violet',
        'earned' => $certsCount >= 2,
    ],
    [
        'name'   => 'Task Master',
        'desc'   => 'Completed 10+ workspace tasks',
        'icon'   => '🔧',
        'color'  => 'cyan',
        'earned' => $tasksCount >= 10,
    ],
];

// ── 9. Club journey steps ─────────────────────────────────────
$journeySteps = ['Joined', 'Volunteer', 'Contributor', 'Coordinator', 'Lead', 'President'];
$currentStepIdx = 1; // default — could be driven by position field in future

// ── 10. Asset base URL ────────────────────────────────────────
$assetBase = SecurityHelper::asset('');

$pageTitle = 'My Profile — CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>
<link rel="stylesheet" href="<?= SecurityHelper::escape(SecurityHelper::asset('assets/css/dashboard.css')) ?>">

<div class="app-container">

    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content" style="gap: 1.75rem;">

        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <!-- ══ PROFILE HERO ═══════════════════════════════════════ -->
        <div class="profile-hero">

            <!-- Cyber-grid banner -->
            <div class="profile-hero-banner"></div>

            <!-- Avatar + name row -->
            <div class="profile-hero-body">

                <div class="avatar-ring">
                    <div class="avatar-img">
                        <?php if ($photoPath && file_exists(dirname(__DIR__, 2) . '/public/assets/storage/' . $photoPath)): ?>
                            <img src="<?= SecurityHelper::escape(SecurityHelper::asset('assets/storage/' . $photoPath)) ?>" alt="Profile Photo">
                        <?php else: ?>
                            <?= SecurityHelper::escape($initials) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-meta">
                    <h2><?= SecurityHelper::escape($fullName) ?></h2>

                    <div class="position-chip">
                        <i data-lucide="shield" style="width:13px;height:13px;"></i>
                        <?= SecurityHelper::escape($position) ?>
                    </div>

                    <p class="dept-line">
                        <i data-lucide="building-2" style="width:14px;height:14px;color:var(--color-secondary);"></i>
                        <?= SecurityHelper::escape($department) ?>
                        <?php if ($semester || $year): ?>
                            <span style="color:var(--color-border)">|</span>
                            <?= SecurityHelper::escape(implode(' · ', array_filter([$year, $semester]))) ?>
                        <?php endif; ?>
                        <?php if ($joinedAt): ?>
                            <span style="color:var(--color-border)">|</span>
                            <i data-lucide="calendar-days" style="width:12px;height:12px;"></i>
                            Since <?= date('M Y', strtotime($joinedAt)) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="profile-hero-actions">
                    <span class="btn btn-secondary" style="font-size:.8rem;padding:.6rem 1.1rem;cursor:default;">
                        <i data-lucide="trophy" style="width:15px;height:15px;color:var(--color-warning);"></i>
                        <?= number_format($xp) ?> XP · <?= SecurityHelper::escape($currentTier['label']) ?>
                    </span>
                </div>
            </div>

            <!-- Contact pills -->
            <div class="contact-row">
                <span class="contact-pill">
                    <i data-lucide="mail" style="width:14px;height:14px;"></i>
                    <?= SecurityHelper::escape($email) ?>
                </span>
                <span class="contact-pill">
                    <i data-lucide="phone" style="width:14px;height:14px;"></i>
                    <?= SecurityHelper::escape($phone) ?>
                </span>
                <span class="contact-pill">
                    <i data-lucide="user-check" style="width:14px;height:14px;"></i>
                    <?= SecurityHelper::escape($profile['role_name'] ?? 'Member') ?>
                </span>
                <span class="contact-pill" style="margin-left:auto;">
                    <i data-lucide="circle-dot" style="width:14px;height:14px;color:var(--color-success);"></i>
                    Active Member
                </span>
            </div>

            <!-- XP progress bar -->
            <div class="xp-bar-wrap">
                <div class="xp-label-row">
                    <span>
                        <strong><?= SecurityHelper::escape($currentTier['label']) ?></strong>
                        <?php if ($nextTier): ?>
                            → <?= SecurityHelper::escape($nextTier['label']) ?>
                        <?php else: ?>
                            (Max Tier)
                        <?php endif; ?>
                    </span>
                    <span>
                        <strong><?= number_format($xp) ?> XP</strong>
                        <?php if ($nextTier): ?>
                            / <?= number_format($nextTier['min']) ?> XP
                        <?php endif; ?>
                    </span>
                </div>
                <div class="xp-track">
                    <div class="xp-fill" id="xpFill" data-pct="<?= $xpPercent ?>"></div>
                </div>
            </div>
        </div>

        <!-- ══ STATISTICS GRID ════════════════════════════════════ -->
        <div class="stat-grid">
            <?php
            $stats = [
                ['label'=>'Events Joined',    'value'=>$eventsCount,  'icon'=>'calendar-check', 'color'=>'99,102,241',  'hex'=>'#6366f1'],
                ['label'=>'Meetings Attended','value'=>$meetingsCount,'icon'=>'video',           'color'=>'6,182,212',   'hex'=>'#06b6d4'],
                ['label'=>'Certificates',     'value'=>$certsCount,   'icon'=>'award',           'color'=>'245,158,11',  'hex'=>'#f59e0b'],
                ['label'=>'Workspaces',       'value'=>$wsCount,      'icon'=>'folder-open',     'color'=>'168,85,247',  'hex'=>'#a855f7'],
                ['label'=>'Tasks Done',       'value'=>$tasksCount,   'icon'=>'check-circle-2',  'color'=>'16,185,129',  'hex'=>'#10b981'],
            ];
            foreach ($stats as $s): ?>
            <div class="stat-card" style="--stat-rgb:<?= $s['color'] ?>;--stat-color:<?= $s['hex'] ?>;">
                <div class="stat-icon" style="background:rgba(<?= $s['color'] ?>,.12);">
                    <i data-lucide="<?= $s['icon'] ?>" style="color:<?= $s['hex'] ?>;width:18px;height:18px;"></i>
                </div>
                <div class="stat-value count-up" data-target="<?= $s['value'] ?>">0</div>
                <div class="stat-label"><?= SecurityHelper::escape($s['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ══ CLUB JOURNEY ══════════════════════════════════════ -->
        <section>
            <div class="section-head">
                <h3>
                    <i data-lucide="git-branch"></i>
                    Club Journey
                </h3>
            </div>
            <div class="kavach-card" style="padding:1.5rem 2rem;">
                <div class="journey-track">
                    <?php foreach ($journeySteps as $i => $step):
                        $cls = $i < $currentStepIdx ? 'done' : ($i === $currentStepIdx ? 'current' : '');
                        $icons = ['🎯','🤝','⭐','🛡️','🚀','👑'];
                    ?>
                    <div class="journey-step <?= $cls ?>">
                        <div class="journey-node"><?= $icons[$i] ?? '●' ?></div>
                        <div class="journey-label"><?= SecurityHelper::escape($step) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══ ACHIEVEMENT BADGES ════════════════════════════════ -->
        <section>
            <div class="section-head">
                <h3>
                    <i data-lucide="star"></i>
                    Achievements
                </h3>
                <span class="section-badge">
                    <?= count(array_filter($badges, fn($b) => $b['earned'])) ?> / <?= count($badges) ?> Earned
                </span>
            </div>
            <div class="badge-grid">
                <?php foreach ($badges as $badge): ?>
                <div class="badge-card <?= $badge['earned'] ? 'earned' : 'locked' ?>">
                    <div class="badge-icon-wrap <?= SecurityHelper::escape($badge['color']) ?>">
                        <?= $badge['icon'] ?>
                    </div>
                    <div class="badge-name"><?= SecurityHelper::escape($badge['name']) ?></div>
                    <div class="badge-desc"><?= SecurityHelper::escape($badge['desc']) ?></div>
                    <?php if ($badge['earned']): ?>
                        <span class="badge-earned-tag">✓ Earned</span>
                    <?php else: ?>
                        <span style="font-size:.7rem;color:var(--text-dark);">Locked</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ══ MAIN SPLIT — History + Productivity ═══════════════ -->
<div class="split-grid"></div>

            <!-- ── Event History Timeline ─────────────────────── -->
            <section>
                <div class="section-head">
                    <h3>
                        <i data-lucide="history"></i>
                        Event History
                    </h3>
                    <span class="section-badge"><?= count($eventHistory) ?> Events</span>
                </div>

                <?php if (empty($eventHistory)): ?>
                <div class="kavach-card" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                    <i data-lucide="calendar-x" style="width:40px;height:40px;margin-bottom:1rem;opacity:.4;"></i>
                    <p>No event history yet.</p>
                    <p style="font-size:.8rem;margin-top:.4rem;">Register for an upcoming event to start building your portfolio.</p>
                </div>
                <?php else: ?>
                <div class="kavach-card" style="padding:1.25rem 1.5rem;">
                    <div class="event-timeline">
                        <?php foreach ($eventHistory as $eh): ?>
                        <div class="timeline-item">
                            <div class="tl-dot">
                                <i data-lucide="calendar"></i>
                            </div>
                            <div class="tl-body">
                                <div class="tl-title"><?= SecurityHelper::escape($eh['title']) ?></div>
                                <div class="tl-meta">
                                    <span class="tl-chip role"><?= SecurityHelper::escape($eh['contribution_role']) ?></span>
                                    <span class="tl-chip date"><?= date('M d, Y', strtotime($eh['start_time'])) ?></span>
                                    <?php if ($eh['has_cert']): ?>
                                    <span class="tl-chip cert">🎓 Certified</span>
                                    <?php endif; ?>
                                    <?php if ($eh['attendance_status'] === 'Attended'): ?>
                                    <span class="tl-chip" style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);color:#6ee7b7;">✓ Attended</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <!-- ── Productivity Panel ─────────────────────────── -->
            <div style="display:flex;flex-direction:column;gap:1.5rem;">

                <!-- Upcoming Tasks -->
                <section>
                    <div class="section-head">
                        <h3>
                            <i data-lucide="list-todo"></i>
                            Pending Tasks
                        </h3>
                        <?php if (count($tasksQueue)): ?>
                        <span class="section-badge"><?= count($tasksQueue) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="kavach-card" style="padding:1rem 1.5rem;">
                        <?php if (empty($tasksQueue)): ?>
                        <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:1.5rem 0;">
                            ✅ No pending tasks. Excellent!
                        </p>
                        <?php else: ?>
                        <?php foreach ($tasksQueue as $t): ?>
                        <div class="productivity-item">
                            <div class="prod-icon" style="background:rgba(99,102,241,.1);">
                                <i data-lucide="check-square" style="color:var(--color-primary);"></i>
                            </div>
                            <div>
                                <div class="prod-label"><?= SecurityHelper::escape($t['title']) ?></div>
                                <div class="prod-sub">
                                    <span><?= SecurityHelper::escape($t['event_title']) ?></span>
                                    <span class="dot-sep"></span>
                                    <span style="color:<?= $t['status'] === 'In Review' ? 'var(--color-warning)' : 'var(--color-secondary)' ?>;"><?= SecurityHelper::escape($t['status']) ?></span>
                                    <?php if ($t['due_date']): ?>
                                    <span class="dot-sep"></span>
                                    <span>Due <?= date('M d', strtotime($t['due_date'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="margin-left:auto;font-size:.8rem;font-weight:700;color:var(--color-warning);white-space:nowrap;"><?= $t['points_value'] ?> XP</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Upcoming Events -->
                <section>
                    <div class="section-head">
                        <h3>
                            <i data-lucide="calendar"></i>
                            Upcoming Events
                        </h3>
                    </div>
                    <div class="kavach-card" style="padding:1rem 1.5rem;">
                        <?php if (empty($upcomingEvents)): ?>
                        <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:1.5rem 0;">
                            No upcoming events scheduled.
                        </p>
                        <?php else: ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                        <div class="productivity-item">
                            <div class="prod-icon" style="background:rgba(6,182,212,.1);">
                                <i data-lucide="zap" style="color:var(--color-secondary);"></i>
                            </div>
                            <div>
                                <div class="prod-label"><?= SecurityHelper::escape($ev['title']) ?></div>
                                <div class="prod-sub">
                                    <i data-lucide="map-pin" style="width:11px;height:11px;"></i>
                                    <span><?= SecurityHelper::escape($ev['location']) ?></span>
                                    <span class="dot-sep"></span>
                                    <span><?= date('M d, Y', strtotime($ev['start_time'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Upcoming Meetings -->
                <section>
                    <div class="section-head">
                        <h3>
                            <i data-lucide="users"></i>
                            Upcoming Meetings
                        </h3>
                    </div>
                    <div class="kavach-card" style="padding:1rem 1.5rem;">
                        <?php if (empty($upcomingMeetings)): ?>
                        <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:1.5rem 0;">
                            No meetings scheduled.
                        </p>
                        <?php else: ?>
                        <?php foreach ($upcomingMeetings as $m): ?>

<div class="productivity-item">
    <div class="prod-icon" style="background:rgba(168,85,247,.1);">
        <i data-lucide="video" style="color:var(--color-accent);"></i>
    </div>

    <div style="flex:1;">

        <div class="prod-label">
            <?= SecurityHelper::escape($m['title']) ?>
        </div>

        <div class="prod-sub">
            <span>
                <?= date('M d, Y · g:i A', strtotime($m['meeting_date'])) ?>
            </span>
        </div>

        <div style="
            margin-top:8px;
            color:#94a3b8;
            font-size:13px;
        ">
            <?= SecurityHelper::escape($m['agenda']) ?>
        </div>

    </div>
</div>

<?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </div>

    </main>
</div>

<script>
/* ── Animated stat counters ──────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {

    // XP progress bar
    const fill = document.getElementById('xpFill');
    if (fill) {
        setTimeout(() => { fill.style.width = fill.dataset.pct + '%'; }, 200);
    }

    // Animated number count-up
    document.querySelectorAll('.count-up[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target, 10);
        if (target === 0) { el.textContent = '0'; return; }
        const step     = Math.ceil(target / 60);
        let   current  = 0;
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current.toLocaleString();
            if (current >= target) clearInterval(timer);
        }, 18);
    });

    // Ensure Lucide icons render
    if (window.lucide) lucide.createIcons();
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>
