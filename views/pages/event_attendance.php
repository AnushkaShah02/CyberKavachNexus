<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$db = Database::getConnection();

$user = $_SESSION['user'];

if(
    $user['role_name'] !== 'Faculty Coordinator'
    &&
    $user['role_name'] !== 'Student Coordinator'
    &&
    $user['role_name'] !== 'Tech Coordinator'
){
    die('Access Denied');
}

$stmt = $db->prepare("
SELECT
    e.*,

    (
        SELECT COUNT(*)
        FROM participant_registrations pr
        WHERE pr.event_id = e.id
    ) AS total_registrations

FROM events e

WHERE e.status = 'Approved'

ORDER BY e.start_time DESC
");

$stmt->execute();

$events = $stmt->fetchAll();

$pageTitle = 'Event Attendance';

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

<div class="page-header">

<h1>
Event Attendance
</h1>

<p>
Manage attendance event-wise.
</p>

</div>

<div class="events-grid">

<?php foreach($events as $event): ?>

<div class="event-card">

<h3>
<?= SecurityHelper::escape($event['title']) ?>
</h3>

<p>
📍 <?= SecurityHelper::escape($event['location']) ?>
</p>

<p>
📅 <?= date(
'd M Y',
strtotime($event['start_time'])
) ?>
</p>

<p>
👥 Registered:
<?= (int)$event['total_registrations'] ?>
</p>

<a
class="manage-btn"
href="<?= SecurityHelper::asset(
'views/pages/attendance_details.php?event_id='
. $event['id']
) ?>">

Manage Attendance

</a>

</div>

<?php endforeach; ?>

</div>

</main>

</div>

<style>

.events-grid{
display:grid;
grid-template-columns:
repeat(auto-fill,minmax(320px,1fr));
gap:20px;
}

.event-card{
background:var(--bg-surface);
padding:20px;
border-radius:20px;
border:1px solid rgba(255,255,255,.08);
}

.manage-btn{
display:block;
margin-top:15px;
text-align:center;
padding:12px;
background:#22c55e;
color:white;
text-decoration:none;
border-radius:12px;
font-weight:600;
}

</style>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>