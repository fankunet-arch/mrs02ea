<?php
mrs_require_login();
$page_title = 'MRS 仪表盘';
$stats = mrs_get_recent_operations($pdo, 10);
$inventory = mrs_get_inventory_snapshot($pdo);
include MRS_VIEW_PATH . '/header.php';
?>
<section class="card">
    <h2>快捷入口</h2>
    <div class="quick-links">
        <a class="btn" href="/mrs/ap/index.php?action=inbound">入库录入</a>
        <a class="btn" href="/mrs/ap/index.php?action=outbound">出库核销</a>
        <a class="btn" href="/mrs/ap/index.php?action=inventory">库存快照</a>
        <a class="btn" href="/mrs/ap/index.php?action=reports">统计报表</a>
    </div>
</section>
<section class="card">
    <h2>库存概览</h2>
    <?php if (empty($inventory['summary'])): ?>
        <p>当前没有在库包裹。</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>物料</th><th>在库箱数</th></tr>
            </thead>
            <tbody>
                <?php foreach ($inventory['summary'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                        <td><?php echo (int)$row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<section class="card">
    <h2>最近操作</h2>
    <?php if (empty($stats)): ?>
        <p>暂无操作记录。</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>ID</th><th>物料</th><th>批次</th><th>箱号</th><th>状态</th><th>时间</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $item): ?>
                    <tr>
                        <td><?php echo (int)$item['package_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['sku_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['batch_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['box_number']); ?></td>
                        <td><?php echo htmlspecialchars($item['status']); ?></td>
                        <td><?php echo htmlspecialchars($item['updated_at'] ?: $item['inbound_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php include MRS_VIEW_PATH . '/footer.php'; ?>
