<?php
/**
 * MRS System - Core Library
 * Path: app/mrs/lib/mrs_lib.php
 * Purpose: Shared authentication and ledger utilities for MRS.
 */

// =====================
// Authentication helpers
// =====================

function mrs_authenticate_user($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, user_login, user_secret_hash, user_email, user_display_name, user_status FROM sys_users WHERE user_login = :username LIMIT 1");
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
        mrs_log('用户认证失败: ' . $e->getMessage(), 'ERROR');
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
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function mrs_require_login() {
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

// =====================
// Ledger helpers
// =====================

function mrs_parse_box_range($range) {
    $range = trim($range);
    if (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
        $start = (int)$matches[1];
        $end = (int)$matches[2];
        if ($start <= 0 || $end < $start) {
            return [];
        }
        $width = max(strlen($matches[1]), strlen($matches[2]), 4);
        $numbers = [];
        for ($i = $start; $i <= $end; $i++) {
            $numbers[] = str_pad((string)$i, $width, '0', STR_PAD_LEFT);
        }
        return $numbers;
    }

    if (preg_match('/^\d+$/', $range)) {
        $width = max(strlen($range), 4);
        return [str_pad($range, $width, '0', STR_PAD_LEFT)];
    }

    return [];
}

function mrs_upsert_sku($pdo, $sku_name) {
    $stmt = $pdo->prepare("INSERT INTO mrs_sku (sku_name) VALUES (:sku_name) ON DUPLICATE KEY UPDATE updated_at = NOW(6)");
    $stmt->execute(['sku_name' => trim($sku_name)]);
}

function mrs_bulk_create_packages($pdo, $sku_name, $batch_code, $box_range, $spec_info, $created_by = null) {
    $boxes = mrs_parse_box_range($box_range);
    if (empty($boxes)) {
        return ['success' => false, 'message' => '箱号范围格式错误，请使用形如 1-5 或 7'];
    }

    try {
        $pdo->beginTransaction();

        mrs_upsert_sku($pdo, $sku_name);

        $stmt = $pdo->prepare("INSERT INTO mrs_package_ledger (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_at, created_by)
            VALUES (:sku_name, :batch_code, :box_number, :spec_info, 'in_stock', NOW(6), NOW(6), :created_by)");

        foreach ($boxes as $box) {
            $stmt->execute([
                'sku_name' => trim($sku_name),
                'batch_code' => trim($batch_code),
                'box_number' => $box,
                'spec_info' => trim($spec_info),
                'created_by' => $created_by
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'count' => count($boxes)];
    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('批量入库失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '写入数据库时发生错误'];
    }
}

function mrs_get_available_packages($pdo, $sku_name = null, $limit = 200) {
    $sql = "SELECT * FROM mrs_package_ledger WHERE status = 'in_stock'";
    $params = [];

    if ($sku_name) {
        $sql .= " AND sku_name = :sku_name";
        $params['sku_name'] = $sku_name;
    }

    $sql .= " ORDER BY inbound_time ASC, package_id ASC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    if ($sku_name) {
        $stmt->bindValue(':sku_name', $sku_name, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function mrs_ship_packages($pdo, $package_ids, $shipped_by = null) {
    if (empty($package_ids)) {
        return ['success' => false, 'message' => '请至少选择一个包裹'];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
        $sql = "UPDATE mrs_package_ledger SET status = 'shipped', outbound_time = NOW(6), updated_at = NOW(6), updated_by = ? WHERE package_id IN ($placeholders) AND status = 'in_stock'";
        $stmt = $pdo->prepare($sql);
        $values = array_merge([$shipped_by], $package_ids);
        $stmt->execute($values);

        return ['success' => true, 'affected' => $stmt->rowCount()];
    } catch (PDOException $e) {
        mrs_log('出库失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '数据库更新失败'];
    }
}

function mrs_void_package($pdo, $package_id, $void_reason = null, $operator = null) {
    try {
        $stmt = $pdo->prepare("UPDATE mrs_package_ledger SET status = 'void', void_reason = :reason, updated_at = NOW(6), updated_by = :operator WHERE package_id = :package_id");
        $stmt->execute([
            'reason' => $void_reason,
            'operator' => $operator,
            'package_id' => $package_id
        ]);

        return ['success' => $stmt->rowCount() > 0];
    } catch (PDOException $e) {
        mrs_log('标记损耗失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '数据库更新失败'];
    }
}

function mrs_get_inventory_snapshot($pdo) {
    $summary_sql = "SELECT sku_name, COUNT(*) as total FROM mrs_package_ledger WHERE status = 'in_stock' GROUP BY sku_name ORDER BY sku_name";
    $summary_stmt = $pdo->query($summary_sql);
    $summary = $summary_stmt->fetchAll();

    $detail_sql = "SELECT sku_name, batch_code, box_number, spec_info, inbound_time, status FROM mrs_package_ledger WHERE status = 'in_stock' ORDER BY sku_name, inbound_time ASC";
    $detail_stmt = $pdo->query($detail_sql);
    $detail = $detail_stmt->fetchAll();

    return ['summary' => $summary, 'detail' => $detail];
}

function mrs_get_monthly_stats($pdo, $month) {
    $start = $month . '-01 00:00:00';
    $end = date('Y-m-d H:i:s', strtotime($start . ' +1 month'));

    $inbound_stmt = $pdo->prepare("SELECT COUNT(*) AS inbound_count, COUNT(DISTINCT sku_name) AS sku_count FROM mrs_package_ledger WHERE inbound_time >= :start AND inbound_time < :end");
    $inbound_stmt->execute(['start' => $start, 'end' => $end]);
    $inbound = $inbound_stmt->fetch();

    $outbound_stmt = $pdo->prepare("SELECT COUNT(*) AS outbound_count FROM mrs_package_ledger WHERE outbound_time >= :start AND outbound_time < :end AND status = 'shipped'");
    $outbound_stmt->execute(['start' => $start, 'end' => $end]);
    $outbound = $outbound_stmt->fetch();

    return [
        'inbound_count' => $inbound['inbound_count'] ?? 0,
        'sku_count' => $inbound['sku_count'] ?? 0,
        'outbound_count' => $outbound['outbound_count'] ?? 0,
    ];
}

function mrs_get_recent_operations($pdo, $limit = 20) {
    $sql = "SELECT package_id, sku_name, batch_code, box_number, status, inbound_time, outbound_time, updated_at FROM mrs_package_ledger ORDER BY COALESCE(updated_at, inbound_time) DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mrs_fetch_skus($pdo) {
    $stmt = $pdo->query("SELECT sku_name FROM mrs_sku ORDER BY updated_at DESC, sku_name ASC");
    return $stmt->fetchAll();
}
