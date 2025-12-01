<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$sku = $_POST['sku_name'] ?? '';
$batch = $_POST['batch_code'] ?? '';
$boxes = $_POST['box_range'] ?? '';
$spec = $_POST['spec_info'] ?? null;
$user_id = $_SESSION['mrs_user_id'] ?? null;

$result = mrs_create_inbound_entries($pdo, $sku, $batch, $boxes, $spec, $user_id);

if ($result['success']) {
    $message = sprintf('已创建 %d 条记录，跳过 %d 条重复', $result['created'], count($result['skipped'] ?? []));
    if (!empty($result['skipped'])) {
        $message .= ' (重复箱号: ' . implode(', ', $result['skipped']) . ')';
    }
    header('Location: /mrs/ap/index.php?action=inbound&msg=' . urlencode($message));
    exit;
}

header('Location: /mrs/ap/index.php?action=inbound&error=' . urlencode($result['message'] ?? '保存失败'));
exit;
