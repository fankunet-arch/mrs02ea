<?php
/**
 * MRS Package Ledger - Front Router
 * 文件路径: dc_html/mrs/ap/index.php
 * 说明: 所有可访问页面/接口入口，独立于 Express
 */

define('MRS_ENTRY', true);

define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

require_once PROJECT_ROOT . '/app/mrs/bootstrap.php';

$action = $_GET['action'] ?? 'login';
$action = basename($action);

$allowed_actions = [
    'login',
    'logout',
    'dashboard',
    'save_inbound_api',
    'list_packages_api',
    'update_status_api',
    'stats_api',
];

if (!in_array($action, $allowed_actions, true)) {
    http_response_code(404);
    die('Invalid action');
}

$action_file = MRS_ACTION_PATH . '/' . $action . '.php';
if (file_exists($action_file)) {
    require_once $action_file;
    exit;
}

http_response_code(404);
die('Action not found');
