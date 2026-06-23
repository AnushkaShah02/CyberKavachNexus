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

$registrationId =
$_GET['registration_id'] ?? 0;

$stmt = $db->prepare("
    SELECT *
    FROM participant_registrations
    WHERE id = ?
");

$stmt->execute([
    $registrationId
]);

$team = $stmt->fetch();

if(!$team){
    die('Team not found');
}

$stmtMembers = $db->prepare("
    SELECT *
    FROM participant_team_members
    WHERE registration_id = ?
");

$stmtMembers->execute([
    $registrationId
]);

$members = $stmtMembers->fetchAll();

$pageTitle = 'Team Details';

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

<h1>
<?= SecurityHelper::escape(
    $team['team_name']
) ?>
</h1>

<div class="team-card">

<h3>Team Leader</h3>

<div class="member-box">

<strong>
<?= SecurityHelper::escape(
    $team['participant_name']
) ?>
</strong>

<br>

<?= SecurityHelper::escape(
    $team['enrollment_no']
) ?>

<br>

<?= SecurityHelper::escape(
    $team['email']
) ?>

<br>

<?= SecurityHelper::escape(
    $team['phone']
) ?>

</div>

<h3 style="margin-top:25px;">
Team Members
</h3>

<?php foreach($members as $m): ?>

<div class="member-box">

<strong>
<?= SecurityHelper::escape(
    $m['participant_name']
) ?>
</strong>

<br>

<?= SecurityHelper::escape(
    $m['enrollment_no']
) ?>

<br>

<?= SecurityHelper::escape(
    $m['email']
) ?>

<br>

<?= SecurityHelper::escape(
    $m['phone']
) ?>

</div>

<?php endforeach; ?>

</div>

</main>

</div>

<style>

.team-card{
background:var(--bg-surface);
padding:24px;
border-radius:20px;
max-width:900px;
}

.member-box{
background:#111827;
padding:15px;
border-radius:12px;
margin-bottom:15px;
border:1px solid rgba(255,255,255,.08);
}

</style>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>