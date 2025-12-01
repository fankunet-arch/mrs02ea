<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>MRS 登录</title>
    <link rel="stylesheet" href="/mrs/ap/css/login-v2.css">
</head>
<body class="login-page">
<div class="login-container">
    <h1 class="logo">MRS 系统</h1>
    <p class="subtitle">物料收发管理 | 独立于 Express 运行</p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" action="/mrs/ap/index.php?action=login" class="login-form">
        <label for="username">用户名</label>
        <input type="text" id="username" name="username" placeholder="请输入用户账号" required autofocus>

        <label for="password">密码</label>
        <input type="password" id="password" name="password" placeholder="请输入密码" required>

        <button type="submit">登录</button>
    </form>
    <p class="footnote">用户账户与 Express 共用，但系统代码完全独立</p>
</div>
</body>
</html>
