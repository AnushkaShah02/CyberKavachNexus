<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$db = Database::getConnection();

$eventId = $_GET['event_id'] ?? 0;

$stmt = $db->prepare("
    SELECT *
    FROM events
    WHERE id = ?
");

$stmt->execute([$eventId]);

$event = $stmt->fetch();

if(isset($_POST['register'])){

if(isset($_POST['register'])){

    if($event['registration_type'] === 'Individual'){

        $stmt = $db->prepare("
            INSERT INTO participant_registrations
            (
                event_id,
                participant_name,
                enrollment_no,
                email,
                phone
            )
            VALUES
            (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['event_id'],
            $_POST['participant_name'],
            $_POST['enrollment_no'],
            $_POST['email'],
            $_POST['phone']
        ]);
    }

    else{

        $stmt = $db->prepare("
            INSERT INTO participant_registrations
            (
                event_id,
                team_name,
                participant_name,
                enrollment_no,
                email,
                phone
            )
            VALUES
            (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['event_id'],
            $_POST['team_name'],
            $_POST['participant_name'][0],
            $_POST['enrollment_no'][0],
            $_POST['email'][0],
            $_POST['phone'][0]
        ]);

        $registrationId = $db->lastInsertId();

        for(
            $i = 1;
            $i < count($_POST['participant_name']);
            $i++
        ){

            $stmtMember = $db->prepare("
                INSERT INTO participant_team_members
                (
                    registration_id,
                    participant_name,
                    enrollment_no,
                    email,
                    phone
                )
                VALUES
                (?, ?, ?, ?, ?)
            ");

            $stmtMember->execute([
                $registrationId,
                $_POST['participant_name'][$i],
                $_POST['enrollment_no'][$i],
                $_POST['email'][$i],
                $_POST['phone'][$i]
            ]);
        }
    }

    $success = true;
}
}

if(!$event){
    die('Event not found');
}

?>

<!DOCTYPE html>
<html>
<head>

<title>
<?= SecurityHelper::escape($event['title']) ?>
</title>

<style>

body{
background:#0f172a;
font-family:Arial,sans-serif;
color:white;
display:flex;
justify-content:center;
padding:40px;
}

.registration-card{
width:100%;
max-width:700px;
background:#111827;
padding:30px;
border-radius:20px;
}

input{
width:100%;
padding:12px;
border:none;
border-radius:10px;
margin-bottom:15px;
background:#1e293b;
color:white;
}

button{
width:100%;
padding:14px;
border:none;
border-radius:12px;
background:#22c55e;
color:white;
font-weight:bold;
cursor:pointer;
}

.success-box{
    background:#22c55e;
    color:white;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
    text-align:center;
}

</style>

</head>

<body>

<div class="registration-card">

<?php if(!empty($success)): ?>

<div class="success-box">
    Registration Successful ✅
</div>

<?php endif; ?>

<h1>
    <?= SecurityHelper::escape($event['title']) ?>
</h1>

<p>
    <?= SecurityHelper::escape($event['description']) ?>
</p>

<p>
    Registration Type:
    <?= SecurityHelper::escape($event['registration_type']) ?>
</p>

<form method="POST">

<input
    type="hidden"
    name="event_id"
    value="<?= $event['id'] ?>">

<?php if($event['registration_type'] === 'Team'): ?>

<input
    type="text"
    name="team_name"
    placeholder="Team Name"
    required>

<br><br>

<?php
for($i = 1; $i <= $event['max_team_size']; $i++):
?>

<h3>
Member <?= $i ?>
</h3>

<input
    type="text"
    name="participant_name[]"
    placeholder="Full Name"
    required>

<input
    type="text"
    name="enrollment_no[]"
    placeholder="Enrollment Number"
    required>

<input
    type="email"
    name="email[]"
    placeholder="Email"
    required>

<input
    type="text"
    name="phone[]"
    placeholder="Phone Number"
    required>

<br>

<?php endfor; ?>

<?php endif; ?>

<?php if($event['registration_type'] === 'Individual'): ?>

<input
    type="hidden"
    name="event_id"
    value="<?= $event['id'] ?>">



<input
    type="text"
    name="participant_name"
    placeholder="Full Name"
    required>



<input
    type="text"
    name="enrollment_no"
    placeholder="Enrollment Number"
    required>



<input
    type="email"
    name="email"
    placeholder="Email"
    required>



<input
    type="text"
    name="phone"
    placeholder="Phone Number"
    required>


<?php endif; ?>

<button
    type="submit"
    name="register">

    Register

</button>

</form>
</div>
</body>
</html>