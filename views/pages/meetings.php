<?php
// views/pages/meetings.php

use CyberKavach\Nexus\Config\Database;
use CyberKavach\Nexus\Middleware\AuthMiddleware;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

AuthMiddleware::handle();

$db = Database::getConnection();
$user = $_SESSION['user'];

// Handle Meeting Creation
if (isset($_POST['create_meeting']) && $user['role_name'] === 'Faculty Coordinator') {
    if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        die('Invalid CSRF Token');
    }
    $stmt = $db->prepare("
        INSERT INTO club_meetings (title, meeting_date, venue, agenda, created_by)
        VALUES (?, ?, ?, ?, ?)
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

// Handle Discussion Notes Saving
if(isset($_POST['save_mom'])){
    if(!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)){
        die('Invalid CSRF Token');
    }
    $stmt = $db->prepare("
        UPDATE club_meetings SET discussion_notes = ? WHERE id = ?
    ");
    $stmt->execute([
        $_POST['discussion_notes'],
        $_POST['meeting_id']
    ]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch Meetings
$stmt = $db->query("SELECT * FROM club_meetings ORDER BY meeting_date DESC");
$meetings = $stmt->fetchAll();

$pageTitle = 'Club Meetings — CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<style>
/* Meetings Timeline Layout */
.meetings-timeline {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.meetings-timeline::before {
    content: '';
    position: absolute;
    top: 0; bottom: 0; left: 24px;
    width: 2px;
    background: linear-gradient(to bottom, var(--color-primary), var(--color-secondary), transparent);
}

.meeting-node {
    position: relative;
    padding-left: 4rem;
    margin-bottom: 3rem;
}

.meeting-dot {
    position: absolute;
    left: 17px;
    top: 1.5rem;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--color-primary);
    border: 3px solid var(--bg-main);
    box-shadow: 0 0 15px var(--color-primary);
    z-index: 2;
}

.meeting-card {
    background: var(--bg-surface);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 2rem;
    transition: var(--transition-bounce);
    position: relative;
}

.meeting-card:hover {
    transform: translateY(-5px);
    border-color: var(--color-primary-glow);
    box-shadow: var(--shadow-glow);
}

/* Connecting Line */
.meeting-card::before {
    content: '';
    position: absolute;
    top: 1.5rem;
    left: -2rem;
    width: 2rem;
    height: 2px;
    background: var(--color-border);
    z-index: 1;
}

.meeting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.meeting-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.meeting-date {
    color: var(--color-primary);
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meeting-venue {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.meeting-agenda {
    margin: 1.5rem 0;
    padding: 1rem;
    background: rgba(0,0,0,0.2);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--color-secondary);
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--text-main);
}

.meeting-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
}

/* Modal Styling reusing previous definitions */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition-smooth);
}

.modal-overlay.show-modal {
    opacity: 1;
    visibility: visible;
}

.modal-card {
    background: var(--bg-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    width: 90%;
    max-width: 600px;
    padding: 2.5rem;
    transform: scale(0.95);
    transition: var(--transition-bounce);
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
}

.modal-overlay.show-modal .modal-card {
    transform: scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.modal-header h2 {
    font-size: 1.5rem;
}

.close-modal-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: var(--transition-smooth);
    border: none;
    cursor: pointer;
}

.close-modal-btn:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
</style>

<div class="app-container">
    <?php require_once dirname(__DIR__, 2) . '/views/components/sidebar.php'; ?>

    <main class="main-content">
        <?php require_once dirname(__DIR__, 2) . '/views/components/navbar.php'; ?>

        <div class="section-header animate-on-scroll">
            <div>
                <h1 class="page-title" style="font-size: 2rem; margin-bottom: 0.5rem;">Club Meetings</h1>
                <p style="color: var(--text-muted);">Manage committee schedules, agendas, and discussion notes.</p>
            </div>

            <?php if($user['role_name'] === 'Faculty Coordinator'): ?>
                <button class="btn btn-primary" onclick="document.getElementById('meetingModal').classList.add('show-modal')">
                    <i data-lucide="plus"></i> Schedule Meeting
                </button>
            <?php endif; ?>
        </div>

        <div class="meetings-timeline">
            <?php foreach($meetings as $i => $meeting): ?>
                <div class="meeting-node animate-on-scroll" style="animation-delay: <?= ($i % 5) * 0.1 ?>s;">
                    <div class="meeting-dot"></div>
                    <div class="meeting-card">
                        <div class="meeting-header">
                            <div>
                                <h3 class="meeting-title"><?= SecurityHelper::escape($meeting['title']) ?></h3>
                                <div class="meeting-venue">
                                    <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i> 
                                    <?= SecurityHelper::escape($meeting['venue']) ?>
                                </div>
                            </div>
                            <div class="meeting-date">
                                <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                                <?= date('d M Y - h:i A', strtotime($meeting['meeting_date'])) ?>
                            </div>
                        </div>

                        <div class="meeting-agenda">
                            <strong>Agenda:</strong><br>
                            <?= nl2br(SecurityHelper::escape($meeting['agenda'])) ?>
                        </div>

                        <?php if(!empty($meeting['discussion_notes'])): ?>
                            <div style="margin-top: 1rem;">
                                <strong><i data-lucide="file-text" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i> Notes Preview:</strong>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    <?= nl2br(SecurityHelper::escape(substr($meeting['discussion_notes'], 0, 150))) ?>...
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="meeting-actions">
                            <a href="<?= SecurityHelper::asset('views/pages/meeting_attendance.php?meeting_id=' . $meeting['id']) ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                                <i data-lucide="users"></i> Attendance
                            </a>
                            
                            <button type="button" class="btn btn-primary" style="padding: 0.5rem 1rem;" onclick="openNotesModal('<?= $meeting['id'] ?>', `<?= htmlspecialchars($meeting['discussion_notes'] ?? '', ENT_QUOTES) ?>`)">
                                <i data-lucide="file-edit"></i> <?= empty($meeting['discussion_notes']) ? 'Add Notes' : 'Edit Notes' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Create Meeting Modal -->
<div class="modal-overlay" id="meetingModal" onclick="if(event.target===this)this.classList.remove('show-modal')">
    <div class="modal-card">
        <div class="modal-header">
            <h2>Schedule Meeting</h2>
            <button class="close-modal-btn" onclick="document.getElementById('meetingModal').classList.remove('show-modal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
            <div class="form-group">
                <label class="form-label">Meeting Title</label>
                <input type="text" name="title" class="form-input" placeholder="e.g. Core Committee Sync" required>
            </div>
            <div class="form-group">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" name="meeting_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Venue</label>
                <input type="text" name="venue" class="form-input" placeholder="e.g. CSPIT Lab 1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Agenda</label>
                <textarea name="agenda" class="form-input" rows="4" placeholder="Points to discuss..." required></textarea>
            </div>
            <button type="submit" name="create_meeting" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i data-lucide="calendar-plus"></i> Schedule Meeting
            </button>
        </form>
    </div>
</div>

<!-- Discussion Notes Modal -->
<div class="modal-overlay" id="notesModal" onclick="if(event.target===this)this.classList.remove('show-modal')">
    <div class="modal-card" style="max-width: 700px;">
        <div class="modal-header">
            <h2>Discussion Notes</h2>
            <button type="button" class="close-modal-btn" onclick="document.getElementById('notesModal').classList.remove('show-modal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">
            <input type="hidden" name="meeting_id" id="modalMeetingId">
            <div class="form-group">
                <textarea name="discussion_notes" id="modalMom" class="form-input" rows="12" placeholder="Document the key decisions and action items here..."></textarea>
            </div>
            <button type="submit" name="save_mom" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i data-lucide="save"></i> Save Notes
            </button>
        </form>
    </div>
</div>

<script>
function openNotesModal(id, notes) {
    document.getElementById('modalMeetingId').value = id;
    document.getElementById('modalMom').value = notes;
    document.getElementById('notesModal').classList.add('show-modal');
}
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>
