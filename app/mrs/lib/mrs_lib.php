<?php
/**
 * MRS Package Ledger System - Core Library
 * 文件路径: app/mrs/lib/mrs_lib.php
 * 说明: 独立的业务逻辑与鉴权实现
 */

// ============================================
// 认证相关函数
// ============================================
function mrs_authenticate_user($pdo, $username, $password)
{
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

function mrs_create_user_session($user)
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

function mrs_is_user_logged_in()
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

function mrs_destroy_user_session()
{
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

function mrs_require_login()
{
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

// ============================================
// 包裹台账逻辑
// ============================================
function mrs_create_inbound_packages(PDO $pdo, $sku_name, $batch_code, $start_no, $end_no, $spec_info = null, $created_by = null)
{
    $start = (int)$start_no;
    $end = (int)$end_no;

    if ($start < 1 || $end < $start) {
        throw new InvalidArgumentException('箱号范围不合法');
    }

    $created_at = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $inbound_time = $created_at;

    $pdo->beginTransaction();

    try {
        $inserted = 0;
        $skipped = [];

        $check_stmt = $pdo->prepare("SELECT package_id FROM mrs_package_ledger WHERE sku_name = :sku AND batch_code = :batch AND box_number = :box LIMIT 1");
        $insert_stmt = $pdo->prepare("INSERT INTO mrs_package_ledger (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_at, created_by) VALUES (:sku, :batch, :box, :spec, 'in_stock', :inbound_time, :created_at, :created_by)");

        for ($i = $start; $i <= $end; $i++) {
            $box_number = str_pad((string)$i, 4, '0', STR_PAD_LEFT);

            $check_stmt->execute([
                'sku' => $sku_name,
                'batch' => $batch_code,
                'box' => $box_number,
            ]);

            if ($check_stmt->fetch()) {
                $skipped[] = $box_number;
                continue;
            }

            $insert_stmt->execute([
                'sku' => $sku_name,
                'batch' => $batch_code,
                'box' => $box_number,
                'spec' => $spec_info,
                'inbound_time' => $inbound_time,
                'created_at' => $created_at,
                'created_by' => $created_by,
            ]);

            $inserted++;
        }

        $pdo->commit();

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        mrs_log('入库失败: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

function mrs_get_packages(PDO $pdo, array $filters = [])
{
    $sql = "SELECT package_id, sku_name, batch_code, box_number, spec_info, status, inbound_time, outbound_time, created_at FROM mrs_package_ledger";
    $conditions = [];
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $conditions[] = 'status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['sku_name'])) {
        $conditions[] = 'sku_name LIKE :sku';
        $params['sku'] = '%' . $filters['sku_name'] . '%';
    }

    if (!empty($filters['batch_code'])) {
        $conditions[] = 'batch_code = :batch';
        $params['batch'] = $filters['batch_code'];
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY inbound_time DESC, package_id DESC LIMIT 200';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('查询包裹失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

function mrs_update_package_status(PDO $pdo, $package_id, $new_status, $updated_by = null)
{
    $allowed = ['in_stock', 'shipped', 'void'];
    if (!in_array($new_status, $allowed, true)) {
        throw new InvalidArgumentException('非法状态值');
    }

    try {
        $stmt = $pdo->prepare('SELECT status, outbound_time FROM mrs_package_ledger WHERE package_id = :id');
        $stmt->execute(['id' => $package_id]);
        $package = $stmt->fetch();

        if (!$package) {
            throw new RuntimeException('包裹不存在');
        }

        if ($package['status'] === 'shipped' && $new_status !== 'shipped') {
            throw new RuntimeException('已出库记录不可回退');
        }

        if ($package['status'] === 'void') {
            throw new RuntimeException('作废记录不可变更');
        }

        $fields = ['status' => $new_status, 'updated_by' => $updated_by];

        if ($new_status === 'shipped' && empty($package['outbound_time'])) {
            $fields['outbound_time'] = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        $set_clauses = [];
        foreach ($fields as $key => $value) {
            $set_clauses[] = "$key = :$key";
        }

        $sql = 'UPDATE mrs_package_ledger SET ' . implode(', ', $set_clauses) . ' WHERE package_id = :id';
        $stmt = $pdo->prepare($sql);
        $fields['id'] = $package_id;
        $stmt->execute($fields);

        return true;
    } catch (PDOException $e) {
        mrs_log('更新状态失败: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

function mrs_get_monthly_flow(PDO $pdo, $month)
{
    $start = DateTimeImmutable::createFromFormat('Y-m', $month)->setDate((int)substr($month, 0, 4), (int)substr($month, 5, 2), 1);
    $end = $start->modify('first day of next month');

    $result = [
        'inbound' => 0,
        'outbound' => 0,
    ];

    $inbound_stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM mrs_package_ledger WHERE inbound_time >= :start AND inbound_time < :end');
    $inbound_stmt->execute(['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')]);
    $result['inbound'] = (int)$inbound_stmt->fetchColumn();

    $outbound_stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM mrs_package_ledger WHERE outbound_time >= :start AND outbound_time < :end AND status = 'shipped'");
    $outbound_stmt->execute(['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')]);
    $result['outbound'] = (int)$outbound_stmt->fetchColumn();

    return $result;
}

function mrs_get_inventory_snapshot(PDO $pdo)
{
    $summary_sql = "SELECT sku_name, COUNT(*) AS total FROM mrs_package_ledger WHERE status = 'in_stock' GROUP BY sku_name ORDER BY sku_name";
    $detail_sql = "SELECT sku_name, batch_code, box_number, spec_info, inbound_time FROM mrs_package_ledger WHERE status = 'in_stock' ORDER BY sku_name, inbound_time";

    try {
        $summary = $pdo->query($summary_sql)->fetchAll();
        $details = $pdo->query($detail_sql)->fetchAll();

        $grouped = [];
        foreach ($details as $row) {
            $grouped[$row['sku_name']][] = $row;
        }

        return [
            'summary' => $summary,
            'details' => $grouped,
        ];
    } catch (PDOException $e) {
        mrs_log('获取库存快照失败: ' . $e->getMessage(), 'ERROR');
        return ['summary' => [], 'details' => []];
    }
}
