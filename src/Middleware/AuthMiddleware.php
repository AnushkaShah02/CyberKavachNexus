<?php
// src/Middleware/AuthMiddleware.php

namespace CyberKavach\Nexus\Middleware;

use CyberKavach\Nexus\Helpers\ResponseHelper;

class AuthMiddleware {
    /**
     * Handles validating session presence. Restricts guests from authenticated workflows.
     *
     * @return array Returns authenticated user profile if logged in.
     */
    public static function handle(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate user session array existence
        if (empty($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            self::abortUnauthorized();
        }

        return $_SESSION['user'];
    }

    /**
     * Sends appropriate response formats depending on API vs direct page requests.
     */
    private static function abortUnauthorized(): void {
        $isApi = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) 
                 || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isApi) {
            ResponseHelper::sendJsonResponse(
                false, 
                401, 
                'Authentication required. Please log in to proceed.'
            );
        } else {
            // Redirect direct browser requests to login portal
            $projectUrlPath = '';
            if (preg_match('/^(.*?)\/(public|views|src|config)/', $_SERVER['SCRIPT_NAME'] ?? '', $matches)) {
                $projectUrlPath = $matches[1];
            }
            header('Location: ' . $projectUrlPath . '/public/index.php/views/pages/login.php');
            exit;
        }
    }
}
