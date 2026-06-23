<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use CyberKavach\Nexus\Controllers\AuthController;
use CyberKavach\Nexus\Helpers\ResponseHelper;

$action = $_GET['action'] ?? '';
$controller = new AuthController();

if ($action === 'login') {
    $controller->login();
} elseif ($action === 'logout') {
    $controller->logout();
} else {
    ResponseHelper::sendJsonResponse(false, 400, 'Invalid authentication API action request.');
}
