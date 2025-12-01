<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 登录</title>
    <link rel="stylesheet" href="/mrs/ap/css/login.css">
</head>
<body class="login-page">
<div class="login-card">
    <h1>MRS 台账登录</h1>
    <p class="subtitle">与 Express 共用账号体系</p>
    <?php if ($error): ?>
        <div class="alert danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="/mrs/ap/index.php?action=do_login">
        <label>用户名</label>
        <input type="text" name="username" required placeholder="输入账号">
        <label>密码</label>
        <input type="password" name="password" required placeholder="输入密码">
        <button type="submit">登录</button>
    </form>
    <div class="footnote">MRS 与 Express 数据隔离，仅复用用户登录表。</div>
</div>
</body>
</html>
