<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_require_login();

$package_id = (int)($_POST['package_id'] ?? 0);
$status = $_POST['status'] ?? '';
$note = $_POST['status_note'] ?? null;
$user_id = $_SESSION['mrs_user_id'] ?? null;

$result = mrs_update_package_status($pdo, $package_id, $status, $note, $user_id);

if ($result['success']) {
    header('Location: /mrs/ap/index.php?action=inventory&msg=' . urlencode('状态已更新'));
    exit;
}

header('Location: /mrs/ap/index.php?action=inventory&error=' . urlencode($result['message'] ?? '更新失败'));
exit;
