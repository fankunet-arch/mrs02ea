<?php
/**
 * MRS Package Ledger System - Core Library
 */

function mrs_authenticate_user($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, user_login, user_secret_hash, user_email, user_display_name, user_status FROM sys_users WHERE user_login = :username LIMIT 1");
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user || $user['user_status'] !== 'active') {
            mrs_log("登录失败: 账号不可用 - {$username}", 'WARNING');
            return false;
        }

        if (password_verify($password, $user['user_secret_hash'])) {
            $update = $pdo->prepare("UPDATE sys_users SET user_last_login_at = NOW(6) WHERE user_id = :user_id");
            $update->bindValue(':user_id', $user['user_id'], PDO::PARAM_INT);
            $update->execute();
            unset($user['user_secret_hash']);
            mrs_log("登录成功: {$username}", 'INFO');
            return $user;
        }

        mrs_log("登录失败: 密码错误 - {$username}", 'WARNING');
        return false;
    } catch (PDOException $e) {
        mrs_log('用户认证异常: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

function mrs_create_user_session($user) {
    mrs_start_secure_session();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_login'] = $user['user_login'];
    $_SESSION['user_display_name'] = $user['user_display_name'];
    $_SESSION['user_email'] = $user['user_email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

function mrs_is_user_logged_in() {
    mrs_start_secure_session();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        mrs_destroy_user_session();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function mrs_destroy_user_session() {
    mrs_start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function mrs_require_login() {
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

function mrs_save_inbound_range($pdo, $sku_name, $batch_code, $range_text, $spec_info = null, $operator = 'system') {
    $sku_name = trim($sku_name);
    $batch_code = trim($batch_code);
    $spec_info = trim($spec_info ?? '');
    $range_text = trim($range_text);
    if ($sku_name === '' || $batch_code === '' || $range_text === '') {
        throw new InvalidArgumentException('缺少必要字段');
    }

    $parts = explode('-', $range_text);
    $start = (int)($parts[0] ?? 0);
    $end = count($parts) === 2 ? (int)$parts[1] : $start;

    if ($start <= 0 || $end <= 0 || $end < $start) {
        throw new InvalidArgumentException('箱号范围格式不正确');
    }

    $pdo->beginTransaction();
    try {
        $inserted = 0;
        $checkStmt = $pdo->prepare("SELECT 1 FROM mrs_package_ledger WHERE sku_name = :sku_name AND batch_code = :batch_code AND box_number = :box_number LIMIT 1");
        $insertStmt = $pdo->prepare("INSERT INTO mrs_package_ledger (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_at, created_by) VALUES (:sku_name, :batch_code, :box_number, :spec_info, 'in_stock', NOW(6), NOW(6), :created_by)");

        for ($i = $start; $i <= $end; $i++) {
            $box_number = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $checkStmt->execute([
                'sku_name' => $sku_name,
                'batch_code' => $batch_code,
                'box_number' => $box_number,
            ]);
            if ($checkStmt->fetch()) {
                continue;
            }

            $insertStmt->execute([
                'sku_name' => $sku_name,
                'batch_code' => $batch_code,
                'box_number' => $box_number,
                'spec_info' => $spec_info,
                'created_by' => $operator,
            ]);
            $inserted++;
        }

        $pdo->commit();
        return $inserted;
    } catch (Throwable $e) {
        $pdo->rollBack();
        mrs_log('入库失败: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

function mrs_mark_outbound($pdo, $package_ids, $operator = 'system') {
    if (empty($package_ids)) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
    $sql = "UPDATE mrs_package_ledger SET status='shipped', outbound_time = NOW(6), outbound_by = ?, updated_at = NOW(6) WHERE status='in_stock' AND package_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$operator], $package_ids);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function mrs_mark_void($pdo, $package_ids, $reason = null, $operator = 'system') {
    if (empty($package_ids)) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
    $sql = "UPDATE mrs_package_ledger SET status='void', void_reason = ?, updated_at = NOW(6), void_by = ?, void_time = NOW(6) WHERE status != 'shipped' AND package_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$reason, $operator], $package_ids);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function mrs_fetch_inventory($pdo, $filters = []) {
    $sql = "SELECT package_id, sku_name, batch_code, box_number, spec_info, status, inbound_time, outbound_time FROM mrs_package_ledger WHERE 1=1";
    $params = [];

    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['sku_name'])) {
        $sql .= " AND sku_name LIKE :sku";
        $params['sku'] = '%' . $filters['sku_name'] . '%';
    }

    if (!empty($filters['batch_code'])) {
        $sql .= " AND batch_code = :batch";
        $params['batch'] = $filters['batch_code'];
    }

    $sql .= " ORDER BY inbound_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function mrs_fetch_inventory_summary($pdo) {
    $sql = "SELECT sku_name, status, COUNT(*) as count FROM mrs_package_ledger GROUP BY sku_name, status ORDER BY sku_name";
    $rows = $pdo->query($sql)->fetchAll();
    $summary = [];
    foreach ($rows as $row) {
        $sku = $row['sku_name'];
        if (!isset($summary[$sku])) {
            $summary[$sku] = ['in_stock' => 0, 'shipped' => 0, 'void' => 0];
        }
        $summary[$sku][$row['status']] = (int)$row['count'];
    }
    return $summary;
}

function mrs_fetch_monthly_flow($pdo, $month) {
    $stmtIn = $pdo->prepare("SELECT COUNT(*) as inbound_count, COUNT(DISTINCT sku_name) as inbound_skus FROM mrs_package_ledger WHERE DATE_FORMAT(inbound_time, '%Y-%m') = :month");
    $stmtOut = $pdo->prepare("SELECT COUNT(*) as outbound_count FROM mrs_package_ledger WHERE status='shipped' AND DATE_FORMAT(outbound_time, '%Y-%m') = :month");
    $stmtIn->execute(['month' => $month]);
    $stmtOut->execute(['month' => $month]);
    return [
        'inbound' => $stmtIn->fetch(),
        'outbound' => $stmtOut->fetch(),
    ];
}

function mrs_fetch_recent_packages($pdo, $limit = 20) {
    $stmt = $pdo->prepare("SELECT package_id, sku_name, batch_code, box_number, status, inbound_time, outbound_time FROM mrs_package_ledger ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
