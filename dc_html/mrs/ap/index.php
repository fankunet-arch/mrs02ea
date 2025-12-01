<?php
/**
 * MRS 管理后台入口 (仅在 /mrs/ap 下暴露)
 */

define('MRS_ENTRY', true);

define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));
require_once PROJECT_ROOT . '/app/mrs/bootstrap_mrs.php';

$action = $_GET['action'] ?? null;
if ($action === null) {
    $action = mrs_is_user_logged_in() ? 'dashboard' : 'login';
}
$action = basename($action);

$page_actions = [
    'dashboard' => MRS_ACTION_PATH . '/dashboard.php',
    'login' => MRS_ACTION_PATH . '/login.php',
    'logout' => MRS_ACTION_PATH . '/logout.php',
];

$api_actions = [
    'do_login' => MRS_API_PATH . '/do_login.php',
];

if (isset($page_actions[$action])) {
    require_once $page_actions[$action];
    exit;
}

if (isset($api_actions[$action])) {
    require_once $api_actions[$action];
    exit;
}

http_response_code(404);
echo 'Action not found';
