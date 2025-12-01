<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$current_month = date('Y-m');
$flow = mrs_get_monthly_flow($pdo, $current_month);
$inventory = mrs_get_inventory_snapshot($pdo);
$recent_packages = mrs_get_packages($pdo, ['status' => 'in_stock']);

require MRS_VIEW_PATH . '/dashboard_view.php';
