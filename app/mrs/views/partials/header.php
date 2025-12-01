<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}
$display_name = $_SESSION['mrs_user_display_name'] ?? '访客';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 物料收发台账</title>
    <link rel="stylesheet" href="/mrs/ap/css/backend.css">
</head>
<body>
<header class="topbar">
    <div class="brand">MRS 台账</div>
    <nav class="nav-links">
        <a href="/mrs/ap/index.php?action=dashboard">总览</a>
        <a href="/mrs/ap/index.php?action=inbound">入库录入</a>
        <a href="/mrs/ap/index.php?action=outbound">出库核销</a>
        <a href="/mrs/ap/index.php?action=inventory">库存清单</a>
        <a href="/mrs/ap/index.php?action=reports">统计报表</a>
    </nav>
    <div class="userbox">
        <span class="user">👤 <?php echo htmlspecialchars($display_name); ?></span>
        <a class="logout" href="/mrs/ap/index.php?action=logout">退出</a>
    </div>
</header>
<main class="page">
<?php if (!empty($_GET['msg'])): ?>
    <div class="alert success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>
<div class="card">
