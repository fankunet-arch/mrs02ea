<?php include __DIR__ . '/shared/header.php'; ?>
<section class="card">
    <div class="card-header">
        <h1>入库登记</h1>
        <p class="muted">遵循“货-批-号”三要素，一次批量生成多个箱号。</p>
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
    <form method="post" class="grid form two-col">
        <label>物料（货）
            <input type="text" name="sku_name" placeholder="如：香蕉" required>
        </label>
        <label>批次（批）
            <input type="text" name="batch_code" placeholder="如：A01" required>
        </label>
        <label>箱号范围（号）
            <input type="text" name="box_range" placeholder="如：1-5 或 0001,0002" required>
            <small class="muted">支持区间、逗号或换行分隔，系统自动补齐4位数。</small>
        </label>
        <label>单箱规格
            <input type="text" name="spec_info" placeholder="如：20斤/箱（可选）">
        </label>
        <div class="full">
            <button type="submit" class="btn primary">生成入库记录</button>
        </div>
    </form>
</section>

<section class="card">
    <div class="card-header">
        <h2>最近创建</h2>
        <span class="muted">按入库时间倒序显示</span>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_inbound)): ?>
                    <tr><td colspan="5" class="muted">暂无记录</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_inbound as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['sku_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['batch_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['box_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['spec_info'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['inbound_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/shared/footer.php'; ?>
