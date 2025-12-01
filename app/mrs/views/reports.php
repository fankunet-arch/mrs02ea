<?php
if (!defined('MRS_ENTRY')) { die('Access denied'); }
require MRS_VIEW_PATH . '/partials/header.php';

$month = $_GET['month'] ?? date('Y-m');
$flow = mrs_get_monthly_flow($pdo, $month);
$summary = mrs_get_inventory_summary($pdo);
?>
<h1>统计报表</h1>
<form class="filter" method="get" action="/mrs/ap/index.php">
    <input type="hidden" name="action" value="reports">
    <label>月份</label>
    <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
    <button type="submit">刷新</button>
</form>
<div class="grid metrics">
    <div class="metric">
        <div class="label">入库（<?php echo htmlspecialchars($flow['month']); ?>）</div>
        <div class="value"><?php echo $flow['inbound_count']; ?></div>
        <div class="note">包裹数</div>
    </div>
    <div class="metric">
        <div class="label">出库（<?php echo htmlspecialchars($flow['month']); ?>）</div>
        <div class="value"><?php echo $flow['outbound_count']; ?></div>
        <div class="note">包裹数</div>
    </div>
    <div class="metric">
        <div class="label">物料覆盖</div>
        <div class="value"><?php echo $flow['sku_count']; ?></div>
        <div class="note">当月涉及物料种类</div>
    </div>
</div>
<h2>实时库存快照</h2>
<table class="table">
    <thead>
        <tr>
            <th>物料</th>
            <th>总计</th>
            <th>在库</th>
            <th>已出库</th>
            <th>损耗</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($summary as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['sku_name']); ?></td>
                <td><?php echo $row['total']; ?></td>
                <td><?php echo $row['in_stock']; ?></td>
                <td><?php echo $row['shipped']; ?></td>
                <td><?php echo $row['void_count']; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($summary)): ?>
            <tr><td colspan="5" class="empty">暂无库存数据</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php require MRS_VIEW_PATH . '/partials/footer.php'; ?>
