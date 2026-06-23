<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$db = Database::getConnection();

$eventId = $_GET['event_id'] ?? 0;

$stmt = $db->prepare("
    SELECT *
    FROM events
    WHERE id = ?
");

$stmt->execute([$eventId]);

$event = $stmt->fetch();

if(!$event){
    die('Event not found');
}

if(isset($_POST['save_attendance'])){

    foreach($_POST['attendance'] as $registrationId => $status){

        $stmtCheck = $db->prepare("
            SELECT id
            FROM participant_attendance
            WHERE
                event_id = ?
                AND registration_id = ?
        ");

        $stmtCheck->execute([
            $eventId,
            $registrationId
        ]);

        $exists = $stmtCheck->fetch();

        if($exists){

            $stmtUpdate = $db->prepare("
                UPDATE participant_attendance
                SET
                    attendance_status = ?,
                    marked_by = ?,
                    marked_at = NOW()
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $status,
                $_SESSION['user']['id'],
                $exists['id']
            ]);

        } else {

            $stmtInsert = $db->prepare("
                INSERT INTO participant_attendance
                (
                    event_id,
                    registration_id,
                    attendance_status,
                    marked_by,
                    marked_at
                )
                VALUES
                (?, ?, ?, ?, NOW())
            ");

            $stmtInsert->execute([
                $eventId,
                $registrationId,
                $status,
                $_SESSION['user']['id']
            ]);
        }
    }

    $success = true;
}

$stmtParticipants = $db->prepare("
    SELECT *
    FROM participant_registrations
    WHERE event_id = ?
    ORDER BY team_name ASC,
             participant_name ASC
");

$stmtParticipants->execute([$eventId]);

$participants = $stmtParticipants->fetchAll();

$attendanceMap = [];

$stmtAttendance = $db->prepare("
    SELECT registration_id, attendance_status
    FROM participant_attendance
    WHERE event_id = ?
");

$stmtAttendance->execute([$eventId]);

while ($row = $stmtAttendance->fetch()) {
    $attendanceMap[$row['registration_id']] =
        $row['attendance_status'];
}

$pageTitle = 'Attendance';

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

<h1>
<?= SecurityHelper::escape($event['title']) ?>
</h1>

<?php if(!empty($success)): ?>

<div class="success-box">
Attendance Saved Successfully ✅
</div>

<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

<div class="attendance-card">

<table>

<thead>

<tr>

<th>Team</th>
<th>Name</th>
<th>Enrollment</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($participants as $p): ?>

<tr>

<td>
<?= SecurityHelper::escape(
$p['team_name'] ?? '-'
) ?>
</td>

<td>
<?= SecurityHelper::escape(
$p['participant_name']
) ?>
</td>

<td>
<?= SecurityHelper::escape(
$p['enrollment_no']
) ?>
</td>

<td>

<select
name="attendance[<?= $p['id'] ?>]">

<option
value="Present"
<?= (($attendanceMap[$p['id']] ?? '') === 'Present')
    ? 'selected'
    : '' ?>>
Present
</option>

<option
value="Absent"
<?= (($attendanceMap[$p['id']] ?? '') === 'Absent')
    ? 'selected'
    : '' ?>>
Absent
</option>

</select>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<button
type="submit"
name="save_attendance"
class="save-btn">

Save Attendance

</button>

</div>

</form>

</main>

</div>

<style>

.attendance-card{
background:var(--bg-surface);
padding:20px;
border-radius:20px;
overflow:auto;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
padding:12px;
border-bottom:
1px solid rgba(255,255,255,.08);
}

select{
padding:8px;
border-radius:8px;
}

.save-btn{
margin-top:20px;
padding:14px 24px;
background:#22c55e;
border:none;
border-radius:12px;
color:white;
cursor:pointer;
}

.success-box{
background:#22c55e;
padding:15px;
border-radius:12px;
margin-bottom:20px;
color:white;
}

</style>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>