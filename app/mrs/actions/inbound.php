<?php
mrs_require_login();
$page_title = '入库录入';
$message = null;
$error = null;
$skus = mrs_fetch_skus($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku_name = $_POST['sku_name'] ?? '';
    $batch_code = $_POST['batch_code'] ?? '';
    $box_range = $_POST['box_range'] ?? '';
    $spec_info = $_POST['spec_info'] ?? '';

    if (!$sku_name || !$batch_code || !$box_range) {
        $error = '物料、批次、箱号范围为必填项。';
    } else {
        $result = mrs_bulk_create_packages($pdo, $sku_name, $batch_code, $box_range, $spec_info, $_SESSION['user_login']);
        if ($result['success']) {
            $message = sprintf('成功创建 %d 条入库记录。', $result['count']);
        } else {
            $error = $result['message'];
        }
    }
}

include MRS_VIEW_PATH . '/header.php';
?>
<section class="card">
    <h2>入库录入</h2>
    <p>支持输入箱号范围（如 1-5 自动生成 5 条记录），遵循“货-批-号”三要素。</p>
    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <label>物料名称</label>
        <input list="sku-list" type="text" name="sku_name" required placeholder="例如：香蕉" value="<?php echo htmlspecialchars($_POST['sku_name'] ?? ''); ?>">
        <datalist id="sku-list">
            <?php foreach ($skus as $sku): ?>
                <option value="<?php echo htmlspecialchars($sku['sku_name']); ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <label>批次号</label>
        <input type="text" name="batch_code" required placeholder="例如：A01" value="<?php echo htmlspecialchars($_POST['batch_code'] ?? ''); ?>">

        <label>箱号范围</label>
        <input type="text" name="box_range" required placeholder="例如：1-10 或 7" value="<?php echo htmlspecialchars($_POST['box_range'] ?? ''); ?>">

        <label>单箱规格（可选）</label>
        <input type="text" name="spec_info" placeholder="例如：20斤" value="<?php echo htmlspecialchars($_POST['spec_info'] ?? ''); ?>">

        <button type="submit" class="btn">批量建账</button>
    </form>
</section>
<?php include MRS_VIEW_PATH . '/footer.php'; ?>
