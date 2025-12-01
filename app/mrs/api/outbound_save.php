<?php
/**
 * API: Save Outbound
 * 文件路径: app/mrs/api/outbound_save.php
 */

if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mrs_json_response(false, null, '非法请求方式');
}

$input = mrs_get_json_input();
if (!$input) {
    $input = $_POST;
}

$package_ids = $input['package_ids'] ?? [];

if (empty($package_ids) || !is_array($package_ids)) {
    mrs_json_response(false, null, '请选择要出库的包裹');
}

// 获取操作员
$operator = $_SESSION['user_login'] ?? 'system';

// 执行出库
$result = mrs_outbound_packages($pdo, $package_ids, $operator);

if ($result['success']) {
    mrs_json_response(true, null, $result['message']);
} else {
    mrs_json_response(false, null, $result['message']);
}
