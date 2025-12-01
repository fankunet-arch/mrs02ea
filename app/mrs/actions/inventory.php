<?php
mrs_require_login();
$page_title = '库存快照';
$data = mrs_get_inventory_snapshot($pdo);
include MRS_VIEW_PATH . '/header.php';
?>
<section class="card">
    <h2>库存汇总</h2>
    <?php if (empty($data['summary'])): ?>
        <p>当前没有在库包裹。</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>物料</th><th>在库箱数</th></tr></thead>
            <tbody>
                <?php foreach ($data['summary'] as $row): ?>
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
    <h2>在库明细</h2>
    <?php if (empty($data['detail'])): ?>
        <p>暂无明细。</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>物料</th><th>批次</th><th>箱号</th><th>规格</th><th>入库时间</th></tr></thead>
            <tbody>
                <?php foreach ($data['detail'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['batch_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['box_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['spec_info']); ?></td>
                        <td><?php echo htmlspecialchars($row['inbound_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php include MRS_VIEW_PATH . '/footer.php'; ?>
