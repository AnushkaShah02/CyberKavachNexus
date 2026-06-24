<?php
// src/Helpers/security_helper.php

namespace CyberKavach\Nexus\Helpers;

class SecurityHelper {
    /**
     * Escape strings to prevent Cross-Site Scripting (XSS) injections on pages.
     *
     * @param string|null $value
     * @return string
     */
    public static function escape(?string $value): string {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generate a cryptographically secure CSRF token and save it to the session.
     *
     * @return string
     */
    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify if the provided token matches the active session token.
     *
     * @param string|null $token
     * @return bool
     */
    public static function verifyCsrfToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Resolve asset URLs dynamically, handling root and subdirectory installs.
     *
     * @param string $path Path relative to public/
     * @return string
     */
    public static function asset(string $path): string
{
    $path = ltrim($path, '/');

    // Static assets
    if (
        str_starts_with($path, 'assets/') ||
        str_starts_with($path, 'uploads/')
    ) {
        return '/' . $path;
    }

    // Pages and APIs
    return '/index.php/' . $path;
}
}
