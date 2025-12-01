<?php include __DIR__ . '/shared/header.php'; ?>
<section class="card narrow">
    <h1>登录 MRS</h1>
    <p class="muted">使用与 Express 相同的账号密码登录，进入库存账本。</p>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <label>用户名
            <input type="text" name="username" placeholder="工号或账号" required>
        </label>
        <label>密码
            <input type="password" name="password" placeholder="请输入密码" required>
        </label>
        <button type="submit" class="btn primary">登录</button>
    </form>
</section>
<?php include __DIR__ . '/shared/footer.php'; ?>
