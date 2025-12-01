<?php
/**
 * MRS Package Ledger - 前端入口路由
 * 路径: dc_html/mrs/ap/index.php
 */

// 定义入口标识
define('MRS_ENTRY', true);

// 定义项目根目录（dc_html的上级目录）
define('PROJECT_ROOT', dirname(dirname(__DIR__)));

// 加载bootstrap（在app/mrs目录中）
require_once PROJECT_ROOT . '/app/mrs/bootstrap.php';

// 获取action参数
$action = $_GET['action'] ?? 'dashboard';
$action = basename($action);

// 允许访问的action
$allowed_actions = [
    'login',
    'dashboard',
    'inbound',
    'outbound',
    'inventory',
    'logout'
];

if (!in_array($action, $allowed_actions, true)) {
    http_response_code(404);
    die('Invalid action');
}

// 路由到对应文件
$action_file = MRS_ACTION_PATH . '/' . $action . '.php';
if (file_exists($action_file)) {
    require_once $action_file;
} else {
    http_response_code(404);
    die('Action not found');
}
