<?php
if (!defined('MRS_ENTRY')) { die('Access denied'); }
require MRS_VIEW_PATH . '/partials/header.php';

$status = $_GET['status'] ?? 'all';
$skuFilter = $_GET['sku'] ?? '';
$batchFilter = $_GET['batch'] ?? '';
$items = mrs_get_inventory($pdo, ['status' => $status, 'sku' => $skuFilter, 'batch' => $batchFilter]);
?>
<h1>库存清单</h1>
<form class="filter" method="get" action="/mrs/ap/index.php">
    <input type="hidden" name="action" value="inventory">
    <label>状态</label>
    <select name="status">
        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>全部</option>
        <option value="in_stock" <?php echo $status === 'in_stock' ? 'selected' : ''; ?>>在库</option>
        <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>已出库</option>
        <option value="void" <?php echo $status === 'void' ? 'selected' : ''; ?>>损耗</option>
    </select>
    <label>物料</label>
    <input type="text" name="sku" value="<?php echo htmlspecialchars($skuFilter); ?>" placeholder="关键词">
    <label>批次</label>
    <input type="text" name="batch" value="<?php echo htmlspecialchars($batchFilter); ?>" placeholder="关键词">
    <button type="submit">筛选</button>
</form>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>物料</th>
            <th>批次</th>
            <th>箱号</th>
            <th>规格</th>
            <th>状态</th>
            <th>入库</th>
            <th>出库</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $row): ?>
            <tr>
                <td>#<?php echo $row['package_id']; ?></td>
                <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_code']); ?></td>
                <td><?php echo htmlspecialchars($row['box_number']); ?></td>
                <td><?php echo htmlspecialchars($row['spec_info'] ?? ''); ?></td>
                <td><span class="status status-<?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td><?php echo mrs_format_datetime($row['inbound_time']); ?></td>
                <td><?php echo mrs_format_datetime($row['outbound_time']); ?></td>
                <td>
                    <?php if ($row['status'] !== 'shipped'): ?>
                        <form method="post" action="/mrs/ap/index.php?action=update_status" class="inline-form">
                            <input type="hidden" name="package_id" value="<?php echo $row['package_id']; ?>">
                            <select name="status">
                                <option value="in_stock" <?php echo $row['status']==='in_stock'?'selected':''; ?>>在库</option>
                                <option value="void" <?php echo $row['status']==='void'?'selected':''; ?>>损耗</option>
                            </select>
                            <input type="text" name="status_note" placeholder="备注" value="<?php echo htmlspecialchars($row['status_note'] ?? ''); ?>">
                            <button type="submit">保存</button>
                        </form>
                    <?php else: ?>
                        <span class="muted">已出库不可更改</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
            <tr><td colspan="9" class="empty">无匹配记录</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php require MRS_VIEW_PATH . '/partials/footer.php'; ?>
