<?php
/**
 * VIS Video Inspiration System - Frontend Router
 * 文件路径: dc_html/vis/ap/index.php
 * 说明: 中央路由入口 (网络可访问)
 */

// 定义系统入口标识
define('VIS_ENTRY', true);

// 定义项目根目录 (dc_html的上级目录)
define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

// 加载bootstrap (在app目录中)
require_once PROJECT_ROOT . '/app/vis/bootstrap.php';

// 获取action参数
$action = $_GET['action'] ?? 'gallery';
$action = basename($action); // 防止路径遍历

// 身份验证: 后台管理操作必须经过会话校验
$backend_actions = [
    'admin_list',           // 后台视频列表
    'admin_upload',         // 后台上传页面
    'video_upload',         // 处理上传
    'video_save',           // 保存编辑
    'video_delete',         // 删除视频
    'admin_categories',     // 分类管理
    'category_save',        // 保存分类
];

// 前台公开访问的action不需要登录
$public_actions = [
    'gallery',              // 前台视频展示
    'play_sign',            // 获取播放签名URL
];

// 如果是后台操作，检查登录
if (in_array($action, $backend_actions)) {
    vis_require_login();
}

// 允许的所有action列表
$allowed_actions = array_merge($backend_actions, $public_actions);

// 验证action是否允许
if (!in_array($action, $allowed_actions)) {
    $accepts_json = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($accepts_json || $is_ajax) {
        vis_json_response(false, null, 'Invalid action');
    }

    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 - Page Not Found</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:40px;}';
    echo '.card{max-width:520px;margin:0 auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}';
    echo '.card h1{margin-top:0;font-size:22px;color:#c62828;} .card p{color:#444;line-height:1.6;} .card a{color:#1565c0;text-decoration:none;font-weight:600;}</style>';
    echo '</head><body><div class="card"><h1>404 - 页面不存在</h1><p>请求的页面未找到。</p>';
    echo '<p><a href="/vis/ap/index.php?action=gallery">返回首页</a></p></div></body></html>';
    exit;
}

// API action (返回JSON)
$api_actions = [
    'video_upload',
    'video_save',
    'video_delete',
    'play_sign',
    'category_save',
];

// 路由到对应的action或API文件 (在app目录中)
if (in_array($action, $api_actions)) {
    // API路由
    $api_file = VIS_API_PATH . '/' . $action . '.php';
    if (file_exists($api_file)) {
        require_once $api_file;
    } else {
        vis_json_response(false, null, 'API not found');
    }
} else {
    // 页面路由
    $view_file = VIS_VIEW_PATH . '/' . $action . '.php';
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        http_response_code(404);
        die('Page not found');
    }
}
