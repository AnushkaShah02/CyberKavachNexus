<?php
// src/Helpers/response_helper.php

namespace CyberKavach\Nexus\Helpers;

class ResponseHelper {
    /**
     * Standardizes API JSON output formatting and exits script execution.
     *
     * @param bool   $success
     * @param int    $statusCode
     * @param string $message
     * @param array  $data
     */
    public static function sendJsonResponse(bool $success, int $statusCode, string $message, array $data = []): void {
        // Clear buffer and apply JSON headers
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code($statusCode);
        }

        echo json_encode([
            'success'     => $success,
            'status_code' => $statusCode,
            'message'     => $message,
            'data'        => $data
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        exit;
    }
}
