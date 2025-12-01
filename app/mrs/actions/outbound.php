<?php
mrs_require_login();
$page_title = '出库核销';
$error = null;
$message = null;
$selected_sku = $_GET['sku_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_sku = $_POST['sku_name'] ?? '';
    $package_ids = $_POST['package_ids'] ?? [];
    $result = mrs_ship_packages($pdo, $package_ids, $_SESSION['user_login']);
    if ($result['success']) {
        $message = sprintf('已成功出库 %d 个包裹。', $result['affected']);
    } else {
        $error = $result['message'];
    }
}

$available = mrs_get_available_packages($pdo, $selected_sku, 200);
$skus = mrs_fetch_skus($pdo);

include MRS_VIEW_PATH . '/header.php';
?>
<section class="card">
    <h2>出库核销</h2>
    <p>按入库时间 FIFO 列出可用包裹，勾选后完成出库。</p>
    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="get" class="form-inline">
        <label>物料筛选</label>
        <select name="sku_name">
            <option value="">全部物料</option>
            <?php foreach ($skus as $sku): ?>
                <option value="<?php echo htmlspecialchars($sku['sku_name']); ?>" <?php echo $selected_sku === $sku['sku_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sku['sku_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">筛选</button>
    </form>
    <form method="post" class="table-form">
        <input type="hidden" name="sku_name" value="<?php echo htmlspecialchars($selected_sku); ?>">
        <?php if (empty($available)): ?>
            <p>暂无可出库包裹。</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="check-all" onclick="document.querySelectorAll('.pkg-checkbox').forEach(cb => cb.checked = this.checked);"></th>
                        <th>物料</th><th>批次</th><th>箱号</th><th>规格</th><th>入库时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available as $pkg): ?>
                        <tr>
                            <td><input class="pkg-checkbox" type="checkbox" name="package_ids[]" value="<?php echo (int)$pkg['package_id']; ?>"></td>
                            <td><?php echo htmlspecialchars($pkg['sku_name']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['batch_code']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['box_number']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['spec_info']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['inbound_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn danger">确认出库</button>
        <?php endif; ?>
    </form>
</section>
<?php include MRS_VIEW_PATH . '/footer.php'; ?>
