<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$db = Database::getConnection();

$user = $_SESSION['user'];

if (
    isset($_POST['create_meeting']) &&
    $user['role_name'] === 'Faculty Coordinator'
) {

    if (
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        INSERT INTO club_meetings
        (
            title,
            meeting_date,
            venue,
            agenda,
            created_by
        )
        VALUES
        (
            ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['meeting_date'],
        $_POST['venue'],
        $_POST['agenda'],
        $user['id']
    ]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $db->query("
    SELECT *
    FROM club_meetings
    ORDER BY meeting_date DESC
");

$meetings = $stmt->fetchAll();

if(isset($_POST['save_mom'])){

    if(
        !SecurityHelper::verifyCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ){
        die('Invalid CSRF Token');
    }

    $stmt = $db->prepare("
        UPDATE club_meetings
        SET discussion_notes = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['discussion_notes'],
        $_POST['meeting_id']
    ]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


$pageTitle = 'Club Meetings';

require_once dirname(__DIR__, 2)
. '/views/layouts/header.php';

?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2)
. '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2)
. '/views/components/navbar.php'; ?>



<div class="page-header">

<div>
<h1>Club Meetings</h1>
<p>
Manage CyberKavach internal meetings
</p>
</div>

<?php if(
$user['role_name']
=== 'Faculty Coordinator'
): ?>

<button
class="create-btn"
onclick="
document
.getElementById('meetingModal')
.classList.add('show-modal')
">

Create Meeting

</button>

<?php endif; ?>

</div>

<div class="meetings-grid">

<?php foreach($meetings as $meeting): ?>

<div class="meeting-card">

<h3>
<?= SecurityHelper::escape(
$meeting['title']
) ?>
</h3>

<p>
📅
<?= date(
'd M Y h:i A',
strtotime($meeting['meeting_date'])
) ?>
</p>

<p>
📍
<?= SecurityHelper::escape(
$meeting['venue']
) ?>
</p>

<p>
<?= nl2br(
SecurityHelper::escape(
$meeting['agenda']
)
) ?>
</p>

<div class="meeting-section">

<h4>
📋 Discussion Notes
</h4>

<div class="notes-preview">

<?=
!empty($meeting['discussion_notes'])
? nl2br(
SecurityHelper::escape(
substr($meeting['discussion_notes'],0,150)
)
)
: 'No discussion notes added yet.'
?>

</div>

</div>

<span class="status">
<?= $meeting['status'] ?>
</span>

<div class="meeting-actions">

<a
href="meeting_attendance.php?meeting_id=<?= $meeting['id'] ?>"
class="glass-btn">

👥 Attendance

</a>

<?php if(
$user['role_name']
=== 'Faculty Coordinator'
): ?>

<button
type="button"
class="glass-btn"
onclick="openNotesModal(
'<?= $meeting['id'] ?>',
`<?= htmlspecialchars($meeting['discussion_notes'] ?? '', ENT_QUOTES) ?>`
)">

📝 Edit Notes

</button>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

<div
class="meeting-modal"
id="meetingModal"
onclick="
if(event.target===this)
this.classList.remove('show-modal')
">

<div class="meeting-modal-card">

<h2>Create Meeting</h2>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

<input
type="text"
name="title"
placeholder="Meeting Title"
required>

<input
type="datetime-local"
name="meeting_date"
required>

<input
type="text"
name="venue"
placeholder="Venue"
required>

<textarea
name="agenda"
placeholder="Agenda"
rows="5"
required></textarea>

<button
type="submit"
name="create_meeting"
class="submit-btn">

Create Meeting

</button>

</form>

</div>

</div>

<div
class="meeting-modal"
id="notesModal">

<div class="meeting-modal-card">

<h2>Discussion Notes</h2>

<form method="POST">

<input
type="hidden"
name="csrf_token"
value="<?= SecurityHelper::generateCsrfToken() ?>">

<input
type="hidden"
name="meeting_id"
id="modalMeetingId">

<textarea
name="discussion_notes"
id="modalMom"
rows="10"
placeholder="Write discussion notes...">
</textarea>

<button
type="submit"
name="save_mom"
class="submit-btn">

Save Discussion Notes

</button>

</form>

</div>

</div>

</main>

</div>

<style>

.page-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.create-btn{
background:#2563eb;
color:white;
border:none;
padding:14px 24px;
border-radius:14px;
cursor:pointer;
font-weight:600;
}

.meetings-grid{
display:grid;
grid-template-columns:
repeat(auto-fill,minmax(350px,1fr));
gap:20px;
}

.meeting-card{
background:var(--bg-surface);
padding:20px;
border-radius:20px;
border:1px solid rgba(255,255,255,.08);
}

.meeting-card h3{
margin-bottom:15px;
}

.meeting-card p{
margin-bottom:10px;
}

.status{
display:inline-block;
margin-top:10px;
padding:8px 14px;
background:rgba(34,197,94,.15);
color:#22c55e;
border-radius:999px;
}

.meeting-modal{
position:fixed;
inset:0;
background:rgba(0,0,0,.7);
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

.meeting-modal-card{
width:min(600px,90%);
background:#111827;
padding:30px;
border-radius:24px;
}

.meeting-modal-card form{
display:flex;
flex-direction:column;
gap:15px;
}

.meeting-modal-card input,
.meeting-modal-card textarea{
padding:14px;
border:none;
border-radius:12px;
background:#1e293b;
color:white;
}

.submit-btn{
padding:14px;
border:none;
border-radius:12px;
background:#22c55e;
color:white;
cursor:pointer;
font-weight:bold;
}

.meeting-card{

position:relative;

overflow:hidden;

padding:28px;

border-radius:32px;

background:
linear-gradient(
135deg,
rgba(99,102,241,.14),
rgba(15,23,42,.95)
);

backdrop-filter:blur(20px);

border:
1px solid rgba(255,255,255,.08);

transition:.35s ease;

box-shadow:
0 10px 30px
rgba(0,0,0,.2);

}

.meeting-card:hover{

transform:
translateY(-8px);

box-shadow:
0 25px 50px
rgba(99,102,241,.25);

}

.meeting-section{

margin-top:18px;

padding-top:18px;

border-top:
1px solid rgba(255,255,255,.08);

}

.meeting-section h4{

font-size:.95rem;

margin-bottom:10px;

color:#cbd5e1;

}

.notes-preview{

font-size:.9rem;

line-height:1.6;

color:#94a3b8;

}

.meeting-actions{

display:flex;

gap:10px;

margin-top:20px;

}

.glass-btn{

display:inline-flex;

align-items:center;

justify-content:center;

padding:12px 20px;

border-radius:999px;

background:
rgba(99,102,241,.15);

border:
1px solid rgba(99,102,241,.2);

color:white;

text-decoration:none;

font-weight:600;

transition:.3s;
}

.glass-btn:hover{

background:
rgba(99,102,241,.3);

transform:
translateY(-2px);

}

</style>

<script>

function openNotesModal(id, notes){

    document
    .getElementById('modalMeetingId')
    .value = id;

    document
    .getElementById('modalMom')
    .value = notes;

    document
    .getElementById('notesModal')
    .classList.add('show-modal');
}

document
.getElementById('notesModal')
.addEventListener('click', function(e){

    if(e.target === this){

        this.classList.remove('show-modal');
    }
});

</script>

<?php
require_once dirname(__DIR__, 2)
. '/views/layouts/footer.php';
?>