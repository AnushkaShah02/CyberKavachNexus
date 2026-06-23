<?php

use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$user = $_SESSION['user'];

$db = Database::getConnection();

if(isset($_POST['save_update'])){

    if(
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ){
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        UPDATE event_tasks
        SET work_update = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $_POST['work_update'],
        $_POST['task_id']
    ]);

    if($success){
        echo "Saved!";
    }else{
        print_r($stmt->errorInfo());
        exit;
    }
}

if(isset($_POST['complete_task'])){

    if(
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ){
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
    UPDATE event_tasks
    SET status = 'Submitted'
    WHERE id = ?
");

    $stmt->execute([
        $_POST['task_id']
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $db->prepare("
    SELECT
        et.*,
        e.title AS event_title,
        e.start_time
    FROM event_tasks et
    JOIN events e
        ON et.event_id = e.id
    WHERE et.assigned_to = ?
    ORDER BY e.start_time ASC
");

$stmt->execute([
    $user['id']
]);

$tasks = $stmt->fetchAll();

$pageTitle = 'My Tasks - CyberKavach Nexus';

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

<h1 class="page-title">My Tasks</h1>

<div class="tasks-grid">

<?php foreach($tasks as $task): ?>

<div class="task-card <?= $task['status'] === 'Completed' ? 'task-completed' : '' ?>">

<div class="task-header">

<h3>
<?= SecurityHelper::escape($task['event_title']) ?>
</h3>

<?php if($task['status']=='Completed'): ?>

<div class="status-pill completed">
✓ Completed
</div>

<?php else: ?>

<div class="status-pill pending">
Pending
</div>

<?php endif; ?>

</div>

<p>

📅 Event Date:

<?= date(
    'd M Y',
    strtotime($task['start_time'])
) ?>

</p>

<p>

📝 Task:

<?= SecurityHelper::escape(
    $task['task_title']
) ?>

</p>

<p>

<?php if($task['status'] === 'Completed'): ?>

<p style="color:#22c55e;font-weight:700;">
✅ Completed
</p>

<?php else: ?>

<p style="color:#f59e0b;font-weight:700;">
⏳ Pending
</p>

<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

<input
    type="hidden"
    name="task_id"
    value="<?= $task['id'] ?>">

<textarea
    name="work_update"
    rows="4"
    placeholder="Describe what you completed. Example: Registration form created, 120 responses received, volunteer list prepared, certificates designed, banners printed, etc."></textarea>

<br><br>

<button
type="submit"
name="save_update"
class="save-btn">

Save Update

</button>

</form>

</p>

<p>

🔥 Priority:

<?= SecurityHelper::escape(
    $task['priority']
) ?>

</p>

<?php if(!empty($task['deadline'])): ?>

<p>

⏳ Deadline:

<?= SecurityHelper::escape(
    $task['deadline']
) ?>

</p>

<?php endif; ?>
    <?php if($task['status'] !== 'Completed'): ?>

    <form method="POST">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= SecurityHelper::generateCsrfToken() ?>">

    <input
        type="hidden"
        name="task_id"
        value="<?= $task['id'] ?>">

    <button
        type="submit"
        name="complete_task"
        class="complete-btn">

        Submit for Review

    </button>

</form>

    <?php endif; ?>

</div>

<?php endforeach; ?>

</div>

</main>

</div>

<style>

.tasks-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(360px,400px));
gap:28px;
justify-content:start;
margin-top:2rem;
}

.task-card{
background:#0f172a;
border:1px solid rgba(255,255,255,.08);
border-radius:28px;
padding:28px;

display:flex;
flex-direction:column;
gap:18px;

transition:.3s;
position:relative;
}

.task-card:hover{
transform:translateY(-4px);
border-color:rgba(255,255,255,.15);
}

.task-completed::before{
content:'';
position:absolute;

left:0;
top:30px;
bottom:30px;

width:4px;
background:#22c55e;
border-radius:20px;
}

.task-card h3{
margin-bottom:.75rem;
}

.complete-btn{
width:100%;
height:52px;

background:#6366f1;
color:white;

border:none;
border-radius:18px;

font-weight:700;
font-size:.95rem;

cursor:pointer;
transition:.3s;
}

.complete-btn:hover{
background:#7c3aed;
}

.task-header{
display:flex;
justify-content:space-between;
align-items:center;
}

.task-header h3{
font-size:1.4rem;
font-weight:700;
}

.status-pill{
padding:8px 14px;
border-radius:999px;
font-size:.85rem;
font-weight:600;
}

.completed{
background:rgba(34,197,94,.12);
color:#22c55e;
}

.pending{
background:rgba(245,158,11,.12);
color:#f59e0b;
}

textarea{
width:100%;
background:#111827;
border:1px solid rgba(255,255,255,.08);
border-radius:18px;
padding:16px;
resize:none;

color:white;
font-size:.95rem;
outline:none;
}

textarea:focus{
border-color:#6366f1;
}

.save-btn{
width:100%;
height:50px;

background:white;
color:#111827;

border:none;
border-radius:18px;

font-weight:700;
cursor:pointer;

transition:.3s;
}

.save-btn:hover{
transform:translateY(-2px);
}

</style>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>