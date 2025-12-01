<?php
if (!defined('MRS_ENTRY')) { die('Access denied'); }
require MRS_VIEW_PATH . '/partials/header.php';

$metrics = mrs_get_dashboard_metrics($pdo);
$recent = mrs_get_recent_activity($pdo, 8);
?>
<div class="grid metrics">
    <div class="metric">
        <div class="label">台账总数</div>
        <div class="value"><?php echo $metrics['total']; ?></div>
        <div class="note">含在库、已出库与损耗</div>
    </div>
    <div class="metric">
        <div class="label">在库</div>
        <div class="value text-green"><?php echo $metrics['in_stock']; ?></div>
        <div class="note">可用于出库选择</div>
    </div>
    <div class="metric">
        <div class="label">本月入库</div>
        <div class="value"><?php echo $metrics['flow']['inbound_count']; ?></div>
        <div class="note"><?php echo htmlspecialchars($metrics['flow']['month']); ?> 月</div>
    </div>
    <div class="metric">
        <div class="label">本月出库</div>
        <div class="value"><?php echo $metrics['flow']['outbound_count']; ?></div>
        <div class="note">涉及 <?php echo $metrics['flow']['sku_count']; ?> 种物料</div>
    </div>
</div>

<h2>最近更新</h2>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>物料</th>
            <th>批次</th>
            <th>箱号</th>
            <th>状态</th>
            <th>入库</th>
            <th>出库</th>
            <th>最后更新</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recent as $row): ?>
            <tr>
                <td>#<?php echo $row['package_id']; ?></td>
                <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_code']); ?></td>
                <td><?php echo htmlspecialchars($row['box_number']); ?></td>
                <td><span class="status status-<?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td><?php echo mrs_format_datetime($row['inbound_time']); ?></td>
                <td><?php echo mrs_format_datetime($row['outbound_time']); ?></td>
                <td><?php echo mrs_format_datetime($row['updated_at'] ?? $row['created_at'] ?? null); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recent)): ?>
            <tr><td colspan="8" class="empty">暂无记录</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php require MRS_VIEW_PATH . '/partials/footer.php'; ?>
