<?php
// public/index.php

// 1. Establish Autoloaders (Composer with Custom Fallback)
$basePath = dirname(__DIR__);
if (file_exists($basePath . '/vendor/autoload.php')) {
    require_once $basePath . '/vendor/autoload.php';
} else {
    // Fallback PSR-4 Autoloader to keep system active before composer is run
    spl_autoload_register(function ($class) {
        $prefix = 'CyberKavach\\Nexus\\';
        $baseDir = dirname(__DIR__) . '/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        
        // Split components to support files directly under config/ folder
        if (strpos($relativeClass, 'Config\\') === 0) {
            $file = $baseDir . 'config/' . substr($relativeClass, 7) . '.php';
        } else {
            $file = $baseDir . 'src/' . str_replace('\\', '/', $relativeClass) . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Load Environmental Variables if vlucas/dotenv is installed
if (class_exists('Dotenv\Dotenv') && file_exists($basePath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

// 2. Initialize Security Headers and Sessions
use CyberKavach\Nexus\Config\Security;
use CyberKavach\Nexus\Helpers\SecurityHelper;
use CyberKavach\Nexus\Middleware\CSRFMiddleware;

Security::setHeaders();
Security::startSecureSession();

// Generate Anti-CSRF token for headers/view injections
$csrfToken = SecurityHelper::generateCsrfToken();

// 3. Inspect and filter State mutating requests against CSRF
CSRFMiddleware::handle();

// 4. Basic URI Dispatch Routing (Decoupled Front-Controller logic)
$projectUrlPath = '';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = '/' . trim($requestUri, '/');

$requestUri = '/' . trim($requestUri, '/');

// Authenticated checks to route between dashboards and login
$isLoggedIn = !empty($_SESSION['user']) && isset($_SESSION['user']['id']);

switch ($requestUri) {
    case '':
    case '/':
    case '/index.php':
        if ($isLoggedIn) {
            header('Location: ' . $projectUrlPath . '/views/pages/dashboard.php');
        } else {
            header('Location: ' . $projectUrlPath . '/views/pages/login.php');
        }
        exit;

    case '/api/auth.php':
        require_once $basePath . '/public/api/auth.php';
        exit;

    case '/verify':
    case '/verify.php':
        require_once $basePath . '/views/pages/verify.php';
        exit;

case '/dashboard':
case '/dashboard.php':
case '/views/pages/dashboard.php':
    require_once $basePath . '/views/pages/dashboard.php';
    exit;

case '/events':
case '/events.php':
case '/views/pages/events.php':
    require_once $basePath . '/views/pages/events.php';
    exit;

case '/meetings':
case '/meetings.php':
case '/views/pages/meetings.php':
    require_once $basePath . '/views/pages/meetings.php';
    exit;

case '/workspace':
case '/workspace.php':
case '/views/pages/workspace.php':
    require_once $basePath . '/views/pages/workspace.php';
    exit;

case '/workspace_details':
case '/workspace_details.php':
case '/views/pages/workspace_details.php':
    require_once $basePath . '/views/pages/workspace_details.php';
    exit;


case '/view_team':
case '/view_team.php':
case '/views/pages/view_team.php':
    require_once $basePath . '/views/pages/view_team.php';
    exit;

case '/event_attendance':
case '/event_attendance.php':
case '/views/pages/event_attendance.php':
    require_once $basePath . '/views/pages/event_attendance.php';
    exit;

case '/attendance_details':
case '/attendance_details.php':
case '/views/pages/attendance_details.php':
    require_once $basePath . '/views/pages/attendance_details.php';
    exit;

    default:
        // Direct browser requests targeting core view pages (fallback routing)
        if (preg_match('/^\/views\/pages\/(.+)\.php$/', $requestUri, $matches)) {
            $targetView = $basePath . $requestUri;
            if (file_exists($targetView)) {
                require_once $targetView;
                exit;
            }
        }
        
        // Resource not found handler
        http_response_code(404);
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>404 - Not Found</title>
            <style>
                body { background: #0c0f17; color: #f3f4f6; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .card { text-align: center; background: #181d28; padding: 40px; border-radius: 12px; border: 1px solid #2d3748; }
                h1 { color: #4299e1; margin-bottom: 10px; }
                a { color: #f6ad55; text-decoration: none; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h1>404 - Resource Not Found</h1>
                <p>The page or action you requested does not exist.</p>
                <p><a href='/'>Go Home</a></p>
            </div>
        </body>
        </html>";
        exit;
}
