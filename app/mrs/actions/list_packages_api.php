<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$status = $_GET['status'] ?? 'in_stock';
$sku_name = trim($_GET['sku_name'] ?? '');
$batch_code = trim($_GET['batch_code'] ?? '');

$packages = mrs_get_packages($pdo, [
    'status' => $status,
    'sku_name' => $sku_name,
    'batch_code' => $batch_code,
]);

mrs_json_response(true, $packages, 'ok');
