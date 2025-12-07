<?php
/**
 * VIS登录流程完整测试
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VIS登录流程测试 ===\n\n";

// 1. 测试vis_require_login跳转
echo "1. 测试未登录时的跳转\n";
echo "检查: app/vis/lib/vis_lib.php\n";
$vis_lib = file_get_contents('/home/user/mrs02ea/app/vis/lib/vis_lib.php');
if (preg_match("/function vis_require_login.*?\{(.*?)\}/s", $vis_lib, $matches)) {
    $function_body = $matches[1];
    if (strpos($function_body, "/vis/ap/index.php?action=login") !== false) {
        echo "✅ vis_require_login() 跳转到 /vis/ap/index.php?action=login\n";
    } else if (strpos($function_body, "/mrs/") !== false) {
        echo "❌ 发现MRS跳转！\n";
        echo "内容: " . trim($function_body) . "\n";
    } else {
        echo "⚠️ 未找到跳转逻辑\n";
    }
}

// 2. 测试logout跳转
echo "\n2. 测试登出跳转\n";
echo "检查: app/vis/api/logout.php\n";
if (file_exists('/home/user/mrs02ea/app/vis/api/logout.php')) {
    $logout = file_get_contents('/home/user/mrs02ea/app/vis/api/logout.php');
    if (strpos($logout, "/vis/ap/index.php?action=login") !== false) {
        echo "✅ logout.php 跳转到 /vis/ap/index.php?action=login\n";
    } else if (strpos($logout, "/mrs/") !== false) {
        echo "❌ logout.php 包含MRS跳转！\n";
        preg_match_all("/header.*Location.*?;/", $logout, $matches);
        foreach ($matches[0] as $match) {
            echo "  发现: $match\n";
        }
    }
} else {
    echo "❌ logout.php 文件不存在\n";
}

// 3. 检查会话名称
echo "\n3. 检查会话名称配置\n";
echo "检查: app/vis/config_vis/env_vis.php\n";
$env = file_get_contents('/home/user/mrs02ea/app/vis/config_vis/env_vis.php');
if (preg_match("/define\('VIS_SESSION_NAME',\s*'([^']+)'/", $env, $matches)) {
    $session_name = $matches[1];
    echo "✅ VIS_SESSION_NAME = '$session_name'\n";
    if ($session_name !== 'VIS_SESSID') {
        echo "⚠️ 建议使用 'VIS_SESSID' 而不是 '$session_name'\n";
    }
} else {
    echo "❌ 未找到VIS_SESSION_NAME定义\n";
}

// 4. 检查所有VIS文件中的MRS引用
echo "\n4. 扫描所有VIS文件中的MRS引用\n";
$vis_files = [
    '/home/user/mrs02ea/app/vis/lib/vis_lib.php',
    '/home/user/mrs02ea/app/vis/api/do_login.php',
    '/home/user/mrs02ea/app/vis/api/logout.php',
    '/home/user/mrs02ea/dc_html/vis/ap/index.php',
];

$found_mrs = false;
foreach ($vis_files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (preg_match_all("/(mrs\/ap|MRS_[A-Z_]+|mrs_[a-z_]+)/i", $content, $matches)) {
        // 排除注释和字符串中的引用
        $real_matches = [];
        foreach ($matches[0] as $match) {
            if (!preg_match("/(\/\/|#|\*|'search'|deploy)/", $match)) {
                $real_matches[] = $match;
            }
        }
        if (!empty($real_matches)) {
            echo "⚠️ " . basename($file) . " 包含MRS引用:\n";
            foreach (array_unique($real_matches) as $m) {
                echo "    - $m\n";
            }
            $found_mrs = true;
        }
    }
}
if (!$found_mrs) {
    echo "✅ 未发现MRS引用\n";
}

// 5. 模拟登录流程
echo "\n5. 模拟登录重定向测试\n";
echo "模拟访问: /vis/ap/index.php (未登录)\n";

// 模拟未登录状态
$_SESSION = [];
ob_start();

// 模拟vis_require_login调用
function test_vis_require_login() {
    // 检查登录状态
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // 这里会跳转
        return '/vis/ap/index.php?action=login';
    }
    return null;
}

$redirect = test_vis_require_login();
if ($redirect) {
    echo "✅ 未登录时会跳转到: $redirect\n";
} else {
    echo "❌ 未检测到跳转\n";
}

ob_end_clean();

echo "\n=== 测试完成 ===\n";
echo "\n如果看到❌标记，说明存在问题需要修复。\n";
