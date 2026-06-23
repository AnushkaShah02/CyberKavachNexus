<?php
// config/security.php

namespace CyberKavach\Nexus\Config;

class Security {
    /**
     * Set secure response headers to harden the application against standard attacks.
     */
    public static function setHeaders(): void {
        if (headers_sent()) {
            return;
        }

        // Prevent Clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection filter in older browsers
        header('X-XSS-Protection: 1; mode=block');

        // Control referrer details passed
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Strict Content Security Policy (Allowing essential CDNs for Chart.js and Font libraries)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " . // Allowed CDN for Chart.js & Lucide
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        header("Content-Security-Policy: {$csp}");
    }

    /**
     * Start and configure PHP session settings securely.
     */
    public static function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Determine if request is running over HTTPS
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                        || ($_SERVER['SERVER_PORT'] == 443);

            // Configure cookies flags
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie expires when browser closes
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true, // Thwart JavaScript reads of the session ID
                'samesite' => 'Strict' // Prevent CSRF leakage on cross-site requests
            ]);

            // Prevent session ID passing via URL
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_trans_sid', 0);

            session_start();
        }

        // Periodically regenerate session IDs to prevent hijacking/fixation
        if (!isset($_SESSION['session_created_at'])) {
            $_SESSION['session_created_at'] = time();
        } elseif (time() - $_SESSION['session_created_at'] > 900) { // 15 Minute window
            session_regenerate_id(true);
            $_SESSION['session_created_at'] = time();
        }
    }
}
