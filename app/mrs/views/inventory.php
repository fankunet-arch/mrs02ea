<?php include __DIR__ . '/shared/header.php'; ?>
<section class="card">
    <div class="card-header">
        <h1>库存快照</h1>
        <p class="muted">回答“现在库里还有什么？”</p>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>物料</th>
                    <th>批次</th>
                    <th>在库</th>
                    <th>已出库</th>
                    <th>损耗</th>
                    <th>合计</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshot)): ?>
                    <tr><td colspan="6" class="muted">暂无库存数据</td></tr>
                <?php else: ?>
                    <?php foreach ($snapshot as $sku => $detail): ?>
                        <?php foreach ($detail['batches'] as $batch => $status_counts): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sku, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($batch, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)($status_counts['in_stock'] ?? 0); ?></td>
                                <td><?php echo (int)($status_counts['shipped'] ?? 0); ?></td>
                                <td><?php echo (int)($status_counts['void'] ?? 0); ?></td>
                                <td><?php echo (int)$detail['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/shared/footer.php'; ?>
