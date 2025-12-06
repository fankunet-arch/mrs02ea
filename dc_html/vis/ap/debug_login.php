<?php
/**
 * VIS 登录调试文件 - 用于诊断登录跳转问题
 */
define('VIS_ENTRY', true);
define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>VIS Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f0f0f0;}";
echo ".debug{background:#fff;padding:15px;margin:10px 0;border-left:4px solid #007bff;}";
echo ".error{border-left-color:#dc3545;background:#fff5f5;}";
echo ".success{border-left-color:#28a745;background:#f5fff5;}";
echo "h2{margin:0 0 10px 0;color:#333;}</style></head><body>";

echo "<h1>VIS 登录系统调试</h1>";

// 1. 检查文件路径
echo "<div class='debug'><h2>1. 文件路径检查</h2>";
echo "PROJECT_ROOT: " . PROJECT_ROOT . "<br>";
echo "Bootstrap: " . PROJECT_ROOT . '/app/vis/bootstrap.php<br>';
echo "Bootstrap exists: " . (file_exists(PROJECT_ROOT . '/app/vis/bootstrap.php') ? '✅ YES' : '❌ NO') . "</div>";

// 2. 加载bootstrap
try {
    require_once PROJECT_ROOT . '/app/vis/bootstrap.php';
    echo "<div class='debug success'><h2>2. Bootstrap加载</h2>✅ 成功加载</div>";
} catch (Exception $e) {
    echo "<div class='debug error'><h2>2. Bootstrap加载</h2>❌ 失败: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// 3. 检查关键常量
echo "<div class='debug'><h2>3. 关键常量</h2>";
echo "VIS_VIEW_PATH: " . (defined('VIS_VIEW_PATH') ? VIS_VIEW_PATH : '❌ 未定义') . "<br>";
echo "VIS_API_PATH: " . (defined('VIS_API_PATH') ? VIS_API_PATH : '❌ 未定义') . "<br>";
echo "VIS_SESSION_NAME: " . (defined('VIS_SESSION_NAME') ? VIS_SESSION_NAME : '❌ 未定义') . "</div>";

// 4. 检查登录页面文件
echo "<div class='debug'><h2>4. 登录页面文件</h2>";
$login_view = VIS_VIEW_PATH . '/login.php';
echo "Login view path: " . $login_view . "<br>";
echo "File exists: " . (file_exists($login_view) ? '✅ YES' : '❌ NO') . "<br>";
if (file_exists($login_view)) {
    echo "File size: " . filesize($login_view) . " bytes<br>";
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($login_view));
}
echo "</div>";

// 5. 检查会话
echo "<div class='debug'><h2>5. 会话信息</h2>";
echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '⚠️ Not active') . "<br>";
echo "Session name: " . session_name() . "<br>";
echo "Is logged in: " . (function_exists('vis_is_user_logged_in') && vis_is_user_logged_in() ? '✅ YES' : '❌ NO') . "</div>";

// 6. 检查 vis_require_login 函数
echo "<div class='debug'><h2>6. vis_require_login 函数</h2>";
if (function_exists('vis_require_login')) {
    echo "✅ 函数已定义<br>";
    // 读取函数源代码
    $ref = new ReflectionFunction('vis_require_login');
    $filename = $ref->getFileName();
    $start_line = $ref->getStartLine() - 1;
    $end_line = $ref->getEndLine();
    $length = $end_line - $start_line;
    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));
    echo "<pre style='background:#f8f8f8;padding:10px;overflow:auto;'>" . htmlspecialchars($body) . "</pre>";
} else {
    echo "❌ 函数未定义";
}
echo "</div>";

// 7. 模拟路由逻辑
echo "<div class='debug'><h2>7. 路由模拟（action=login）</h2>";
$test_action = 'login';
$public_actions = ['login', 'do_login'];
$allowed_actions = ['login', 'do_login', 'logout', 'admin_list', 'admin_upload', 'video_upload', 'video_save', 'video_delete'];

echo "Test action: <strong>{$test_action}</strong><br>";
echo "Is in public_actions: " . (in_array($test_action, $public_actions) ? '✅ YES' : '❌ NO') . "<br>";
echo "Should call vis_require_login: " . (!in_array($test_action, $public_actions) ? '❌ YES (会跳转)' : '✅ NO (不会跳转)') . "<br>";
echo "Is in allowed_actions: " . (in_array($test_action, $allowed_actions) ? '✅ YES' : '❌ NO') . "</div>";

// 8. 实际访问测试
echo "<div class='debug'><h2>8. 实际访问测试</h2>";
echo "<a href='/vis/ap/index.php?action=login' style='display:inline-block;padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;'>点击测试: /vis/ap/index.php?action=login</a>";
echo "</div>";

echo "</body></html>";
