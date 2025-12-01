<?php mrs_start_secure_session(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 系统</title>
    <link rel="stylesheet" href="/mrs/ap/css/backend.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">MRS 物料收发系统</div>
        <?php if (mrs_is_user_logged_in()): ?>
        <div class="topbar-actions">
            <span class="user">👤 <?php echo htmlspecialchars(mrs_current_user_display(), ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn" href="/mrs/ap/index.php?action=logout">退出</a>
        </div>
        <?php endif; ?>
    </header>
    <?php if (mrs_is_user_logged_in()): ?>
    <nav class="sidebar">
        <a href="/mrs/ap/index.php?action=dashboard" class="nav-item">总览</a>
        <a href="/mrs/ap/index.php?action=inbound" class="nav-item">入库登记</a>
        <a href="/mrs/ap/index.php?action=outbound" class="nav-item">出库核销</a>
        <a href="/mrs/ap/index.php?action=inventory" class="nav-item">库存快照</a>
    </nav>
    <main class="content">
    <?php else: ?>
    <main class="content single">
    <?php endif; ?>
