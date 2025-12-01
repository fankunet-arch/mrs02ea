<?php
/**
 * MRS System - 配置文件
 * 路径: app/mrs/config_mrs/env_mrs.php
 * 说明: 定义数据库连接、路径常量以及基础配置。
 */

// 防止直接访问
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

// ============================================
// 数据库配置（与 Express 共享用户库）
// ============================================

// 支持环境变量，优先使用 MRS_*，其次使用 EXPRESS_*，最后使用默认值
if (!defined('MRS_DB_HOST')) {
    define('MRS_DB_HOST', getenv('MRS_DB_HOST') ?: (getenv('EXPRESS_DB_HOST') ?: 'mhdlmskp2kpxguj.mysql.db'));
}
if (!defined('MRS_DB_NAME')) {
    define('MRS_DB_NAME', getenv('MRS_DB_NAME') ?: (getenv('EXPRESS_DB_NAME') ?: 'mhdlmskp2kpxguj'));
}
if (!defined('MRS_DB_USER')) {
    define('MRS_DB_USER', getenv('MRS_DB_USER') ?: (getenv('EXPRESS_DB_USER') ?: 'mhdlmskp2kpxguj'));
}
if (!defined('MRS_DB_PASS')) {
    define('MRS_DB_PASS', getenv('MRS_DB_PASS') ?: (getenv('EXPRESS_DB_PASS') ?: 'BWNrmksqMEqgbX37r3QNDJLGRrUka'));
}
if (!defined('MRS_DB_CHARSET')) {
    define('MRS_DB_CHARSET', getenv('MRS_DB_CHARSET') ?: 'utf8mb4');
}

// ============================================
// 路径常量
// ============================================

if (!defined('MRS_APP_PATH')) {
    define('MRS_APP_PATH', dirname(dirname(__FILE__)));
}
if (!defined('MRS_CONFIG_PATH')) {
    define('MRS_CONFIG_PATH', MRS_APP_PATH . '/config_mrs');
}
if (!defined('MRS_LIB_PATH')) {
    define('MRS_LIB_PATH', MRS_APP_PATH . '/lib');
}
if (!defined('MRS_ACTION_PATH')) {
    define('MRS_ACTION_PATH', MRS_APP_PATH . '/actions');
}
if (!defined('MRS_VIEW_PATH')) {
    define('MRS_VIEW_PATH', MRS_APP_PATH . '/views');
}
if (!defined('MRS_LOG_PATH')) {
    define('MRS_LOG_PATH', dirname(dirname(MRS_APP_PATH)) . '/logs/mrs');
}
if (!defined('MRS_WEB_ROOT')) {
    define('MRS_WEB_ROOT', dirname(dirname(dirname(MRS_APP_PATH))) . '/dc_html/mrs/ap');
}

// ============================================
// 系统配置
// ============================================

date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!is_dir(MRS_LOG_PATH)) {
    mkdir(MRS_LOG_PATH, 0755, true);
}
ini_set('error_log', MRS_LOG_PATH . '/error.log');

// 数据库连接配置
$db_config = [
    'host' => MRS_DB_HOST,
    'dbname' => MRS_DB_NAME,
    'user' => MRS_DB_USER,
    'pass' => MRS_DB_PASS,
    'charset' => MRS_DB_CHARSET,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

/**
 * 获取数据库连接
 * @return PDO
 * @throws PDOException
 */
function get_mrs_db_connection() {
    global $db_config;

    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db_config['host'], $db_config['dbname'], $db_config['charset']);
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $db_config['options']);
    }

    return $pdo;
}

/**
 * 简单日志记录
 * @param string $message
 * @param string $level
 * @param array $context
 */
function mrs_log($message, $level = 'INFO', $context = []) {
    $log_file = MRS_LOG_PATH . '/debug.log';
    if (!is_dir(MRS_LOG_PATH)) {
        mkdir(MRS_LOG_PATH, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $context_str = $context ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    file_put_contents($log_file, sprintf("[%s] [%s] %s%s\n", $timestamp, $level, $message, $context_str), FILE_APPEND);
}

/**
 * JSON响应
 */
function mrs_json_response($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
