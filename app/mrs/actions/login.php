<?php
mrs_start_secure_session();

if (mrs_is_user_logged_in()) {
    header('Location: /mrs/ap/index.php?action=dashboard');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        $user = mrs_authenticate_user($pdo, $username, $password);
        if ($user) {
            mrs_create_user_session($user);
            header('Location: /mrs/ap/index.php?action=dashboard');
            exit;
        }
        $error = '登录失败：用户名或密码错误，或账户未激活。';
    }
}

mrs_render('login', [
    'error' => $error,
]);
