<?php
/**
 * MRS Package Ledger System - 管理端路由
 */

define('MRS_ENTRY', true);

define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

if (file_exists(PROJECT_ROOT . '/app/mrs/bootstrap_mock.php')) {
    require_once PROJECT_ROOT . '/app/mrs/bootstrap_mock.php';
} else {
    require_once PROJECT_ROOT . '/app/mrs/bootstrap.php';
}

$action = $_GET['action'] ?? 'dashboard';
$action = basename($action);

if ($action !== 'login' && $action !== 'do_login') {
    mrs_require_login();
}

$allowed_actions = [
    'login',
    'do_login',
    'logout',
    'dashboard',
    'inbound',
    'inbound_save',
    'outbound',
    'outbound_save',
    'inventory',
    'reports',
    'update_status',
];

if (!in_array($action, $allowed_actions, true)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body><h1>404 - Invalid action</h1></body></html>';
    exit;
}

$api_actions = [
    'do_login',
    'logout',
    'inbound_save',
    'outbound_save',
    'update_status'
];

if (in_array($action, $api_actions, true)) {
    $api_file = MRS_API_PATH . '/' . $action . '.php';
    if (file_exists($api_file)) {
        require_once $api_file;
    } else {
        mrs_json_response(false, null, 'API not found');
    }
    exit;
}

$view_file = MRS_VIEW_PATH . '/' . $action . '.php';
if (file_exists($view_file)) {
    require_once $view_file;
    exit;
}

http_response_code(404);
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body><h1>404 - Page Not Found</h1></body></html>';
