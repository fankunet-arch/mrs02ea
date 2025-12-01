<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$sku_name = trim($_POST['sku_name'] ?? '');
$batch_code = trim($_POST['batch_code'] ?? '');
$range_start = (int)($_POST['range_start'] ?? 0);
$range_end = (int)($_POST['range_end'] ?? 0);
$spec_info = trim($_POST['spec_info'] ?? '') ?: null;

if ($sku_name === '' || $batch_code === '' || $range_start === 0 || $range_end === 0) {
    mrs_json_response(false, null, '请填写完整的物料、批次和箱号范围');
}

try {
    $result = mrs_create_inbound_packages(
        $pdo,
        $sku_name,
        $batch_code,
        $range_start,
        $range_end,
        $spec_info,
        $_SESSION['mrs_user_login'] ?? null
    );

    $message = sprintf('成功入库 %d 箱', $result['inserted']);
    if (!empty($result['skipped'])) {
        $message .= '，重复跳过: ' . implode(', ', $result['skipped']);
    }

    mrs_json_response(true, $result, $message);
} catch (Throwable $e) {
    mrs_json_response(false, null, $e->getMessage());
}
