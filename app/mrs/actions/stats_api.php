<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$month = $_GET['month'] ?? date('Y-m');

$flow = mrs_get_monthly_flow($pdo, $month);
$inventory = mrs_get_inventory_snapshot($pdo);

mrs_json_response(true, [
    'flow' => $flow,
    'inventory' => $inventory,
], 'ok');
