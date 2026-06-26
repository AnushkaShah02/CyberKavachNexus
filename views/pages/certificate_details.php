<?php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

use Dompdf\Dompdf;
use Dompdf\Options;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use CyberKavach\Nexus\Helpers\MailHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

AuthMiddleware::handle();

$db = Database::getConnection();

echo "<pre>";
echo "ENV DB = ";
var_dump($_ENV['DB_DATABASE'] ?? null);

echo "GETENV DB = ";
var_dump(getenv('DB_DATABASE'));

echo "CONNECTED DB = ";
echo $db->query("SELECT DATABASE()")->fetchColumn();

exit;

$eventId = $_GET['event_id'] ?? 0;

$stmt = $db->prepare("
    SELECT *
    FROM events
    WHERE id = ?
");

$stmt->execute([$eventId]);

$event = $stmt->fetch();

if (!$event) {
    die('Event not found');
}

$stmtParticipants = $db->prepare("
SELECT
    pr.id,
    pr.team_name,
    pr.participant_name,
    pr.enrollment_no,
    pr.email,
    pr.phone
FROM participant_registrations pr
INNER JOIN participant_attendance pa
    ON pa.registration_id = pr.id
WHERE
    pr.event_id = ?
    AND pa.attendance_status = 'Present'

UNION ALL

SELECT
    ptm.id + 100000,
    pr.team_name,
    ptm.participant_name,
    ptm.enrollment_no,
    ptm.email,
    ptm.phone
FROM participant_team_members ptm
INNER JOIN participant_registrations pr
    ON pr.id = ptm.registration_id
INNER JOIN participant_attendance pa
    ON pa.registration_id = pr.id
WHERE
    pr.event_id = ?
    AND pa.attendance_status = 'Present'

ORDER BY team_name, participant_name
");

$stmtParticipants->execute([
    $eventId,
    $eventId
]);
$participants = $stmtParticipants->fetchAll();

$teams = [];

foreach ($participants as $p) {

    $teamName = $p['team_name'] ?: 'Individual';

    if (!isset($teams[$teamName])) {

        $teams[$teamName] = [
            'members' => []
        ];
    }

    $teams[$teamName]['members'][] = $p;
}

if(isset($_POST['send_emails'])){

    $stmtCertificates = $db->prepare("
        SELECT
            c.*,
            pr.email
        FROM certificates c
        JOIN participant_registrations pr
            ON pr.enrollment_no = c.enrollment_no
        WHERE c.event_id = ?
    ");

    $stmtCertificates->execute([$eventId]);

    $certificates = $stmtCertificates->fetchAll();

    foreach($certificates as $certificate){

        $pdfPath = $certificate['pdf_path'];

        echo "<pre>";

echo "Email: " . $certificate['email'] . PHP_EOL;
echo "PDF Path: " . $pdfPath . PHP_EOL;

if (!file_exists($pdfPath)) {
    die("❌ PDF NOT FOUND: " . $pdfPath);
}

echo "✅ PDF FOUND" . PHP_EOL;

$result = MailHelper::sendCertificate(
    $certificate['email'],
    $certificate['participant_name'],
    $pdfPath
);

echo "Mail Result: ";
var_dump($result);

echo "</pre>";

exit;
    }

    $emailSuccess = true;
}



if (isset($_POST['generate'])) {
foreach ($_POST['certificate_type'] as $teamNameHash => $type) {

    foreach ($teams as $teamName => $team) {

        if (md5($teamName) !== $teamNameHash) {
            continue;
        }

        foreach ($team['members'] as $participant) {
        
        $stmtCheck = $db->prepare("
SELECT id
FROM certificates
WHERE
    event_id = ?
    AND participant_name = ?
    AND certificate_type = ?
");

$stmtCheck->execute([
    $eventId,
    $participant['participant_name'],
    $type
]);

if ($stmtCheck->fetch()) {
    continue;
}   
        
        $certificateCode =
            'CK-' .
            strtoupper(substr(md5(uniqid()),0,10));

            $options = new Options();
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);

            $certificateTitle = 'Certificate of Participation';

            if($type === 'Winner'){
                $certificateTitle = 'Certificate of Achievement';
            }

            if($type === 'Runner-up'){
                $certificateTitle = 'Certificate of Excellence';
            }

            if($type === 'Coordinator'){
                $certificateTitle = 'Certificate of Appreciation';
            }

            $charusatLogoPath =
            dirname(__DIR__,2)
            . '/public/assets/storage/logos/charusat_logo.png';

            $cyberLogoPath =
            dirname(__DIR__,2)
            . '/public/assets/storage/logos/cyberkavach_logo.png';

            $charusatLogo =
            'data:image/png;base64,' .
            base64_encode(file_get_contents($charusatLogoPath));

            $cyberLogo =
            'data:image/png;base64,' .
            base64_encode(file_get_contents($cyberLogoPath));

            $achievementText = 'for successfully participating in';

if ($type === 'Winner') {
    $achievementText = 'for securing <b>WINNER</b> in';
}

if ($type === 'Runner-up') {
    $achievementText = 'for securing <b>RUNNER-UP</b> in';
}

if ($type === 'Coordinator') {
    $achievementText = 'for valuable contribution as <b>EVENT COORDINATOR</b> for';
}

            $html = '
            <html>
            <body style="
            font-family:Arial;
            text-align:center;
            padding:40px;
            border:10px solid #2563eb;
            ">

            <table width="100%">
            <tr>
            <td align="left">
            <img src="' . $charusatLogo . '" width="120">
            </td>

            <td align="right">
            <img src="' . $cyberLogo . '" width="120">
            </td>
            </tr>
            </table>

            <h1>' . $certificateTitle . '</h1>

            <p>This certificate is proudly awarded to</p>

            <h2>' .
            htmlspecialchars($participant['participant_name']) .
            '</h2>

            <p>' . $achievementText . '</p>

            <h3>' .
            htmlspecialchars($event['title']) .
            '</h3>

            <p>
            Issued on:
            '.date('d M Y').'
            </p>

            </body>
            </html>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4','landscape');
            $dompdf->render();
    

            $pdfFileName =
            $certificateCode . '.pdf';

      $pdfDirectory = dirname(__DIR__, 2) . '/storage/certificates/';

if (!is_dir($pdfDirectory)) {
    mkdir($pdfDirectory, 0777, true);
}

$pdfPath = $pdfDirectory . $pdfFileName;



if (file_put_contents($pdfPath, $dompdf->output()) === false) {
    die("FAILED TO SAVE PDF");
}


if (!file_exists($pdfPath)) {
    die("PDF DOES NOT EXIST AFTER SAVING");
}


            $stmtCheckCertificate = $db->prepare("
SELECT id
FROM certificates
WHERE
    event_id = ?
    AND enrollment_no = ?
    AND certificate_type = ?
");

$stmtCheckCertificate->execute([
    $eventId,
    $participant['enrollment_no'],
    $type
]);

if ($stmtCheckCertificate->fetch()) {
    continue;
}

            $stmtInsert = $db->prepare("
            INSERT INTO certificates
            (
                event_id,
                participant_name,
                enrollment_no,
                certificate_type,
                certificate_code,
                pdf_path
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?
            )
            ");

            if (!$stmtInsert) {
    die("<pre>PREPARE FAILED\n" . print_r($db->errorInfo(), true) . "</pre>");
}



         $result = $stmtInsert->execute([
    $eventId,
    $participant['participant_name'],
    $participant['enrollment_no'],
    $type,
    $certificateCode,
   $pdfPath
]);





        }
    }
}

$success = true;
        }

$participantMap = [];

foreach ($participants as $p) {
    $participantMap[$p['id']] = $p;
}

$pageTitle = 'Generate Certificates';

$stmtRegistered = $db->prepare("
    SELECT COUNT(*)
    FROM participant_registrations
    WHERE event_id = ?
");

$stmtRegistered->execute([$eventId]);

$totalRegistered = $stmtRegistered->fetchColumn();

$stmtTeams = $db->prepare("
    SELECT COUNT(DISTINCT team_name)
    FROM participant_registrations
    WHERE
        event_id = ?
        AND team_name IS NOT NULL
        AND team_name != ''
");

$stmtTeams->execute([$eventId]);

$totalTeams = $stmtTeams->fetchColumn();

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">

<?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

<main class="main-content">

<?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

<h1>
<?= SecurityHelper::escape($event['title']) ?>
</h1>

<?php if (!empty($success)): ?>

<div class="success-box">
    Certificates Generated Successfully ✅
</div>

<?php endif; ?>

<?php if (!empty($emailSuccess)): ?>

<div class="success-box">
    Certificates Sent Successfully 📧
</div>

<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?= SecurityHelper::generateCsrfToken() ?>">

<input
    type="text"
    id="teamSearch"
    placeholder="Search Team Name..."
    style="
        width:100%;
        padding:12px;
        border-radius:10px;
        margin-bottom:20px;
        border:1px solid #ccc;
    ">

<div class="certificate-card">

<table>

<thead>

<tr>
    <th>Team</th>
    <th>Name</th>
    <th>Enrollment</th>
    <th>Certificate Type</th>
</tr>

</thead>

<tbody>

<?php foreach ($teams as $teamName => $team): ?>

<tr class="team-row">

<td>
<?= SecurityHelper::escape($teamName) ?>
</td>

<td>

<?php foreach ($team['members'] as $member): ?>

<div>
<?= SecurityHelper::escape(
    $member['participant_name']
) ?>
</div>

<?php endforeach; ?>

</td>

<td>

<?php foreach ($team['members'] as $member): ?>

<div>
<?= SecurityHelper::escape(
    $member['enrollment_no']
) ?>
</div>

<?php endforeach; ?>

</td>

<td>

<select
name="certificate_type[<?= md5($teamName) ?>]">

<option value="Participation">
Participation
</option>

<option value="Winner">
Winner
</option>

<option value="Runner-up">
Runner-up
</option>

<option value="Coordinator">
Coordinator
</option>

</select>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<button
    type="submit"
    name="generate"
    class="generate-btn">

    Generate Certificates

</button>

<button
    type="submit"
    name="send_emails"
    class="generate-btn">

Send Certificates

</button>

</div>

</form>

</main>

</div>

<style>

.certificate-card{
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
    border-bottom:1px solid rgba(255,255,255,.08);
}

select{
    padding:8px;
    border-radius:8px;
}

.generate-btn{
    margin-top:20px;
    padding:14px 24px;
    background:#2563eb;
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

<script>

document
.getElementById('teamSearch')
.addEventListener('keyup', function(){

    let value =
    this.value.toLowerCase();

    document
    .querySelectorAll('.team-row')
    .forEach(function(row){

        let text =
        row.innerText.toLowerCase();

        row.style.display =
        text.includes(value)
        ? ''
        : 'none';

    });

});

</script>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>