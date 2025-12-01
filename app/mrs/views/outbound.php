<?php
if (!defined('MRS_ENTRY')) { die('Access denied'); }
require MRS_VIEW_PATH . '/partials/header.php';

$filterSku = $_GET['sku'] ?? '';
$filterBatch = $_GET['batch'] ?? '';

$where = ["status = 'in_stock'"];
$params = [];
if ($filterSku !== '') {
    $where[] = 'sku_name LIKE :sku';
    $params[':sku'] = '%' . $filterSku . '%';
}
if ($filterBatch !== '') {
    $where[] = 'batch_code LIKE :batch';
    $params[':batch'] = '%' . $filterBatch . '%';
}
$sql = 'SELECT * FROM mrs_package_ledger WHERE ' . implode(' AND ', $where) . ' ORDER BY inbound_time ASC, package_id ASC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();
?>
<h1>出库核销</h1>
<p class="muted">按入库时间自动排序，勾选对应包裹后提交。出库后记录不可撤销。</p>
<form class="filter" method="get" action="/mrs/ap/index.php">
    <input type="hidden" name="action" value="outbound">
    <label>物料关键词</label>
    <input type="text" name="sku" value="<?php echo htmlspecialchars($filterSku); ?>" placeholder="例如：香蕉">
    <label>批次关键词</label>
    <input type="text" name="batch" value="<?php echo htmlspecialchars($filterBatch); ?>" placeholder="例如：A01">
    <button type="submit">筛选</button>
</form>
<form method="post" action="/mrs/ap/index.php?action=outbound_save">
<table class="table">
    <thead>
        <tr>
            <th><input type="checkbox" id="check-all"></th>
            <th>ID</th>
            <th>物料</th>
            <th>批次</th>
            <th>箱号</th>
            <th>规格</th>
            <th>入库时间</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($packages as $row): ?>
            <tr>
                <td><input type="checkbox" name="package_ids[]" value="<?php echo $row['package_id']; ?>"></td>
                <td>#<?php echo $row['package_id']; ?></td>
                <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_code']); ?></td>
                <td><?php echo htmlspecialchars($row['box_number']); ?></td>
                <td><?php echo htmlspecialchars($row['spec_info'] ?? ''); ?></td>
                <td><?php echo mrs_format_datetime($row['inbound_time']); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($packages)): ?>
            <tr><td colspan="7" class="empty">暂无在库包裹</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="form-actions">
    <button type="submit">确认出库</button>
</div>
</form>
<script>
const checkAll = document.getElementById('check-all');
if (checkAll) {
    checkAll.addEventListener('change', () => {
        document.querySelectorAll('input[name="package_ids[]"]').forEach(cb => cb.checked = checkAll.checked);
    });
}
</script>
<?php require MRS_VIEW_PATH . '/partials/footer.php'; ?>
