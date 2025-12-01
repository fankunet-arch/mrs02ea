<?php
/**
 * MRS System - 核心库
 */

// ============================================
// 会话与认证
// ============================================

function mrs_start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

function mrs_authenticate_user(PDO $pdo, string $username, string $password) {
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

function mrs_create_user_session(array $user): void {
    mrs_start_secure_session();
    $_SESSION['mrs_user'] = [
        'user_id' => $user['user_id'],
        'user_login' => $user['user_login'],
        'user_display_name' => $user['user_display_name'],
        'user_email' => $user['user_email'],
    ];
    $_SESSION['mrs_logged_in'] = true;
    $_SESSION['mrs_login_time'] = time();
    $_SESSION['mrs_last_activity'] = time();
}

function mrs_is_user_logged_in(): bool {
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

function mrs_destroy_user_session(): void {
    mrs_start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function mrs_require_login(): void {
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

function mrs_current_user_display(): string {
    return $_SESSION['mrs_user']['user_display_name'] ?? ($_SESSION['mrs_user']['user_login'] ?? '');
}

// ============================================
// 视图渲染
// ============================================

function mrs_render(string $view, array $data = []): void {
    extract($data);
    $view_file = MRS_VIEW_PATH . '/' . $view . '.php';
    if (!file_exists($view_file)) {
        http_response_code(404);
        echo 'View not found';
        return;
    }
    include $view_file;
}

// ============================================
// 业务函数：包裹台账
// ============================================

function mrs_parse_box_range(string $range_input): array {
    $range_input = trim($range_input);
    if ($range_input === '') {
        return [];
    }

    $segments = preg_split('/[,\n]+/', $range_input);
    $boxes = [];
    foreach ($segments as $segment) {
        $segment = trim($segment);
        if ($segment === '') {
            continue;
        }

        if (strpos($segment, '-') !== false) {
            [$start, $end] = array_map('trim', explode('-', $segment, 2));
            if (ctype_digit($start) && ctype_digit($end)) {
                $start_num = (int)$start;
                $end_num = (int)$end;
                if ($start_num > 0 && $end_num >= $start_num) {
                    for ($i = $start_num; $i <= $end_num; $i++) {
                        $boxes[] = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
                    }
                }
            }
        } elseif (ctype_digit($segment)) {
            $boxes[] = str_pad($segment, 4, '0', STR_PAD_LEFT);
        }
    }

    return array_values(array_unique($boxes));
}

function mrs_bulk_create_ledger_entries(PDO $pdo, string $sku_name, string $batch_code, string $box_range, ?string $spec_info = null): array {
    $boxes = mrs_parse_box_range($box_range);
    if (empty($boxes)) {
        return ['created' => 0, 'skipped' => 0, 'errors' => ['未能识别任何箱号，请检查输入格式。']];
    }

    $created = 0;
    $skipped = 0;
    $errors = [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO mrs_package_ledger (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_at, updated_at) VALUES (:sku_name, :batch_code, :box_number, :spec_info, 'in_stock', NOW(6), NOW(6), NOW(6))");
        foreach ($boxes as $box_number) {
            try {
                $stmt->execute([
                    ':sku_name' => $sku_name,
                    ':batch_code' => $batch_code,
                    ':box_number' => $box_number,
                    ':spec_info' => $spec_info,
                ]);
                $created++;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $skipped++;
                    continue;
                }
                throw $e;
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = '批量入库失败: ' . $e->getMessage();
        mrs_log('批量入库失败', 'ERROR', ['error' => $e->getMessage()]);
    }

    return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
}

function mrs_get_recent_inbound(PDO $pdo, int $limit = 20): array {
    try {
        $stmt = $pdo->prepare("SELECT package_id, sku_name, batch_code, box_number, spec_info, inbound_time, status FROM mrs_package_ledger ORDER BY inbound_time DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('查询入库记录失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

function mrs_get_inventory_snapshot(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT sku_name, batch_code, status, COUNT(*) AS qty FROM mrs_package_ledger GROUP BY sku_name, batch_code, status ORDER BY sku_name, batch_code");
        $records = $stmt->fetchAll();
        $summary = [];
        foreach ($records as $row) {
            $summary[$row['sku_name']]['batches'][$row['batch_code']][$row['status']] = (int)$row['qty'];
            $summary[$row['sku_name']]['total'] = ($summary[$row['sku_name']]['total'] ?? 0) + (int)$row['qty'];
        }
        return $summary;
    } catch (PDOException $e) {
        mrs_log('查询库存快照失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

function mrs_get_available_packages(PDO $pdo, ?string $sku_name = null, ?string $batch_code = null): array {
    try {
        $sql = "SELECT package_id, sku_name, batch_code, box_number, spec_info, inbound_time FROM mrs_package_ledger WHERE status = 'in_stock'";
        $params = [];
        if ($sku_name) {
            $sql .= " AND sku_name = :sku_name";
            $params[':sku_name'] = $sku_name;
        }
        if ($batch_code) {
            $sql .= " AND batch_code = :batch_code";
            $params[':batch_code'] = $batch_code;
        }
        $sql .= " ORDER BY inbound_time ASC";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('查询可用库存失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

function mrs_mark_shipped(PDO $pdo, array $package_ids): array {
    if (empty($package_ids)) {
        return ['updated' => 0, 'errors' => ['未选择任何包裹']];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
        $stmt = $pdo->prepare("UPDATE mrs_package_ledger SET status = 'shipped', outbound_time = NOW(6), updated_at = NOW(6) WHERE package_id IN ($placeholders) AND status = 'in_stock'");
        $stmt->execute($package_ids);
        return ['updated' => $stmt->rowCount(), 'errors' => []];
    } catch (PDOException $e) {
        mrs_log('出库失败: ' . $e->getMessage(), 'ERROR');
        return ['updated' => 0, 'errors' => ['出库失败: ' . $e->getMessage()]];
    }
}

function mrs_mark_void(PDO $pdo, array $package_ids, ?string $reason = null): array {
    if (empty($package_ids)) {
        return ['updated' => 0, 'errors' => ['未选择任何包裹']];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));
        $stmt = $pdo->prepare("UPDATE mrs_package_ledger SET status = 'void', updated_at = NOW(6), void_reason = :reason WHERE package_id IN ($placeholders) AND status != 'void'");
        $stmt->bindValue(':reason', $reason);
        foreach ($package_ids as $index => $id) {
            $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return ['updated' => $stmt->rowCount(), 'errors' => []];
    } catch (PDOException $e) {
        mrs_log('作废失败: ' . $e->getMessage(), 'ERROR');
        return ['updated' => 0, 'errors' => ['作废失败: ' . $e->getMessage()]];
    }
}

function mrs_get_monthly_flow(PDO $pdo, string $year_month): array {
    try {
        $stmt_in = $pdo->prepare("SELECT COUNT(*) AS cnt FROM mrs_package_ledger WHERE DATE_FORMAT(inbound_time, '%Y-%m') = :ym");
        $stmt_out = $pdo->prepare("SELECT COUNT(*) AS cnt FROM mrs_package_ledger WHERE DATE_FORMAT(outbound_time, '%Y-%m') = :ym AND status = 'shipped'");
        $stmt_in->execute([':ym' => $year_month]);
        $stmt_out->execute([':ym' => $year_month]);
        return [
            'inbound' => (int)$stmt_in->fetchColumn(),
            'outbound' => (int)$stmt_out->fetchColumn(),
        ];
    } catch (PDOException $e) {
        mrs_log('月度统计查询失败: ' . $e->getMessage(), 'ERROR');
        return ['inbound' => 0, 'outbound' => 0];
    }
}

function mrs_get_dashboard_counts(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT status, COUNT(*) AS qty FROM mrs_package_ledger GROUP BY status");
        $rows = $stmt->fetchAll();
        $result = ['in_stock' => 0, 'shipped' => 0, 'void' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['qty'];
        }
        return $result;
    } catch (PDOException $e) {
        mrs_log('统计查询失败: ' . $e->getMessage(), 'ERROR');
        return ['in_stock' => 0, 'shipped' => 0, 'void' => 0];
    }
}
