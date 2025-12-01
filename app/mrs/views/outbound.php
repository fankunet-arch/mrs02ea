<?php include __DIR__ . '/shared/header.php'; ?>
<section class="card">
    <div class="card-header">
        <h1>出库核销</h1>
        <p class="muted">按入库时间 FIFO 显示可出库包裹，勾选后确认出库。</p>
    </div>
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="get" class="filters">
        <input type="hidden" name="action" value="outbound">
        <input type="text" name="sku_name" placeholder="物料" value="<?php echo htmlspecialchars($sku_filter, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="text" name="batch_code" placeholder="批次" value="<?php echo htmlspecialchars($batch_filter, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn">筛选</button>
    </form>

    <form method="post">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:48px;"><input type="checkbox" id="toggle-all"></th>
                        <th>物料</th>
                        <th>批次</th>
                        <th>箱号</th>
                        <th>规格</th>
                        <th>入库时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr><td colspan="6" class="muted">当前筛选下无可用库存</td></tr>
                    <?php else: ?>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td><input type="checkbox" name="package_ids[]" value="<?php echo (int)$pkg['package_id']; ?>" class="row-check"></td>
                                <td><?php echo htmlspecialchars($pkg['sku_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($pkg['batch_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($pkg['box_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($pkg['spec_info'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($pkg['inbound_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn primary">确认出库</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/shared/footer.php'; ?>
