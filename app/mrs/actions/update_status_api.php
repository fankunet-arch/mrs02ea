<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$package_id = (int)($_POST['package_id'] ?? 0);
$new_status = $_POST['status'] ?? '';

if ($package_id <= 0 || $new_status === '') {
    mrs_json_response(false, null, '缺少必要参数');
}

try {
    mrs_update_package_status($pdo, $package_id, $new_status, $_SESSION['mrs_user_login'] ?? null);
    mrs_json_response(true, null, '状态已更新');
} catch (Throwable $e) {
    mrs_json_response(false, null, $e->getMessage());
}
