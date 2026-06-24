<?php
// config/database.php

namespace CyberKavach\Nexus\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;

    /**
     * Retrieve a singleton PDO instance configured for secure queries.
     *
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            // Retrieve configuration with local fallback values for standard XAMPP setups
            $host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? '127.0.0.1';
$port     = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306';
$dbName   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'defaultdb';
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
            $charset  = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Enforce native prepared statements to thrawt injection vectors
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Graceful fallback for API routes vs direct views
                if (php_sapi_name() !== 'cli' && !headers_sent()) {
                    header('Content-Type: application/json; charset=UTF-8');
                    http_response_code(500);
                }
                echo json_encode([
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit;
            }
        }

        return self::$connection;
    }
}
