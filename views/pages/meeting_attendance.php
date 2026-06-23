<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__,2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$db = Database::getConnection();

$meetingId = $_GET['meeting_id'] ?? 0;

if(isset($_POST['save_attendance'])){

    if(
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ){
        die('Invalid CSRF Token');
    }

    if(
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ){
        die('Invalid CSRF Token');
    }

    foreach($_POST['attendance'] as $userId => $status){

        $stmt = $db->prepare("
            INSERT INTO meeting_attendance
            (
                meeting_id,
                user_id,
                status
            )
            VALUES
            (
                ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
            status = VALUES(status)
        ");

        $stmt->execute([
            $meetingId,
            $userId,
            $status
        ]);
    }

    $saved = true;
}

$stmt = $db->prepare("
SELECT
    u.id,
    u.full_name,
    u.current_position,
    COALESCE(ma.status,'Absent') attendance_status
FROM users u
LEFT JOIN meeting_attendance ma
    ON ma.user_id = u.id
    AND ma.meeting_id = ?
WHERE
    u.status = 'Active'
    AND u.current_position <> 'Faculty Coordinator'
ORDER BY u.full_name
");

$stmt->execute([$meetingId]);

$members = $stmt->fetchAll();

$pageTitle = 'Meeting Attendance';

require_once dirname(__DIR__,2)
. '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__,2)
. '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__,2)
. '/views/components/navbar.php'; ?>

<div class="attendance-header">

    <div>
        <h1>Meeting Attendance</h1>
        <p>
            Track member participation for this meeting
        </p>
    </div>

</div>

<?php if(!empty($saved)): ?>

<div class="success-box">
Attendance Updated Successfully
</div>

<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

<div class="members-grid">

<?php foreach($members as $member): ?>

<div class="member-card">

<h3>
<?= SecurityHelper::escape(
$member['full_name']
) ?>
</h3>

<p>
<?= SecurityHelper::escape(
$member['current_position']
) ?>
</p>

<select
name="attendance[<?= $member['id'] ?>]">

<option
value="Present"
<?= $member['attendance_status']=='Present'
? 'selected' : '' ?>>

Present

</option>

<option
value="Absent"
<?= $member['attendance_status']=='Absent'
? 'selected' : '' ?>>

Absent

</option>

<option
value="Excused"
<?= $member['attendance_status']=='Excused'
? 'selected' : '' ?>>

Excused

</option>

</select>

</div>

<?php endforeach; ?>

</div>

<button
type="submit"
name="save_attendance"
class="save-btn">

Save Attendance

</button>

</form>

</main>

</div>

<style>

.members-grid{
display:grid;
grid-template-columns:
repeat(auto-fill,minmax(280px,1fr));
gap:20px;
}

.member-card{

background:
linear-gradient(
135deg,
rgba(99,102,241,.12),
rgba(15,23,42,.95)
);

border:
1px solid rgba(99,102,241,.15);

padding:24px;

border-radius:24px;

transition:.3s;
}

.member-card:hover{

transform:translateY(-6px);

box-shadow:
0 20px 40px
rgba(99,102,241,.2);

}

.member-card h3{
margin-bottom:8px;
}

.member-card p{
color:#94a3b8;
margin-bottom:15px;
}

.member-card select{
width:100%;
padding:12px;
border-radius:12px;
background:#111827;
color:white;
border:none;
}

.save-btn{

margin-top:25px;

padding:14px 28px;

border:none;

border-radius:14px;

background:#6366f1;

color:white;

cursor:pointer;

font-weight:600;
}

.success-box{

background:#22c55e;

padding:15px;

border-radius:12px;

margin-bottom:20px;

color:white;
}

.attendance-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.attendance-header h1{
margin:0;
font-size:2rem;
font-weight:700;
}

.attendance-header p{
margin-top:6px;
color:#94a3b8;
}

</style>

<?php
require_once dirname(__DIR__,2)
. '/views/layouts/footer.php';
?>