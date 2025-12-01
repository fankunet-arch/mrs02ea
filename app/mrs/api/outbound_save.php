<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$package_ids = $_POST['package_ids'] ?? [];
if (is_string($package_ids)) {
    $package_ids = [$package_ids];
}
$package_ids = array_map('intval', $package_ids);
$user_id = $_SESSION['mrs_user_id'] ?? null;

$result = mrs_mark_outbound($pdo, $package_ids, $user_id);

if ($result['success']) {
    $message = sprintf('已出库 %d 条记录', $result['updated'] ?? 0);
    header('Location: /mrs/ap/index.php?action=outbound&msg=' . urlencode($message));
    exit;
}

header('Location: /mrs/ap/index.php?action=outbound&error=' . urlencode($result['message'] ?? '出库失败'));
exit;
