<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);



// views/pages/login.php

$pageTitle = 'Login - CyberKavach Nexus';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';



// Redirect if user is already logged in
if (!empty($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $projectUrlPath = '';
    if (preg_match('/^(.*?)\/(public|views|src|config)/', $_SERVER['SCRIPT_NAME'] ?? '', $matches)) {
        $projectUrlPath = $matches[1];
    }
    header('Location: ' . $projectUrlPath . '/public/index.php/views/pages/dashboard.php');
    exit;
}
?>


<div style="display: flex; min-height: 100vh; justify-content: center; align-items: center; padding: 2rem; background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 45%), radial-gradient(circle at 90% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 45%);">
    <div class="glass-container" style="width: 100%; max-width: 440px; text-align: center;">
        
        <!-- Header / Logo -->
        <div style="margin-bottom: 2rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            <div style="width: 60px; height: 60px; border-radius: 16px; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);">
                <i data-lucide="shield-check" style="width: 32px; height: 32px; color: white;"></i>
            </div>
            <h2 style="font-size: 1.75rem; margin-top: 1rem;">CyberKavach Club</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Nexus Smart Club Management System</p>
        </div>

        <!-- Form -->
        <form id="loginForm" novalidate>
            <div class="form-group" style="text-align: left;">
                <label class="form-label" for="email">Club Email</label>
                <input class="form-control" type="email" id="email" placeholder="name@cyberkavach.org" required>
            </div>

            <div class="form-group" style="text-align: left; margin-bottom: 2rem;">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" placeholder="••••••••" required>
            </div>

            <button class="btn btn-primary" type="submit" style="width: 100%;">
                <span>Access Dashboard</span>
                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </button>
        </form>

        <!-- Sandbox login helper panel -->
        <div style="margin-top: 2.5rem; background: rgba(30, 38, 66, 0.4); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.2rem; text-align: left; font-size: 0.8rem;">
            <div style="color: var(--color-warning); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <i data-lucide="info" style="width: 14px; height: 14px;"></i>
                <span>Sandbox Demo Access Logs:</span>
            </div>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.4rem; color: var(--text-muted);">
                <li>🔑 <strong>Faculty:</strong> <code>ramesh.faculty@cyberkavach.org</code></li>
                <li>🔑 <strong>Student:</strong> <code>aarav.student@cyberkavach.org</code></li>
                <li>🔑 <strong>Member:</strong> <code>neha.member@cyberkavach.org</code></li>
                <li style="margin-top: 0.3rem; border-top: 1px solid var(--color-border); padding-top: 0.3rem; font-style: italic;">Password for all: <code>Kavach@2026</code></li>
            </ul>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showToast('Please fill in all credentials.', 'warning');
            return;
        }

        try {
            const response = await fetch('/cyber2/public/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
                },
                body: JSON.stringify({ email, password })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Authentication successful! Welcome to Nexus.', 'success');
                setTimeout(() => {
                    window.location.href = result.data.redirect;
                }, 800);
            } else {
                showToast(result.message || 'Login failed.', 'danger');
            }
        } catch (error) {
            console.error('Login error:', error);
            showToast('Network error, please verify XAMPP configurations.', 'danger');
        }
    });
});
</script>

<?php
require_once dirname(__DIR__, 2) . '/views/layouts/footer.php';
?>
