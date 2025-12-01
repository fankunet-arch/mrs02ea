<?php
/** @var array $inventory */
/** @var array $summary */
/** @var array $flow */
/** @var array $recent */
/** @var array $messages */
/** @var string $month */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRS 包裹台账中心</title>
    <link rel="stylesheet" href="/mrs/ap/css/admin.css">
</head>
<body>
<header class="topbar">
    <div class="brand">MRS 台账</div>
    <div class="user">
        <span>当前用户：<?= htmlspecialchars($_SESSION['user_display_name'] ?? $_SESSION['user_login'] ?? '未知') ?></span>
        <a href="/mrs/ap/index.php?action=logout" class="logout">退出</a>
    </div>
</header>
<main class="container">
    <section class="panel">
        <div class="panel-header">
            <h2>入库录入</h2>
            <p>输入批次与箱号范围，系统自动生成多条包裹记录。</p>
        </div>
        <div class="panel-body">
            <form method="post" action="/mrs/ap/index.php?action=dashboard" class="form-grid">
                <input type="hidden" name="form_type" value="inbound">
                <label>物料名称
                    <input type="text" name="sku_name" required placeholder="如：香蕉">
                </label>
                <label>批次号
                    <input type="text" name="batch_code" required placeholder="如：A01">
                </label>
                <label>箱号范围
                    <input type="text" name="box_range" required placeholder="1-5 或 8">
                </label>
                <label>单箱规格
                    <input type="text" name="spec_info" placeholder="选填，如20斤">
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn primary">批量入库</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>出库 / 作废</h2>
            <p>勾选在库包裹，按需执行出库或标记损耗。</p>
        </div>
        <div class="panel-body">
            <form method="post" action="/mrs/ap/index.php?action=dashboard">
                <input type="hidden" name="form_type" id="bulk_action" value="outbound">
                <div class="table-actions">
                    <button type="submit" onclick="document.getElementById('bulk_action').value='outbound';" class="btn success">确认出库</button>
                    <button type="submit" onclick="document.getElementById('bulk_action').value='void';" class="btn danger">标记损耗</button>
                    <input type="text" name="void_reason" placeholder="损耗原因（可选）">
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                            <th>物料</th>
                            <th>批次</th>
                            <th>箱号</th>
                            <th>规格</th>
                            <th>状态</th>
                            <th>入库时间</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr><td colspan="7" class="empty">暂无在库包裹</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="package_ids[]" value="<?= (int)$row['package_id'] ?>"></td>
                                <td><?= htmlspecialchars($row['sku_name']) ?></td>
                                <td><?= htmlspecialchars($row['batch_code']) ?></td>
                                <td><?= htmlspecialchars($row['box_number']) ?></td>
                                <td><?= htmlspecialchars($row['spec_info']) ?></td>
                                <td><span class="status status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['inbound_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </section>

    <section class="panel grid">
        <div class="panel-item">
            <h3>月度流量 (<?= htmlspecialchars($month) ?>)</h3>
            <p>入库包裹：<strong><?= (int)($flow['inbound']['inbound_count'] ?? 0) ?></strong>，物料种类：<strong><?= (int)($flow['inbound']['inbound_skus'] ?? 0) ?></strong></p>
            <p>已出库包裹：<strong><?= (int)($flow['outbound']['outbound_count'] ?? 0) ?></strong></p>
        </div>
        <div class="panel-item">
            <h3>实时库存摘要</h3>
            <?php if (empty($summary)): ?>
                <p class="empty">暂无数据</p>
            <?php else: ?>
                <ul class="summary-list">
                    <?php foreach ($summary as $sku => $counts): ?>
                        <li>
                            <span class="sku"><?= htmlspecialchars($sku) ?></span>
                            <span class="pill">在库 <?= (int)($counts['in_stock'] ?? 0) ?></span>
                            <span class="pill secondary">已出 <?= (int)($counts['shipped'] ?? 0) ?></span>
                            <span class="pill danger">损耗 <?= (int)($counts['void'] ?? 0) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="panel-item">
            <h3>最新动态</h3>
            <ul class="recent-list">
                <?php if (empty($recent)): ?>
                    <li class="empty">暂无记录</li>
                <?php else: ?>
                    <?php foreach ($recent as $item): ?>
                        <li>
                            <span class="sku"><?= htmlspecialchars($item['sku_name']) ?></span>
                            <span class="meta">批次 <?= htmlspecialchars($item['batch_code']) ?> | 箱 <?= htmlspecialchars($item['box_number']) ?></span>
                            <span class="status status-<?= htmlspecialchars($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </section>

    <?php if (!empty($messages)): ?>
        <section class="panel">
            <div class="panel-header"><h2>操作反馈</h2></div>
            <div class="panel-body">
                <?php foreach ($messages as $msg): ?>
                    <div class="alert <?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['text']) ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="package_ids[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>
</body>
</html>
