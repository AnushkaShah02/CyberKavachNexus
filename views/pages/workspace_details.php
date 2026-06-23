<?php
// views/pages/workspace_details.php

use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$user = $_SESSION['user'];

$db = Database::getConnection();

if(isset($_POST['assign_task'])){

    $stmtUserRole = $db->prepare("
        SELECT r.name
        FROM users u
        JOIN roles r
            ON u.role_id = r.id
        WHERE u.id = ?
    ");

    $stmtUserRole->execute([
        $_POST['assigned_to']
    ]);

    $selectedUser = $stmtUserRole->fetch();

    $stmt = $db->prepare("
        INSERT INTO event_tasks
        (
            event_id,
            assigned_to,
            role_name,
            task_title,
            priority,
            deadline
        )
        VALUES
        (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['event_id'],
        $_POST['assigned_to'],
        $selectedUser['name'],
        $_POST['task_title'],
        $_POST['priority'],
        $_POST['deadline']
    ]);

    header(
        'Location: '.$_SERVER['REQUEST_URI']
    );

    exit;
}

$stmtUsers = $db->prepare("
    SELECT
    u.id,
    u.full_name,
    r.name AS role_name
FROM users u
JOIN roles r
    ON u.role_id = r.id
ORDER BY u.full_name
");

$stmtUsers->execute();

$users = $stmtUsers->fetchAll();

if(isset($_POST['approve_task'])){

    $stmt = $db->prepare("
        UPDATE event_tasks
        SET
            status = 'Completed',
            completed_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['task_id']
    ]);

    header(
        'Location: '.$_SERVER['REQUEST_URI']
    );

    exit;
}

if(isset($_POST['send_message'])){

if(
    !SecurityHelper::verifyCsrfToken(
        $_POST['csrf_token'] ?? null
    )
){
    die('Invalid CSRF Token');
}

    $stmt = $db->prepare("
        INSERT INTO workspace_messages
        (
            workspace_id,
            user_id,
            message
        )
        VALUES
        (?, ?, ?)
    ");

    $stmt->execute([
        $_GET['id'],
        $user['id'],
        $_POST['message']
    ]);

    header(
        'Location: '.$_SERVER['REQUEST_URI']
    );
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("
    SELECT
    ew.*,
    e.title,
    e.location,
    e.start_time,
    e.status AS event_status
FROM event_workspaces ew
JOIN events e
    ON ew.event_id = e.id
WHERE ew.id = ?
");

$stmt->execute([$id]);

$workspace = $stmt->fetch();

$stmtMsg = $db->prepare("
    SELECT
        wm.*,
        u.full_name,
        r.name AS role_name
    FROM workspace_messages wm
    JOIN users u
        ON wm.user_id = u.id
    JOIN roles r
        ON u.role_id = r.id
    WHERE workspace_id = ?
    ORDER BY wm.created_at ASC
");

$stmtMsg->execute([
    $id
]);

$messages = $stmtMsg->fetchAll();

$stmtTasks = $db->prepare("
    SELECT
        et.*,
        u.full_name,
        r.name AS role_name
    FROM event_tasks et
    LEFT JOIN users u
        ON et.assigned_to = u.id
    LEFT JOIN roles r
        ON u.role_id = r.id
    WHERE et.event_id = ?
    ORDER BY deadline ASC
");

$stmtTasks->execute([
    $workspace['event_id']
]);

$eventTasks = $stmtTasks->fetchAll();

$stmtProgress = $db->prepare("
    SELECT
        role_name,
        COUNT(*) AS total_tasks,
        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_tasks
    FROM event_tasks
    WHERE event_id = ?
    GROUP BY role_name
");

$stmtProgress->execute([
    $workspace['event_id']
]);

$progress = $stmtProgress->fetchAll();

$pageTitle = 'Workspace: ' . SecurityHelper::escape($workspace['title']);

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<style>
/* Discord-style Workspace Layout */
.workspace-wrapper {
    display: grid;
    grid-template-columns: 240px 1fr 300px;
    min-height:700px;
    background: var(--bg-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-glow);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}

@media (max-width: 1200px) {
    .workspace-wrapper {
        grid-template-columns: 200px 1fr 250px;
    }
}
@media (max-width: 992px) {
    .workspace-wrapper {
        grid-template-columns: 1fr;
        height: auto;
    }
    .ws-left, .ws-right {
        display: none; /* In production, these would be drawers */
    }
}

/* LEFT PANEL: Channels & Info */
.ws-left {
    background: rgba(0,0,0,0.2);
    border-right: 1px solid var(--color-border);
    display: flex;
    flex-direction: column;
    padding: 1.5rem 1rem;
    overflow-y: auto;
}

.ws-title-block {
    margin-bottom: 2rem;
}

.ws-title-block h2 {
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.ws-meta-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
}

.ws-channel-group {
    margin-bottom: 1.5rem;
}

.ws-group-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ws-channel {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: var(--radius-sm);
    color: var(--text-muted);
    cursor: pointer;
    transition: var(--transition-smooth);
    font-size: 0.9rem;
    font-weight: 500;
}

.ws-channel:hover, .ws-channel.active {
    background: rgba(255,255,255,0.05);
    color: var(--text-main);
}

.ws-channel.active {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}

/* CENTER PANEL: Discussion */
.ws-center {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.ws-center-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 700;
    font-size: 1.1rem;
    background: rgba(255,255,255,0.02);
}

.ws-chat-area {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.chat-msg {
    display: flex;
    gap: 1rem;
}

.chat-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    flex-shrink: 0;
}

.chat-body {
    flex: 1;
}

.chat-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.chat-name {
    font-weight: 700;
    color: var(--text-main);
}

.chat-role {
    font-size: 0.75rem;
    color: var(--color-primary);
    font-weight: 600;
}

.chat-time {
    font-size: 0.75rem;
    color: var(--text-dark);
}

.chat-text {
    color: #d1d5db;
    font-size: 0.95rem;
    line-height: 1.5;
}

.ws-input-area {
    padding: 1.5rem;
    background: rgba(0,0,0,0.1);
}

.ws-input-wrapper {
    background: rgba(25, 30, 50, 0.6);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ws-input-wrapper input {
    flex: 1;
    background: transparent;
    border: none;
    color: #fff;
    padding: 0.75rem;
    font-family: var(--font-body);
}

.ws-input-wrapper input:focus {
    outline: none;
}

.ws-send-btn {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--color-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-bounce);
}

.ws-send-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 0 15px var(--color-primary);
}

/* RIGHT PANEL: Activity & Tasks */
.ws-right {
    background: rgba(0,0,0,0.2);
    border-left: 1px solid var(--color-border);
    padding: 1.5rem 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.right-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.task-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 1rem;
    margin-bottom: 0.75rem;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-title {
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1.3;
}

.task-meta {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.member-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.member-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    position: relative;
}

.online-dot {
    position: absolute;
    bottom: -2px; right: -2px;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--color-success);
    border: 2px solid var(--bg-main);
}

.form-input{
    width:100%;
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    padding:16px;
    color:white;
    font-size:.95rem;
    outline:none;
}

.form-input:focus{
    border-color:#6366f1;
}

select.form-input{
    appearance:auto;
    -webkit-appearance:auto;
    -moz-appearance:auto;
}
</style>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <div class="workspace-wrapper animate-on-scroll">
            
            <!-- LEFT PANEL -->
            <div class="ws-left">
                <div class="ws-title-block">
                    <h2><?= SecurityHelper::escape($workspace['name']) ?></h2>
                    <span class="ws-meta-badge">● <?= SecurityHelper::escape($workspace['event_status']) ?></span>
                </div>

                <div class="ws-channel-group">
                    <div class="ws-group-title">
                        <span>Event Channels</span>
                        <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                    </div>
                    <div class="ws-channel active">
                        <i data-lucide="hash" style="width: 16px; height: 16px;"></i> general-discussion
                    </div>
                    <div class="ws-channel">
                        <i data-lucide="hash" style="width: 16px; height: 16px;"></i> technical-setup
                    </div>
                    <div class="ws-channel">
                        <i data-lucide="hash" style="width: 16px; height: 16px;"></i> social-media
                    </div>
                </div>

                <div class="ws-channel-group">
                    <div class="ws-group-title">
                        <span>Team Progress</span>
                    </div>
                    <?php foreach($progress as $team): ?>
                        <div class="ws-channel" style="cursor: default; justify-content: space-between;">
                            <span style="font-size: 0.8rem;"><?= SecurityHelper::escape($team['role_name']) ?></span>
                            <span style="font-size: 0.75rem; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">
                                <?= (int)$team['completed_tasks'] ?>/<?= (int)$team['total_tasks'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CENTER PANEL -->
            <div class="ws-center">
                <div class="ws-center-header">
                    <i data-lucide="hash" style="color: var(--text-muted);"></i>
                    general-discussion
                </div>
                
                <div class="ws-chat-area" id="chatArea">
                    <div style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">
                        <h3>Welcome to #general-discussion!</h3>
                        <p style="font-size: 0.85rem;">This is the start of the <?= SecurityHelper::escape($workspace['name']) ?> workspace.</p>
                    </div>

                    <?php foreach($messages as $msg): 
                        $ini = strtoupper(substr($msg['full_name'], 0, 1));
                    ?>
                        <div class="chat-msg">
                            <div class="chat-avatar"><?= SecurityHelper::escape($ini) ?></div>
                            <div class="chat-body">
                                <div class="chat-header">
                                    <span class="chat-name"><?= SecurityHelper::escape($msg['full_name']) ?></span>
                                    <span class="chat-role"><?= SecurityHelper::escape($msg['role_name']) ?></span>
                                    <span class="chat-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <div class="chat-text">
                                    <?= nl2br(SecurityHelper::escape($msg['message'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="ws-input-area">
                    <form method="POST" class="ws-input-wrapper">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
                        <button type="button" class="nav-icon-btn" style="width: 36px; height: 36px; background: transparent; border: none;">
                            <i data-lucide="plus-circle"></i>
                        </button>
                        <input type="text" name="message" placeholder="Message #general-discussion" required autocomplete="off">
                        <button type="submit" name="send_message" class="ws-send-btn">
                            <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="ws-right">
                
                <!-- Pending Tasks -->
                <div>
                    <div class="right-section-title">Pending Tasks</div>
                    <?php foreach($eventTasks as $task): ?>
                        <div class="task-card">
                            <div class="task-header">
                                <span class="task-title"><?= SecurityHelper::escape($task['task_title']) ?></span>
                                <?php if($task['status'] === 'Completed'): ?>
    <span class="chip success"
          style="font-size:.6rem;padding:.2rem .5rem;margin-left:auto;">
          Done
    </span>

<?php elseif($task['status'] === 'Submitted'): ?>
    <span class="chip warning"
          style="font-size:.6rem;padding:.2rem .5rem;margin-left:auto;">
          Review
    </span>

<?php else: ?>
    <span class="chip info"
          style="font-size:.6rem;padding:.2rem .5rem;margin-left:auto;">
          Todo
    </span>
<?php endif; ?>

                                <?php if(
    !empty($task['work_update'])
): ?>

<div style="
margin-top:10px;
padding:12px;
background:#111827;
border-radius:12px;
font-size:13px;
line-height:1.6;
color:#cbd5e1;
">

<b>Work Submitted:</b>
<br><br>

<?= nl2br(
SecurityHelper::escape(
$task['work_update']
)
) ?>

</div>

<?php endif; ?>
                            </div>
                            <div class="task-meta">
                                <?= SecurityHelper::escape($task['full_name'] ?? 'Unassigned') ?> · <?= SecurityHelper::escape($task['role_name']) ?>
                            </div>
                            
                            <!-- Action button for Submitted tasks -->
                             <hr style="
margin-top:12px;
margin-bottom:12px;
border-color:rgba(255,255,255,.1);
">
                            <?php if($task['status'] === 'Submitted' && ($user['role_name'] === 'Faculty Coordinator' || $user['role_name'] === 'Student Coordinator')): ?>
                                <form method="POST" style="margin-top: 0.75rem;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" name="approve_task" class="btn btn-primary" style="width: 100%; padding: 0.4rem; font-size: 0.75rem;">
                                        Approve Task
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Assign Task Form (Coordinators only) -->
                <?php if($user['role_name'] === 'Faculty Coordinator' || $user['role_name'] === 'Student Coordinator'): ?>
                    <div>
                        <div class="right-section-title">Assign New Task</div>
                        <div class="task-card">
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?= $workspace['event_id'] ?>">
                                
                                <input type="text" name="task_title" class="form-input" style="padding: 0.5rem; margin-bottom: 0.5rem;" placeholder="Task Title" required>
                                
                               <select
name="assigned_to"
class="form-input"
style="padding:0.5rem;margin-bottom:0.5rem;"
required>
                                    <option value="">Select Coordinator</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?= $u['id'] ?>">
    <?= SecurityHelper::escape($u['full_name']) ?>
    (<?= SecurityHelper::escape($u['role_name']) ?>)
</option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select name="priority" class="form-input" style="padding: 0.5rem; margin-bottom: 0.5rem;">
                                    <option value="High">High Priority</option>
                                    <option value="Medium" selected>Medium Priority</option>
                                    <option value="Low">Low Priority</option>
                                </select>

                                <input type="date" name="deadline" class="form-input" style="padding: 0.5rem; margin-bottom: 0.5rem;" required>
                                
                                <button type="submit" name="assign_task" class="btn btn-secondary" style="width: 100%; padding: 0.5rem; font-size: 0.8rem;">
                                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Create Task
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Online Members (Mocked for Visual) -->
                <div>
                    <div class="right-section-title">Online Members — 3</div>
                    
                    <div class="member-item">
                        <div class="member-avatar">
                            RP
                            <div class="online-dot"></div>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 600;">Dr. Ramesh Prasad <span style="font-size: 0.7rem; color: var(--text-muted);">(Faculty)</span></div>
                    </div>
                    
                    <div class="member-item">
                        <div class="member-avatar" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa;">
                            AM
                            <div class="online-dot" style="background: var(--color-warning);"></div>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 600;">Aarav Mehta <span style="font-size: 0.7rem; color: var(--text-muted);">(Student)</span></div>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<script>
// Auto-scroll chat to bottom
document.addEventListener('DOMContentLoaded', () => {
    const chatArea = document.getElementById('chatArea');
    if (chatArea) {
        chatArea.scrollTop = chatArea.scrollHeight;
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>