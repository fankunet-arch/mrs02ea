<?php
mrs_require_login();

$counts = mrs_get_dashboard_counts($pdo);
$recent_inbound = mrs_get_recent_inbound($pdo, 10);
$current_month = date('Y-m');
$flow = mrs_get_monthly_flow($pdo, $current_month);

mrs_render('dashboard', [
    'counts' => $counts,
    'recent_inbound' => $recent_inbound,
    'flow' => $flow,
    'current_month' => $current_month,
]);
