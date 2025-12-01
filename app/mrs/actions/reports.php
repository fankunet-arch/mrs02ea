<?php
mrs_require_login();
$page_title = '统计报表';
$month = $_GET['month'] ?? date('Y-m');
$stats = mrs_get_monthly_stats($pdo, $month);
include MRS_VIEW_PATH . '/header.php';
?>
<section class="card">
    <h2>月度收发统计</h2>
    <form method="get" class="form-inline">
        <label>统计月份</label>
        <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
        <button type="submit" class="btn">查询</button>
    </form>
    <div class="stat-grid">
        <div class="stat">
            <div class="stat-label">入库包裹数</div>
            <div class="stat-value"><?php echo (int)$stats['inbound_count']; ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">出库包裹数</div>
            <div class="stat-value"><?php echo (int)$stats['outbound_count']; ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">涉及物料数</div>
            <div class="stat-value"><?php echo (int)$stats['sku_count']; ?></div>
        </div>
    </div>
</section>
<?php include MRS_VIEW_PATH . '/footer.php'; ?>
