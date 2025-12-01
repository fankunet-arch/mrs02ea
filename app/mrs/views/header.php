<?php
if (!isset($page_title)) {
    $page_title = 'MRS 物料收发管理系统';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/mrs/css/backend.css">
    <link rel="stylesheet" href="/mrs/css/quick_ops.css">
</head>
<body>
<header class="topbar">
    <div class="brand">MRS 台账</div>
    <?php if (mrs_is_user_logged_in()): ?>
    <nav class="nav">
        <a href="/mrs/index.php?action=home">仪表盘</a>
        <a href="/mrs/index.php?action=inbound">入库录入</a>
        <a href="/mrs/index.php?action=outbound">出库核销</a>
        <a href="/mrs/index.php?action=inventory">库存快照</a>
        <a href="/mrs/index.php?action=reports">统计报表</a>
        <a href="/mrs/index.php?action=logout" class="danger">退出</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
