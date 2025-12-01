<?php
// Login page
$error = null;
if (isset($_GET['error'])) {
    $map = [
        'invalid' => '账号或密码错误',
        'too_many_attempts' => '尝试次数过多，请稍后再试',
    ];
    $error = $map[$_GET['error']] ?? '请重新登录';
}
require_once MRS_VIEW_PATH . '/login.php';
