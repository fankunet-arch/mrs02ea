<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: /mrs/ap/index.php?action=login&error=请输入用户名和密码');
    exit;
}

$user = mrs_authenticate_user($pdo, $username, $password);

if ($user) {
    mrs_create_user_session($user);
    header('Location: /mrs/ap/index.php?action=dashboard');
    exit;
}

header('Location: /mrs/ap/index.php?action=login&error=账号或密码错误');
exit;
