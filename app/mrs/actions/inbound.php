<?php
mrs_require_login();

$message = null;
$error_list = [];
$recent_inbound = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku_name = trim($_POST['sku_name'] ?? '');
    $batch_code = trim($_POST['batch_code'] ?? '');
    $box_range = trim($_POST['box_range'] ?? '');
    $spec_info = trim($_POST['spec_info'] ?? '') ?: null;

    if ($sku_name === '' || $batch_code === '' || $box_range === '') {
        $error_list[] = '物料、批次和箱号范围均为必填。';
    } else {
        $result = mrs_bulk_create_ledger_entries($pdo, $sku_name, $batch_code, $box_range, $spec_info);
        if (!empty($result['errors'])) {
            $error_list = array_merge($error_list, $result['errors']);
        }
        if ($result['created'] > 0) {
            $message = sprintf('成功创建 %d 条记录，跳过 %d 条重复。', $result['created'], $result['skipped']);
        } elseif ($result['skipped'] > 0) {
            $message = sprintf('全部为重复数据，跳过 %d 条。', $result['skipped']);
        }
    }
}

$recent_inbound = mrs_get_recent_inbound($pdo, 15);

mrs_render('inbound', [
    'message' => $message,
    'errors' => $error_list,
    'recent_inbound' => $recent_inbound,
]);
