<?php
/**
 * MRS Package Ledger - Admin Router
 * Path: dc_html/mrs/ap/index.php
 */

define('MRS_ENTRY', true);

define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

require_once PROJECT_ROOT . '/app/mrs/bootstrap.php';

$action = $_GET['action'] ?? 'home';
$action = basename($action);

$allowed_actions = [
    'home',
    'login',
    'logout',
    'inbound',
    'outbound',
    'inventory',
    'reports'
];

if (!in_array($action, $allowed_actions)) {
    http_response_code(404);
    die('Invalid action');
}

$action_file = MRS_ACTION_PATH . '/' . $action . '.php';

if (file_exists($action_file)) {
    require_once $action_file;
} else {
    http_response_code(404);
    die('Action not found');
}
