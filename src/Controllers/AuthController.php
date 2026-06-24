<?php
// src/Controllers/AuthController.php

namespace CyberKavach\Nexus\Controllers;

use CyberKavach\Nexus\Models\User;
use CyberKavach\Nexus\Models\AuditLog;
use CyberKavach\Nexus\Helpers\ResponseHelper;

class AuthController {
    private User $userModel;
    private AuditLog $auditLog;

    public function __construct() {
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
    }

    /**
     * Authenticate users and establish session.
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Apply brute-force rate-limiting
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }

        if ($_SESSION['login_attempts'] >= 5) {
            if (isset($_SESSION['lockout_time']) && (time() - $_SESSION['lockout_time'] < 300)) {
                $rem = 300 - (time() - $_SESSION['lockout_time']);
                ResponseHelper::sendJsonResponse(false, 429, "Too many login attempts. Lockout active. Try again in {$rem} seconds.");
            } else {
                unset($_SESSION['lockout_time']);
                $_SESSION['login_attempts'] = 0;
            }
        }

        // Read POST raw request body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            ResponseHelper::sendJsonResponse(false, 400, 'Email and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::sendJsonResponse(false, 400, 'Invalid email format.');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time();
            }

            $this->auditLog->log(null, 'LOGIN_FAILED', "Failed login attempt for: {$email}");
            ResponseHelper::sendJsonResponse(false, 401, 'Invalid email or password.');
        }

        if ($user['status'] !== 'Active') {
            ResponseHelper::sendJsonResponse(false, 403, 'Your account is suspended or pending approval.');
        }

        // Clean rate limits
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['lockout_time']);

        // Prevent session fixation exploits
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'            => (int)$user['id'],
            'username'      => $user['username'],
            'email'         => $user['email'],
            'role_id'       => (int)$user['role_id'],
            'role_name'     => $user['role_name'],
            'reward_points' => (int)$user['reward_points']
        ];

        $this->auditLog->log((int)$user['id'], 'LOGIN_SUCCESS', 'User logged in.');
        
       // Splash screen sirf login ke baad dikhani hai
$_SESSION['show_splash'] = true;

ResponseHelper::sendJsonResponse(true, 200, 'Login successful.', [
    'redirect' => '/dashboard'
]);
    }

    /**
     * Terminate active session and destroy session cookie details.
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user']['id'] ?? null;
        if ($userId) {
            $this->auditLog->log((int)$userId, 'LOGOUT', 'User logged out.');
        }

        // Clear all session details
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        ResponseHelper::sendJsonResponse(true, 200, 'Logged out successfully.');
    }
}
