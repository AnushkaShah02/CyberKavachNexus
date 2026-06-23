<?php
// views/pages/verify.php

use CyberKavach\Nexus\Models\Certificate;
use CyberKavach\Nexus\Helpers\SecurityHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$query = trim($_GET['query'] ?? $_GET['hash'] ?? $_GET['uuid'] ?? '');
$cert = null;
$searched = false;

if (!empty($query)) {
    $certModel = new Certificate();
    $cert = $certModel->verify($query);
    $searched = true;
}

$pageTitle = 'Certificate Verification - CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div style="display: flex; min-height: 100vh; flex-direction: column; justify-content: center; align-items: center; padding: 2rem; background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 45%), radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 45%);">
    
    <!-- logo / header -->
    <div style="margin-bottom: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
        <i data-lucide="award" style="width: 48px; height: 48px; color: var(--color-primary);"></i>
        <h2 style="font-size: 1.75rem; margin-top: 0.5rem;">Kavach Registry Portal</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Public certificate and badge verification system.</p>
    </div>

    <div class="glass-container" style="width: 100%; max-width: 580px; padding: 2.5rem;">
        
        <!-- Search bar -->
        <form method="GET" action="" style="display: flex; gap: 0.75rem; margin-bottom: 2rem;">
            <div style="flex: 1; position: relative;">
                <input class="form-control" type="text" name="query" placeholder="Enter Verification Hash or Certificate UUID" value="<?= SecurityHelper::escape($query) ?>" required style="padding-left: 2.5rem;">
                <i data-lucide="search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-dark);"></i>
            </div>
            <button class="btn btn-primary" type="submit">Verify</button>
        </form>

        <?php if ($searched): ?>
            <?php if ($cert): ?>
                <!-- Verified Display Card -->
                <div style="border: 1px solid var(--color-success); background: rgba(16, 185, 129, 0.04); border-radius: 16px; padding: 2rem; text-align: center; position: relative; overflow: hidden; animation: fadeIn 0.4s ease;">
                    <div style="position: absolute; right: -20px; top: -20px; opacity: 0.05;">
                        <i data-lucide="shield-check" style="width: 150px; height: 150px; color: var(--color-success);"></i>
                    </div>

                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.15); color: var(--color-success); padding: 0.4rem 1rem; border-radius: 50px; font-weight: 700; font-size: 0.85rem; margin-bottom: 1.5rem;">
                        <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                        <span>VERIFIED RECORD</span>
                    </div>

                    <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= SecurityHelper::escape($cert['username']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;"><?= SecurityHelper::escape($cert['email']) ?></p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; border-top: 1px solid var(--color-border); padding-top: 1.5rem; text-align: left; font-size: 0.85rem;">
                        <div>
                            <span style="color: var(--text-dark); display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Club Event</span>
                            <strong><?= SecurityHelper::escape($cert['event_title']) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-dark); display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Award Level</span>
                            <strong><?= SecurityHelper::escape($cert['type']) ?></strong>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <span style="color: var(--text-dark); display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Date Issued</span>
                            <strong><?= date('M d, Y', strtotime($cert['issue_date'])) ?></strong>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <span style="color: var(--text-dark); display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">UUID Reference</span>
                            <span style="font-family: monospace; font-size: 0.8rem;"><?= SecurityHelper::escape(substr($cert['uuid'], 0, 18)) ?>...</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Found display card -->
                <div style="border: 1px solid var(--color-danger); background: rgba(239, 68, 68, 0.04); border-radius: 16px; padding: 2.5rem; text-align: center; animation: fadeIn 0.4s ease;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); color: var(--color-danger); margin-bottom: 1rem;">
                        <i data-lucide="alert-octagon" style="width: 28px; height: 28px;"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; color: var(--text-main); margin-bottom: 0.5rem;">Verification Failed</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">No certificate records match the query provided. Please verify the verification code or QR payload and try again.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="/views/pages/login.php" style="color: var(--color-primary); text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                <span>Return to Login</span>
            </a>
        </div>

    </div>
</div>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>
