<?php
// views/pages/events.php

use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Protect route and pull active user profile
AuthMiddleware::handle();

use CyberKavach\Nexus\Config\Database;

$user = $_SESSION['user'];

$canCreateEvent = in_array(
    $user['role_name'],
    [
        'Faculty Coordinator',
        'Student Coordinator'
    ]
);

$canApproveEvent =
    $user['role_name'] === 'Faculty Coordinator';

$db = Database::getConnection();

if (
    isset($_POST['approve_event']) &&
    $canApproveEvent
) {

    if (
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        UPDATE events
        SET
            status = 'Approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $user['id'],
        $_POST['event_id']
    ]);

    $eventId = $_POST['event_id'];

$tasks = [

['Tech Coordinator',
 'Prepare registration setup'],

['Tech Coordinator',
 'Manage attendance system'],

['Social Media Coordinator',
 'Create Instagram post'],

['Social Media Coordinator',
 'Create WhatsApp announcement'],

['Content Coordinator',
 'Write event description'],

['Content Coordinator',
 'Prepare certificates']

];

foreach ($tasks as $task) {

    $stmtTask = $db->prepare("
        INSERT INTO event_tasks
        (
            event_id,
            role_name,
            task_title
        )
        VALUES
        (?, ?, ?)
    ");

    $stmtTask->execute([
        $eventId,
        $task[0],
        $task[1]
    ]);
}



$stmtWorkspace = $db->prepare("
    INSERT INTO event_workspaces
    (
        event_id,
        name,
        description,
        status,
        created_by
    )
    VALUES
    (
        ?,
        ?,
        ?,
        'Open',
        ?
    )
");

$stmtWorkspace->execute([
    $eventId,
    'Workspace - Event #' . $eventId,
    'Auto generated workspace for event execution',
    $user['id']
]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['delete_event'])) {

    if (
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        DELETE FROM events
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['event_id']
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (
    isset($_POST['reject_event']) &&
    $canApproveEvent
) {

    if (
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        UPDATE events
        SET
            status = 'Rejected',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $user['id'],
        $_POST['event_id']
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['approve_event']) &&
    !isset($_POST['reject_event']) &&
    !isset($_POST['delete_event'])
) {

    if (!$canCreateEvent) {
        die('Access Denied');
    }

    if (
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {
        die('Invalid CSRF Token');
    }

    $status = 'Pending Approval';

if ($user['role_name'] === 'Faculty Coordinator') {
    $status = 'Approved';
}

    $stmt = $db->prepare("
    INSERT INTO events

(
    title,
    description,
    location,
    start_time,
    end_time,
    capacity,

    registration_type,
    max_team_size,

    registration_deadline,

    created_by,
    status
)
    
VALUES
(
    :title,
    :description,
    :location,
    :start_time,
    :end_time,
    :capacity,

    :registration_type,
    :max_team_size,

    :registration_deadline,

    :created_by,
    :status
)
    ");

    $stmt->execute([
    'title' => $_POST['title'],
    'description' => $_POST['description'],
    'location' => $_POST['location'],
    'start_time' => $_POST['start_time'],
    'end_time' => $_POST['end_time'],
    'capacity' => $_POST['capacity'] ?: 0,

'registration_type' =>
    $_POST['registration_type'],

'max_team_size' =>
    $_POST['max_team_size'] ?: 1,

'registration_deadline' =>
    $_POST['registration_deadline'] ?: null,

'created_by' => $user['id'],
'status' => $status
]);

$eventId = $db->lastInsertId();

if ($status === 'Approved') {

    $stmtWorkspace = $db->prepare("
        INSERT INTO event_workspaces
        (
            event_id,
            name,
            description,
            status,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'Open',
            ?
        )
    ");

    $stmtWorkspace->execute([
        $eventId,
        $_POST['title'],
        'Auto generated workspace for event execution',
        $user['id']
    ]);
}

    header('Location: ' . $_SERVER['PHP_SELF']);
exit;   
}



$stmt = $db->prepare("
    SELECT
        e.*,
        u.full_name,
        r.name AS role_name
    FROM events e
    JOIN users u ON e.created_by = u.id
    JOIN roles r ON u.role_id = r.id
    ORDER BY e.created_at DESC
");
$stmt->execute();

$events = $stmt->fetchAll();

$pageTitle = 'Events Management - CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <div class="events-header">

    <div>
        <h1 class="page-title">Event Command Center</h1>
        <p>Create, review and manage CyberKavach events.</p>
    </div>

    <?php if (
    $user['role_name'] === 'Faculty Coordinator' ||
    $user['role_name'] === 'Student Coordinator'
): ?>

<button
    class="create-event-btn"
    onclick="document.getElementById('createEventModal').classList.add('show-modal')">

    <i data-lucide="plus"></i>
    Create Event

</button>

<?php endif; ?>

</div>

<div class="events-grid">

<?php foreach ($events as $event): ?>

<div class="event-card">


    <div class="event-top">

        <div>

            <h3>
                <?= SecurityHelper::escape($event['title']) ?>
            </h3>

            <span class="event-status status-<?= strtolower(str_replace(' ', '-', $event['status'])) ?>">
    <?= SecurityHelper::escape($event['status']) ?>
</span>

        </div>

    </div>

    <p class="event-desc">
        <?= SecurityHelper::escape(substr($event['description'] ?? '', 0, 180)) ?>
    </p>

    <div class="event-info-grid">

    <div class="info-item">
        <span>📍</span>
        <div>
            <small>Venue</small>
            <strong><?= SecurityHelper::escape($event['location']) ?></strong>
        </div>
    </div>

    <div class="info-item">
        <span>👤</span>
        <div>
            <small>Organizer</small>
            <strong><?= SecurityHelper::escape($event['full_name']) ?></strong>
        </div>
    </div>

    <div class="info-item">
        <span>🗓</span>
        <div>
            <small>Date</small>
            <strong>
                <?= date('d M Y h:i A', strtotime($event['start_time'])) ?>
            </strong>
        </div>
    </div>

    <div class="info-item">
        <span>👥</span>
        <div>
            <small>Capacity</small>
            <strong><?= (int)$event['capacity'] ?> Participants</strong>
        </div>
    </div>

</div>

<?php if($event['status']=='Approved'): ?>

<div class="registration-box">

    <div class="registration-header">
        🔗Event Registration Link
    </div>

    <div class="registration-url">

        <span>
            localhost/cyber2/...event_id=<?= $event['id'] ?>
        </span>

        <button
onclick="
navigator.clipboard.writeText(
'http://localhost/cyber2/views/pages/event_registration.php?event_id=<?= $event['id'] ?>'
);
showToast('Registration link copied','success');
">
📋 Copy
</button>

    </div>

</div>

<?php endif; ?>

    <div class="event-role">

    <?php if ($canApproveEvent && $event['status'] === 'Pending Approval'): ?>

<div class="approval-actions">

    <form method="POST">

    <input type="hidden" name="csrf_token"
           value="<?= SecurityHelper::generateCsrfToken() ?>">

    <input type="hidden"
           name="event_id"
           value="<?= $event['id'] ?>">

    <button
        type="submit"
        name="approve_event"
        class="approve-btn">
        Approve
    </button>

    <button
        type="submit"
        name="reject_event"
        class="reject-btn">
        Reject
    </button>

</form>



<form method="POST">

    <input type="hidden"
           name="csrf_token"
           value="<?= SecurityHelper::generateCsrfToken() ?>">

    <input type="hidden"
           name="event_id"
           value="<?= $event['id'] ?>">

    <button
        type="submit"
        name="delete_event"
        class="delete-btn"
        onclick="return confirm('Delete this event?')">

        Delete

    </button>

</form>

</div>

<?php endif; ?>

        <?= SecurityHelper::escape($event['role_name']) ?>
    </div>

</div>

<?php endforeach; ?>

</div>

<div class="event-modal"
     id="createEventModal"
     onclick="if(event.target===this)this.classList.remove('show-modal')">

    <div class="event-modal-card">

        <div class="modal-header">
            <h2>Create Event</h2>

            <button
                class="close-modal"
                onclick="document.getElementById('createEventModal').classList.remove('show-modal')">
                ✕
            </button>
        </div>

        <form method="POST">

           <input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

            <div class="form-grid">

                <input
                    type="text"
                    name="title"
                    placeholder="Event Title"
                    required>

                <input
                    type="text"
                    name="location"
                    placeholder="Location"
                    required>

                    <label>
    📅 Event Start Date & Time
</label>
<input
    type="datetime-local"
    name="start_time"
    required>

<label>
    📅 Event End Date & Time
</label>
<input
    type="datetime-local"
    name="end_time"
    required>

<input
    type="number"
    name="capacity"
    placeholder="Event Capacity">


<label>
    Registration Deadline
</label>

<input
    type="datetime-local"
    name="registration_deadline">

    <br><br>

<label>
Registration Type
</label>

<select
    name="registration_type"
    id="registration_type">

    <option value="Individual">
        Individual
    </option>

    <option value="Team">
        Team
    </option>

</select>

<br><br>



<div id="teamSizeWrapper">

<label>
Maximum Team Size
</label>

<input
    type="number"
    name="max_team_size"
    min="2"
    value="2">

</div>

                <textarea
                    name="description"
                    placeholder="Enter complete event details, schedule, rules, agenda, requirements and participant information..."
                    rows="5"
                    required></textarea>

            </div>

            <button type="submit" class="launch-btn">

                <i data-lucide="rocket"></i>

                Launch Event

            </button>

        </form>

    </div>

</div> 

    </main>
</div>
<style>

.events-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:2rem;
}

.events-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(350px,1fr));
gap:1.5rem;
}

.event-modal-card{
    width:min(700px,90%);
    max-height:90vh;
    overflow-y:auto;

    background:
    linear-gradient(
    145deg,
    rgba(13,17,40,.95),
    rgba(9,12,26,.95)
    );

    border:1px solid rgba(99,102,241,.25);
    border-radius:32px;
    padding:2rem;

    box-shadow:
    0 0 80px rgba(99,102,241,.25);
}

.event-card{
position:relative;

background:
linear-gradient(
145deg,
rgba(15,23,42,.96),
rgba(10,15,30,.95)
);

border:1px solid rgba(255,255,255,.06);

border-radius:28px;

padding:1.8rem;

overflow:hidden;

transition:.35s;

box-shadow:
0 15px 50px rgba(0,0,0,.45);
}

.event-card::before{
content:'';
position:absolute;
top:0;
left:0;
width:100%;
height:4px;

background:
linear-gradient(
90deg,
#06b6d4,
#6366f1,
#a855f7
);
}

.form-grid label{
    color:white;
    font-size:.9rem;
    font-weight:600;
    margin-top:.25rem;
}

.event-card:hover{
transform:translateY(-8px);

box-shadow:
0 25px 70px rgba(99,102,241,.18);
}

.delete-btn{
background:#ef4444;
color:white;
border:none;
padding:8px 14px;
border-radius:10px;
cursor:pointer;
margin-top:.5rem;
}

.register-link{
display:inline-block;
margin-top:12px;
padding:10px 18px;
border-radius:12px;
background:#22c55e;
color:white;
text-decoration:none;
font-weight:600;
}

.event-status{
background:rgba(99,102,241,.15);
padding:.35rem .8rem;
border-radius:50px;
font-size:.75rem;
}

.event-desc{
margin:1rem 0;
color:var(--text-muted);
}

.event-meta{
display:flex;
justify-content:space-between;
margin-bottom:1rem;
font-size:.9rem;
}

.event-role{
margin-top:25px;

display:inline-flex;
align-items:center;

padding:12px 22px;

background:
linear-gradient(
135deg,
rgba(6,182,212,.15),
rgba(99,102,241,.18)
);

border:1px solid rgba(99,102,241,.25);

border-radius:50px;

font-weight:600;
}

.create-event-btn{
background:linear-gradient(
135deg,
#6366f1,
#8b5cf6
);

border:none;
padding:14px 24px;
border-radius:18px;
color:white;
font-weight:700;
cursor:pointer;

display:flex;
gap:.75rem;
align-items:center;

box-shadow:
0 0 30px rgba(99,102,241,.25);
}

.status-approved{
background:rgba(34,197,94,.15);
color:#22c55e;
}

.status-pending-approval{
background:rgba(245,158,11,.15);
color:#f59e0b;
}

.status-rejected{
background:rgba(239,68,68,.15);
color:#ef4444;
}

.status-draft{
background:rgba(148,163,184,.15);
color:#94a3b8;
}

.create-event-btn:hover{
transform:translateY(-2px);
}

.event-modal{
position:fixed;
inset:0;

background:rgba(0,0,0,.75);
backdrop-filter:blur(10px);

display:flex;
align-items:center;
justify-content:center;

opacity:0;
visibility:hidden;

transition:.3s;

z-index:9999;
}

.show-modal{
opacity:1;
visibility:visible;
}

.event-modal-card{

width:min(700px,90%);

background:
linear-gradient(
145deg,
rgba(13,17,40,.95),
rgba(9,12,26,.95)
);

border:1px solid rgba(99,102,241,.25);

border-radius:32px;

padding:2rem;

box-shadow:
0 0 80px rgba(99,102,241,.25);
}

.modal-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:2rem;
}

.modal-header h2{
font-size:1.5rem;
}

.close-modal{
background:none;
border:none;
color:white;
font-size:1.3rem;
cursor:pointer;
}

.form-grid{
display:flex;
flex-direction:column;
gap:1rem;
}

.form-grid input,
.form-grid textarea{

background:#0f172a;

border:1px solid rgba(99,102,241,.2);

padding:1rem;

border-radius:16px;

color:white;
}

.launch-btn{

margin-top:1.5rem;

width:100%;

padding:16px;

border:none;

border-radius:18px;

cursor:pointer;

font-weight:700;

color:white;

background:
linear-gradient(
135deg,
#06b6d4,
#6366f1,
#a855f7
);

box-shadow:
0 0 40px rgba(99,102,241,.4);

transition:.3s;
}

.launch-btn:hover{
transform:translateY(-3px) scale(1.02);
}

.event-info-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:18px;
margin:25px 0;
}

.info-item{
display:flex;
gap:14px;

padding:16px;

background:rgba(255,255,255,.03);

border:1px solid rgba(255,255,255,.05);

border-radius:18px;

transition:.3s;
}

.info-item:hover{
background:rgba(99,102,241,.08);

border-color:
rgba(99,102,241,.35);
}

.info-item span{
font-size:20px;
}

.info-item small{
display:block;
color:#94a3b8;
font-size:.75rem;
margin-bottom:4px;
}

.info-item strong{
color:white;
font-size:.95rem;
}

.registration-box{
margin-top:25px;

padding:18px;

background:
linear-gradient(
145deg,
rgba(17,24,39,.9),
rgba(10,15,30,.95)
);

border-radius:20px;

border:1px solid rgba(99,102,241,.2);

box-shadow:
0 10px 30px rgba(0,0,0,.3);
}

.registration-header{
font-size:.85rem;
color:#94a3b8;
margin-bottom:12px;
}

.registration-url{
display:flex;
justify-content:space-between;
align-items:center;
gap:15px;
}

.registration-url span{
overflow:hidden;
text-overflow:ellipsis;
white-space:nowrap;
font-size:.85rem;
color:white;
}

.registration-url button{
min-width:80px;
height:44px;

display:flex;
align-items:center;
justify-content:center;

padding:0 16px;

border:none;
border-radius:14px;

background:linear-gradient(
135deg,
#6366f1,
#8b5cf6
);

color:white;
font-size:.85rem;
font-weight:600;

cursor:pointer;

transition:.3s;

box-shadow:
0 8px 25px rgba(99,102,241,.4);

flex-shrink:0;
}

.registration-url button:hover{
transform:translateY(-3px);
}

</style>

<script>

const registrationType =
document.getElementById('registration_type');

const teamSizeWrapper =
document.getElementById('teamSizeWrapper');

function toggleTeamSize() {

    if (
        registrationType.value === 'Team'
    ) {
        teamSizeWrapper.style.display = 'block';
    } else {
        teamSizeWrapper.style.display = 'none';
    }
}

registrationType.addEventListener(
    'change',
    toggleTeamSize
);

toggleTeamSize();

</script>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>
