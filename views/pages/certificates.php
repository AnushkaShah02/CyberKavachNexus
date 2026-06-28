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

$eventId = $_GET['event_id'] ?? 0;

// ─── VIEW 1: EVENT SELECTION LIST (If no event_id is specified) ───
if ($eventId == 0) {
    $pageTitle = 'Manage Certificates';
    
    // Fetch all approved or completed events to display
    $stmtEvents = $db->prepare("
        SELECT * FROM events 
        WHERE status IN ('Approved', 'Completed') 
        ORDER BY start_time DESC
    ");
    $stmtEvents->execute();
    $events = $stmtEvents->fetchAll();

    require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
    ?>
    <div class="app-container">
        <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>
        <main class="main-content">
            <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>
            
            <div style="margin-bottom: 2rem;">
                <h1 class="page-title">Manage Event Certificates</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Select an active or completed event below to generate, view, and distribute certificates to participants.</p>
            </div>

            <div class="kavach-card" style="padding: 1.5rem; overflow-x: auto;">
                <?php if (empty($events)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem; font-size: 0.9rem;">No active or completed events found to generate certificates.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <th style="padding: 12px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Event Name</th>
                                <th style="padding: 12px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Location</th>
                                <th style="padding: 12px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Date</th>
                                <th style="padding: 12px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Status</th>
                                <th style="padding: 12px; text-align: right; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='none'">
                                <td style="padding: 16px 12px; font-weight: 600; color: #fff;">
                                    <?= SecurityHelper::escape($ev['title']) ?>
                                </td>
                                <td style="padding: 16px 12px; color: var(--text-muted); font-size: 0.9rem;">
                                    <?= SecurityHelper::escape($ev['location']) ?>
                                </td>
                                <td style="padding: 16px 12px; color: var(--text-muted); font-size: 0.9rem;">
                                    <?= date('M d, Y', strtotime($ev['start_time'])) ?>
                                </td>
                                <td style="padding: 16px 12px;">
                                    <span class="chip success" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                                        <?= SecurityHelper::escape($ev['status']) ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right;">
                                    <a href="?event_id=<?= $ev['id'] ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; border-radius: 6px;">
                                        <i data-lucide="award" style="width: 14px; height: 14px;"></i>
                                        Manage
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php
    require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
    exit;
}

// ─── VIEW 2: CERTIFICATE GENERATION/EMAIL PANEL (When event_id is passed) ───
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

// ── SEND EMAIL QUEUE WITH MEMORY-BASED ON-THE-FLY GENERATION ──
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
        // Generate the PDF in memory
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $type = $certificate['certificate_type'];
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

        $charusatLogoPath = dirname(__DIR__,2) . '/public/assets/storage/logos/charusat_logo.png';
        $cyberLogoPath = dirname(__DIR__,2) . '/public/assets/storage/logos/cyberkavach_logo.png';

        $charusatLogo = '';
        if (file_exists($charusatLogoPath)) {
            $charusatLogo = 'data:image/png;base64,' . base64_encode(file_get_contents($charusatLogoPath));
        }
        $cyberLogo = '';
        if (file_exists($cyberLogoPath)) {
            $cyberLogo = 'data:image/png;base64,' . base64_encode(file_get_contents($cyberLogoPath));
        }

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
        <h2>' . htmlspecialchars($certificate['participant_name']) . '</h2>
        <p>' . $achievementText . '</p>
        <h3>' . htmlspecialchars($event['title']) . '</h3>
        <p>Issued on: ' . date('d M Y') . '</p>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','landscape');
        $dompdf->render();

        // Save PDF to a temporary file (works on Windows, Linux, Render, and Railway)
        $tempPdfPath = tempnam(sys_get_temp_dir(), 'cert_') . '.pdf';
        file_put_contents($tempPdfPath, $dompdf->output());

        // Send the email with the temporary attachment
        MailHelper::sendCertificate(
            $certificate['email'],
            $certificate['participant_name'],
            $tempPdfPath
        );

        // Delete the temporary file from the disk immediately
        if (file_exists($tempPdfPath)) {
            unlink($tempPdfPath);
        }
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
                    SELECT id FROM certificates
                    WHERE event_id = ? AND participant_name = ? AND certificate_type = ?
                ");
                $stmtCheck->execute([
                    $eventId,
                    $participant['participant_name'],
                    $type
                ]);

                if ($stmtCheck->fetch()) {
                    continue;
                }   
                
                $certificateCode = 'CK-' . strtoupper(substr(md5(uniqid()),0,10));

                $stmtCheckCertificate = $db->prepare("
                    SELECT id FROM certificates
                    WHERE event_id = ? AND enrollment_no = ? AND certificate_type = ?
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
                    (event_id, participant_name, enrollment_no, certificate_type, certificate_code, pdf_path)
                    VALUES
                    (?, ?, ?, ?, ?, ?)
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
                    null // Storing NULL because we generate files dynamically during emails
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
    WHERE event_id = ? AND team_name IS NOT NULL AND team_name != ''
");
$stmtTeams->execute([$eventId]);
$totalTeams = $stmtTeams->fetchColumn();

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>
    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <div style="margin-bottom: 1.5rem;">
            <a href="certificates.php" style="color: var(--color-primary); font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                ← Back to Event List
            </a>
        </div>

        <h1><?= SecurityHelper::escape($event['title']) ?></h1>

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
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">

            <input type="text" id="teamSearch" placeholder="Search Team Name..." style="width:100%; padding:12px; border-radius:10px; margin-bottom:20px; border:1px solid #ccc;">

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
                            <td><?= SecurityHelper::escape($teamName) ?></td>
                            <td>
                                <?php foreach ($team['members'] as $member): ?>
                                <div><?= SecurityHelper::escape($member['participant_name']) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach ($team['members'] as $member): ?>
                                <div><?= SecurityHelper::escape($member['enrollment_no']) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <select name="certificate_type[<?= md5($teamName) ?>]">
                                    <option value="Participation">Participation</option>
                                    <option value="Winner">Winner</option>
                                    <option value="Runner-up">Runner-up</option>
                                    <option value="Coordinator">Coordinator</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" name="generate" class="generate-btn">
                    Generate Certificates
                </button>

                <button type="submit" name="send_emails" class="generate-btn">
                    Send Certificates
                </button>
            </div>
        </form>
    </main>
</div>

<style>
.certificate-card {
    background:var(--bg-surface);
    padding:20px;
    border-radius:20px;
    overflow:auto;
}
table {
    width:100%;
    border-collapse:collapse;
}
th, td {
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,.08);
}
select {
    padding:8px;
    border-radius:8px;
}
.generate-btn {
    margin-top:20px;
    padding:14px 24px;
    background:#2563eb;
    border:none;
    border-radius:12px;
    color:white;
    cursor:pointer;
}
.success-box {
    background:#22c55e;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
    color:white;
}
</style>

<script>
document.getElementById('teamSearch').addEventListener('keyup', function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll('.team-row').forEach(function(row){
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>