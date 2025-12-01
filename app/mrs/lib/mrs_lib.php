<?php
/**
 * MRS Package Ledger System - Core Library
 */

// =============
// 认证逻辑（与 Express 共用 sys_users 表）
// =============

function mrs_authenticate_user(PDO $pdo, string $username, string $password)
{
    try {
        $stmt = $pdo->prepare('SELECT user_id, user_login, user_secret_hash, user_email, user_display_name, user_status FROM sys_users WHERE user_login = :username LIMIT 1');
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            mrs_log("登录失败: 用户不存在 - {$username}", 'WARNING');
            return false;
        }

        if ($user['user_status'] !== 'active') {
            mrs_log("登录失败: 账户未激活 - {$username}", 'WARNING');
            return false;
        }

        if (!password_verify($password, $user['user_secret_hash'])) {
            mrs_log("登录失败: 密码错误 - {$username}", 'WARNING');
            return false;
        }

        $update = $pdo->prepare('UPDATE sys_users SET user_last_login_at = NOW(6) WHERE user_id = :uid');
        $update->bindValue(':uid', $user['user_id'], PDO::PARAM_INT);
        $update->execute();

        unset($user['user_secret_hash']);
        mrs_log("登录成功: {$username}", 'INFO');
        return $user;
    } catch (PDOException $e) {
        mrs_log('用户认证失败: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

function mrs_create_user_session(array $user): void
{
    mrs_start_secure_session();
    $_SESSION['mrs_user_id'] = $user['user_id'];
    $_SESSION['mrs_user_login'] = $user['user_login'];
    $_SESSION['mrs_user_display_name'] = $user['user_display_name'];
    $_SESSION['mrs_user_email'] = $user['user_email'];
    $_SESSION['mrs_logged_in'] = true;
    $_SESSION['mrs_login_time'] = time();
    $_SESSION['mrs_last_activity'] = time();
}

function mrs_is_user_logged_in(): bool
{
    mrs_start_secure_session();

    if (!isset($_SESSION['mrs_logged_in']) || $_SESSION['mrs_logged_in'] !== true) {
        return false;
    }

    $timeout = 1800;
    if (isset($_SESSION['mrs_last_activity']) && (time() - $_SESSION['mrs_last_activity']) > $timeout) {
        mrs_destroy_user_session();
        return false;
    }

    $_SESSION['mrs_last_activity'] = time();
    return true;
}

function mrs_destroy_user_session(): void
{
    mrs_start_secure_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function mrs_require_login(): void
{
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

// =============
// 业务逻辑
// =============

function mrs_expand_box_numbers(string $range_input): array
{
    $result = [];
    $parts = preg_split('/\s*,\s*/', trim($range_input));
    $padding = 4;

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if (strpos($part, '-') !== false) {
            [$start, $end] = array_map('trim', explode('-', $part));
            if ($start === '' || $end === '') {
                continue;
            }
            $width = max(strlen($start), strlen($end));
            $padding = max($padding, $width);
            $startNum = (int)$start;
            $endNum = (int)$end;
            if ($endNum < $startNum) {
                [$startNum, $endNum] = [$endNum, $startNum];
            }
            for ($i = $startNum; $i <= $endNum; $i++) {
                $result[] = str_pad((string)$i, $padding, '0', STR_PAD_LEFT);
            }
        } else {
            $padding = max($padding, strlen($part));
            $num = (int)$part;
            $result[] = str_pad((string)$num, $padding, '0', STR_PAD_LEFT);
        }
    }

    return array_values(array_unique($result));
}

function mrs_create_inbound_entries(PDO $pdo, string $sku, string $batch, string $box_range, ?string $spec_info = null, ?int $user_id = null): array
{
    $sku = trim($sku);
    $batch = trim($batch);
    $spec_info = $spec_info !== null ? trim($spec_info) : null;

    if ($sku === '' || $batch === '' || $box_range === '') {
        return ['success' => false, 'message' => '物料、批次和箱号范围不能为空'];
    }

    $boxes = mrs_expand_box_numbers($box_range);
    if (empty($boxes)) {
        return ['success' => false, 'message' => '箱号范围格式不正确'];
    }

    $now = date('Y-m-d H:i:s');
    $created = 0;
    $skipped = [];

    try {
        $pdo->beginTransaction();
        foreach ($boxes as $box) {
            $exists = $pdo->prepare('SELECT package_id, status FROM mrs_package_ledger WHERE sku_name = :sku AND batch_code = :batch AND box_number = :box LIMIT 1');
            $exists->execute([':sku' => $sku, ':batch' => $batch, ':box' => $box]);
            if ($exists->fetch()) {
                $skipped[] = $box;
                continue;
            }

            $insert = $pdo->prepare('INSERT INTO mrs_package_ledger (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_at, created_by) VALUES (:sku, :batch, :box, :spec, :status, :inbound_time, :created_at, :created_by)');
            $insert->execute([
                ':sku' => $sku,
                ':batch' => $batch,
                ':box' => $box,
                ':spec' => $spec_info,
                ':status' => 'in_stock',
                ':inbound_time' => $now,
                ':created_at' => $now,
                ':created_by' => $user_id,
            ]);
            $created++;
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('入库登记失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '入库登记失败，请稍后重试'];
    }

    return ['success' => true, 'created' => $created, 'skipped' => $skipped, 'timestamp' => $now];
}

function mrs_get_inventory(PDO $pdo, array $filters = []): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['sku'])) {
        $where[] = 'sku_name LIKE :sku';
        $params[':sku'] = '%' . $filters['sku'] . '%';
    }

    if (!empty($filters['batch'])) {
        $where[] = 'batch_code LIKE :batch';
        $params[':batch'] = '%' . $filters['batch'] . '%';
    }

    $sql = 'SELECT * FROM mrs_package_ledger';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY inbound_time DESC, package_id DESC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function mrs_mark_outbound(PDO $pdo, array $package_ids, ?int $user_id = null): array
{
    if (empty($package_ids)) {
        return ['success' => false, 'message' => '请选择要出库的包裹'];
    }

    $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
    $now = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE mrs_package_ledger SET status = 'shipped', outbound_time = ?, updated_at = ?, updated_by = ? WHERE package_id IN ($placeholders) AND status = 'in_stock'");
        $params = array_merge([$now, $now, $user_id], $package_ids);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        $pdo->commit();
        return ['success' => true, 'updated' => $affected, 'timestamp' => $now];
    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('出库失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '出库失败，请稍后重试'];
    }
}

function mrs_update_package_status(PDO $pdo, int $package_id, string $status, ?string $note = null, ?int $user_id = null): array
{
    $allowed = ['in_stock', 'shipped', 'void'];
    if (!in_array($status, $allowed, true)) {
        return ['success' => false, 'message' => '不支持的状态'];
    }

    try {
        $stmt = $pdo->prepare('SELECT status FROM mrs_package_ledger WHERE package_id = :id');
        $stmt->execute([':id' => $package_id]);
        $current = $stmt->fetchColumn();
        if (!$current) {
            return ['success' => false, 'message' => '记录不存在'];
        }
        if ($current === 'shipped' && $status !== 'shipped') {
            return ['success' => false, 'message' => '已出库记录不可修改'];
        }

        $now = date('Y-m-d H:i:s');
        $update = $pdo->prepare('UPDATE mrs_package_ledger SET status = :status, status_note = :note, updated_at = :updated_at, updated_by = :updated_by WHERE package_id = :id');
        $update->execute([
            ':status' => $status,
            ':note' => $note,
            ':updated_at' => $now,
            ':updated_by' => $user_id,
            ':id' => $package_id,
        ]);

        if ($status === 'shipped') {
            $outbound = $pdo->prepare('UPDATE mrs_package_ledger SET outbound_time = :outbound_time WHERE package_id = :id AND outbound_time IS NULL');
            $outbound->execute([':outbound_time' => $now, ':id' => $package_id]);
        }

        return ['success' => true, 'timestamp' => $now];
    } catch (PDOException $e) {
        mrs_log('状态更新失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '状态更新失败'];
    }
}

function mrs_get_monthly_flow(PDO $pdo, string $month): array
{
    $start = date('Y-m-01 00:00:00', strtotime($month . '-01'));
    $end = date('Y-m-01 00:00:00', strtotime($month . '-01 +1 month'));

    $stmtIn = $pdo->prepare('SELECT COUNT(*) FROM mrs_package_ledger WHERE inbound_time >= :start AND inbound_time < :end');
    $stmtIn->execute([':start' => $start, ':end' => $end]);
    $inbound = (int)$stmtIn->fetchColumn();

    $stmtOut = $pdo->prepare("SELECT COUNT(*) FROM mrs_package_ledger WHERE outbound_time >= :start AND outbound_time < :end AND status = 'shipped'");
    $stmtOut->execute([':start' => $start, ':end' => $end]);
    $outbound = (int)$stmtOut->fetchColumn();

    $stmtSku = $pdo->prepare("SELECT COUNT(DISTINCT sku_name) FROM mrs_package_ledger WHERE inbound_time >= :start AND inbound_time < :end");
    $stmtSku->execute([':start' => $start, ':end' => $end]);
    $sku_count = (int)$stmtSku->fetchColumn();

    return [
        'month' => $month,
        'inbound_count' => $inbound,
        'outbound_count' => $outbound,
        'sku_count' => $sku_count,
    ];
}

function mrs_get_inventory_summary(PDO $pdo): array
{
    $sql = "SELECT sku_name, COUNT(*) AS total, SUM(CASE WHEN status = 'in_stock' THEN 1 ELSE 0 END) AS in_stock, SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped, SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) AS void_count FROM mrs_package_ledger GROUP BY sku_name ORDER BY sku_name";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function mrs_get_dashboard_metrics(PDO $pdo): array
{
    $total = (int)$pdo->query('SELECT COUNT(*) FROM mrs_package_ledger')->fetchColumn();
    $in_stock = (int)$pdo->query("SELECT COUNT(*) FROM mrs_package_ledger WHERE status = 'in_stock'")->fetchColumn();
    $shipped = (int)$pdo->query("SELECT COUNT(*) FROM mrs_package_ledger WHERE status = 'shipped'")->fetchColumn();
    $void = (int)$pdo->query("SELECT COUNT(*) FROM mrs_package_ledger WHERE status = 'void'")->fetchColumn();

    $current_month = date('Y-m');
    $flow = mrs_get_monthly_flow($pdo, $current_month);

    return [
        'total' => $total,
        'in_stock' => $in_stock,
        'shipped' => $shipped,
        'void' => $void,
        'flow' => $flow,
    ];
}

function mrs_get_recent_activity(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->prepare('SELECT * FROM mrs_package_ledger ORDER BY (updated_at IS NULL) ASC, updated_at DESC, inbound_time DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mrs_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return date('Y-m-d H:i', strtotime($value));
}
