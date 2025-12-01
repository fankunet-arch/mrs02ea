<?php
mrs_require_login();

$sku_filter = trim($_GET['sku_name'] ?? '');
$batch_filter = trim($_GET['batch_code'] ?? '');
$packages = mrs_get_available_packages($pdo, $sku_filter ?: null, $batch_filter ?: null);
$message = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = array_map('intval', $_POST['package_ids'] ?? []);
    $result = mrs_mark_shipped($pdo, $selected);
    $packages = mrs_get_available_packages($pdo, $sku_filter ?: null, $batch_filter ?: null);
    if (!empty($result['errors'])) {
        $errors = $result['errors'];
    }
    if ($result['updated'] > 0) {
        $message = sprintf('成功出库 %d 条记录。', $result['updated']);
    }
}

mrs_render('outbound', [
    'packages' => $packages,
    'message' => $message,
    'errors' => $errors,
    'sku_filter' => $sku_filter,
    'batch_filter' => $batch_filter,
]);
