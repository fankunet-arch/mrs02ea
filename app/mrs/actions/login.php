<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = mrs_authenticate_user($pdo, $username, $password);

    if ($user) {
        mrs_create_user_session($user);
        header('Location: /mrs/ap/index.php?action=dashboard');
        exit;
    }

    $error = '用户名或密码错误，或账户未激活';
}

require MRS_VIEW_PATH . '/login_view.php';
