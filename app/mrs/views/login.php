<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 管理登录</title>
    <link rel="stylesheet" href="/mrs/ap/css/login.css">
</head>
<body class="login-page">
    <div class="login-panel">
        <div class="login-card">
            <h1>MRS 系统</h1>
            <p class="sub">物料收发管理后台</p>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">登录失败：<?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form action="/mrs/ap/index.php?action=do_login" method="post">
                <label for="username">账号</label>
                <input type="text" id="username" name="username" required autocomplete="username">

                <label for="password">密码</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <button type="submit">登 录</button>
            </form>
            <p class="tip">凭证与 Express 共用，仅复用账户数据，不调用其代码。</p>
        </div>
    </div>
</body>
</html>
