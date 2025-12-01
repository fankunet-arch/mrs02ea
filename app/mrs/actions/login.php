<?php
if (mrs_is_user_logged_in()) {
    header('Location: /mrs/index.php?action=home');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = mrs_authenticate_user($pdo, $username, $password);
    if ($user) {
        mrs_create_user_session($user);
        header('Location: /mrs/index.php?action=home');
        exit;
    } else {
        $error = '用户名或密码错误，或账户未激活。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 登录</title>
    <link rel="stylesheet" href="/mrs/css/login-v2.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>MRS 台账登录</h1>
        <p class="subtitle">共享 Express 用户库 · 独立运行</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <label>用户名</label>
            <input type="text" name="username" required placeholder="请输入用户名" />
            <label>密码</label>
            <input type="password" name="password" required placeholder="请输入密码" />
            <button type="submit">登录</button>
        </form>
        <p class="helper-text">使用与 Express 相同的账户信息登录。</p>
    </div>
</body>
</html>
