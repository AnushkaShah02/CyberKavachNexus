<?php
// src/Middleware/CSRFMiddleware.php

namespace CyberKavach\Nexus\Middleware;

use CyberKavach\Nexus\Helpers\ResponseHelper;
use CyberKavach\Nexus\Helpers\SecurityHelper;

class CSRFMiddleware {
    /**
     * Inspects inbound requests. Blocks execution on token mismatches during state changes.
     */
    public static function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Read and evaluate token only for data-mutating methods
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $token = null;

            // 1. Attempt reading token from header (Standard for AJAX calls)
            if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            // 2. Attempt reading from standard form POST payload
            elseif (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            }
            // 3. Fallback: Parse raw input stream JSON payload
            else {
                $input = json_decode(file_get_contents('php://input'), true);
                if (isset($input['csrf_token'])) {
                    $token = $input['csrf_token'];
                }
            }

            if (!SecurityHelper::verifyCsrfToken($token)) {
                ResponseHelper::sendJsonResponse(
                    false,
                    403,
                    'Security Error: Invalid or missing CSRF security token.'
                );
            }
        }
    }
}
