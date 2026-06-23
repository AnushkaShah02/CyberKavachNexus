<?php
// src/Middleware/RBACMiddleware.php

namespace CyberKavach\Nexus\Middleware;

use CyberKavach\Nexus\Helpers\ResponseHelper;

class RBACMiddleware {
    /**
     * Asserts the logged-in user possesses one of the authorized role levels.
     *
     * @param array $allowedRoleIds List of allowed role ID constants.
     */
    public static function assertHasRoles(array $allowedRoleIds): void {
        // Enforce session check and pull user profile
        $user = AuthMiddleware::handle();

        $roleId = (int)($user['role_id'] ?? 0);

        if (!in_array($roleId, $allowedRoleIds, true)) {
            self::abortForbidden();
        }
    }

    /**
     * Block execution with a 403 response.
     */
    private static function abortForbidden(): void {
        $isApi = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) 
                 || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isApi) {
            ResponseHelper::sendJsonResponse(
                false,
                403,
                'Authorization Error: You do not possess access permissions for this action.'
            );
        } else {
            // Render basic HTML forbidden panel
            http_response_code(403);
            echo "<!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <title>Access Forbidden</title>
                <style>
                    body { background: #0c0f17; color: #f3f4f6; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                    .card { text-align: center; background: #181d28; padding: 40px; border-radius: 12px; border: 1px solid #2d3748; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                    h1 { color: #f56565; margin-bottom: 10px; }
                    a { color: #4299e1; text-decoration: none; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <h1>403 - Forbidden</h1>
                    <p>You do not have permission to view this resource.</p>
                    <p><a href='/'>Return to Safety</a></p>
                </div>
            </body>
            </html>";
            exit;
        }
    }
}
