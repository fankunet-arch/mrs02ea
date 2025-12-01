<?php
mrs_require_login();

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    if ($formType === 'inbound') {
        $sku = $_POST['sku_name'] ?? '';
        $batch = $_POST['batch_code'] ?? '';
        $range = $_POST['box_range'] ?? '';
        $spec = $_POST['spec_info'] ?? '';
        try {
            $count = mrs_save_inbound_range($pdo, $sku, $batch, $range, $spec, $_SESSION['user_login'] ?? 'operator');
            $messages[] = ['type' => 'success', 'text' => "成功新增 {$count} 条入库记录"];
        } catch (Throwable $e) {
            $messages[] = ['type' => 'error', 'text' => '入库失败：' . $e->getMessage()];
        }
    } elseif (in_array($formType, ['outbound', 'void'], true)) {
        $ids = $_POST['package_ids'] ?? [];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        if (empty($ids)) {
            $messages[] = ['type' => 'warning', 'text' => '请先勾选包裹'];
        } else {
            if ($formType === 'outbound') {
                $updated = mrs_mark_outbound($pdo, $ids, $_SESSION['user_login'] ?? 'operator');
                $messages[] = ['type' => 'success', 'text' => "已出库 {$updated} 条记录"];
            } else {
                $reason = trim($_POST['void_reason'] ?? '') ?: null;
                $updated = mrs_mark_void($pdo, $ids, $reason, $_SESSION['user_login'] ?? 'operator');
                $messages[] = ['type' => 'success', 'text' => "已作废 {$updated} 条记录"];
            }
        }
    }
}

$inventory = mrs_fetch_inventory($pdo, ['status' => 'in_stock']);
$summary = mrs_fetch_inventory_summary($pdo);
$month = date('Y-m');
$flow = mrs_fetch_monthly_flow($pdo, $month);
$recent = mrs_fetch_recent_packages($pdo, 10);

require_once MRS_VIEW_PATH . '/dashboard.php';
