<?php include __DIR__ . '/shared/header.php'; ?>
<section class="card">
    <h1>总览</h1>
    <div class="grid stats">
        <div class="stat">
            <div class="label">在库</div>
            <div class="value text-green"><?php echo (int)($counts['in_stock'] ?? 0); ?></div>
        </div>
        <div class="stat">
            <div class="label">已出库</div>
            <div class="value text-blue"><?php echo (int)($counts['shipped'] ?? 0); ?></div>
        </div>
        <div class="stat">
            <div class="label">损耗/作废</div>
            <div class="value text-gray"><?php echo (int)($counts['void'] ?? 0); ?></div>
        </div>
        <div class="stat">
            <div class="label"><?php echo htmlspecialchars($current_month, ENT_QUOTES, 'UTF-8'); ?> 入/出库</div>
            <div class="value">
                <span class="badge">入 <?php echo (int)$flow['inbound']; ?></span>
                <span class="badge">出 <?php echo (int)$flow['outbound']; ?></span>
            </div>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2>最近入库</h2>
        <a class="btn ghost" href="/mrs/ap/index.php?action=inbound">去入库</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>物料</th>
                    <th>批次</th>
                    <th>箱号</th>
                    <th>规格</th>
                    <th>入库时间</th>
                    <th>状态</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_inbound)): ?>
                    <tr><td colspan="6" class="muted">暂无记录</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_inbound as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['sku_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['batch_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['box_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['spec_info'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['inbound_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge status-<?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/shared/footer.php'; ?>
