<?php
/**
 * MRS Package Management System - Core Library
 * 文件路径: app/mrs/lib/mrs_lib.php
 * 说明: 核心业务逻辑函数
 */

// ============================================
// 认证相关函数 (共享用户数据库)
// ============================================

/**
 * 验证用户登录
 * @param PDO $pdo
 * @param string $username
 * @param string $password
 * @return array|false
 */
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

/**
 * 创建用户会话
 * @param array $user
 */
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

/**
 * 检查用户是否登录
 * @return bool
 */
function mrs_is_user_logged_in() {
    mrs_start_secure_session();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    $timeout = MRS_SESSION_TIMEOUT;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        mrs_destroy_user_session();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * 销毁会话
 */
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

/**
 * 登录保护
 */
function mrs_require_login() {
    if (!mrs_is_user_logged_in()) {
        header('Location: /mrs/ap/index.php?action=login');
        exit;
    }
}

// ============================================
// 物料(SKU)管理函数
// ============================================

/**
 * 获取所有物料列表
 * @param PDO $pdo
 * @return array
 */
function mrs_get_all_skus($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mrs_sku ORDER BY sku_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to get SKUs: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 根据名称搜索物料
 * @param PDO $pdo
 * @param string $keyword
 * @return array
 */
function mrs_search_sku($pdo, $keyword) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mrs_sku WHERE sku_name LIKE :keyword ORDER BY sku_name ASC");
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to search SKU: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 创建新物料
 * @param PDO $pdo
 * @param string $sku_name
 * @return int|false
 */
function mrs_create_sku($pdo, $sku_name) {
    try {
        $stmt = $pdo->prepare("INSERT INTO mrs_sku (sku_name) VALUES (:sku_name)");
        $stmt->execute(['sku_name' => trim($sku_name)]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        mrs_log('Failed to create SKU: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

// ============================================
// 包裹台账管理函数
// ============================================

/**
 * 创建入库记录 (批量)
 * @param PDO $pdo
 * @param string $sku_name 物料名称
 * @param string $batch_code 批次号
 * @param array $box_numbers 箱号数组 ['0001', '0002', ...]
 * @param string $spec_info 规格信息
 * @param string $operator 操作员
 * @return array ['success' => bool, 'created' => int, 'errors' => array]
 */
function mrs_inbound_packages($pdo, $sku_name, $batch_code, $box_numbers, $spec_info = '', $operator = '') {
    $created = 0;
    $errors = [];

    try {
        $pdo->beginTransaction();

        foreach ($box_numbers as $box_number) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO mrs_package_ledger
                    (sku_name, batch_code, box_number, spec_info, status, inbound_time, created_by)
                    VALUES (:sku_name, :batch_code, :box_number, :spec_info, 'in_stock', NOW(), :operator)
                ");

                $stmt->execute([
                    'sku_name' => trim($sku_name),
                    'batch_code' => trim($batch_code),
                    'box_number' => trim($box_number),
                    'spec_info' => trim($spec_info),
                    'operator' => $operator
                ]);

                $created++;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = "箱号 {$box_number} 已存在";
                } else {
                    $errors[] = "箱号 {$box_number} 创建失败: " . $e->getMessage();
                }
            }
        }

        $pdo->commit();

        mrs_log("Inbound completed: created=$created, errors=" . count($errors), 'INFO');

        return [
            'success' => true,
            'created' => $created,
            'errors' => $errors
        ];

    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('Failed to inbound packages: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => '入库失败: ' . $e->getMessage()
        ];
    }
}

/**
 * 获取可用库存 (按物料分组)
 * @param PDO $pdo
 * @param string $sku_name 可选,筛选特定物料
 * @return array
 */
function mrs_get_inventory_summary($pdo, $sku_name = '') {
    try {
        $sql = "
            SELECT
                sku_name,
                COUNT(*) as total_boxes
            FROM mrs_package_ledger
            WHERE status = 'in_stock'
        ";

        if (!empty($sku_name)) {
            $sql .= " AND sku_name = :sku_name";
        }

        $sql .= " GROUP BY sku_name ORDER BY sku_name ASC";

        $stmt = $pdo->prepare($sql);

        if (!empty($sku_name)) {
            $stmt->bindValue(':sku_name', $sku_name, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to get inventory summary: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 获取库存明细 (某个物料的所有在库包裹)
 * @param PDO $pdo
 * @param string $sku_name
 * @param string $order_by 排序方式: 'fifo' (先进先出), 'batch' (按批次)
 * @return array
 */
function mrs_get_inventory_detail($pdo, $sku_name, $order_by = 'fifo') {
    try {
        $sql = "
            SELECT *,
                   DATEDIFF(NOW(), inbound_time) as days_in_stock
            FROM mrs_package_ledger
            WHERE sku_name = :sku_name AND status = 'in_stock'
        ";

        if ($order_by === 'fifo') {
            $sql .= " ORDER BY inbound_time ASC, batch_code ASC, box_number ASC";
        } else {
            $sql .= " ORDER BY batch_code ASC, box_number ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':sku_name', $sku_name, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to get inventory detail: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 出库操作 (批量)
 * @param PDO $pdo
 * @param array $package_ids 要出库的包裹ID数组
 * @param string $operator 操作员
 * @return array ['success' => bool, 'shipped' => int, 'message' => string]
 */
function mrs_outbound_packages($pdo, $package_ids, $operator = '') {
    try {
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($package_ids), '?'));

        $stmt = $pdo->prepare("
            UPDATE mrs_package_ledger
            SET status = 'shipped',
                outbound_time = NOW(),
                updated_by = ?
            WHERE package_id IN ($placeholders)
              AND status = 'in_stock'
        ");

        $params = array_merge([$operator], $package_ids);
        $stmt->execute($params);

        $shipped = $stmt->rowCount();

        $pdo->commit();

        mrs_log("Outbound completed: shipped=$shipped", 'INFO', ['operator' => $operator]);

        return [
            'success' => true,
            'shipped' => $shipped,
            'message' => "成功出库 {$shipped} 个包裹"
        ];

    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('Failed to outbound packages: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'shipped' => 0,
            'message' => '出库失败: ' . $e->getMessage()
        ];
    }
}

/**
 * 状态变更 (损耗/作废)
 * @param PDO $pdo
 * @param int $package_id
 * @param string $new_status 'void' (损耗)
 * @param string $reason 原因
 * @param string $operator
 * @return array
 */
function mrs_change_status($pdo, $package_id, $new_status, $reason = '', $operator = '') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE mrs_package_ledger
            SET status = :new_status,
                void_reason = :reason,
                updated_by = :operator,
                outbound_time = NOW()
            WHERE package_id = :package_id
        ");

        $stmt->execute([
            'new_status' => $new_status,
            'reason' => $reason,
            'operator' => $operator,
            'package_id' => $package_id
        ]);

        $pdo->commit();

        mrs_log("Status changed: package_id=$package_id, new_status=$new_status", 'INFO');

        return [
            'success' => true,
            'message' => '状态已更新'
        ];

    } catch (PDOException $e) {
        $pdo->rollBack();
        mrs_log('Failed to change status: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => '状态更新失败: ' . $e->getMessage()
        ];
    }
}

// ============================================
// 统计报表函数
// ============================================

/**
 * 月度入库统计
 * @param PDO $pdo
 * @param string $month 格式: '2025-11'
 * @return array
 */
function mrs_get_monthly_inbound($pdo, $month) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                sku_name,
                COUNT(*) as package_count,
                COUNT(DISTINCT batch_code) as batch_count
            FROM mrs_package_ledger
            WHERE DATE_FORMAT(inbound_time, '%Y-%m') = :month
            GROUP BY sku_name
            ORDER BY package_count DESC
        ");

        $stmt->bindValue(':month', $month, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to get monthly inbound: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 月度出库统计
 * @param PDO $pdo
 * @param string $month 格式: '2025-11'
 * @return array
 */
function mrs_get_monthly_outbound($pdo, $month) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                sku_name,
                COUNT(*) as package_count
            FROM mrs_package_ledger
            WHERE DATE_FORMAT(outbound_time, '%Y-%m') = :month
              AND status = 'shipped'
            GROUP BY sku_name
            ORDER BY package_count DESC
        ");

        $stmt->bindValue(':month', $month, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to get monthly outbound: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 月度汇总统计
 * @param PDO $pdo
 * @param string $month
 * @return array
 */
function mrs_get_monthly_summary($pdo, $month) {
    try {
        // 入库总数
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM mrs_package_ledger
            WHERE DATE_FORMAT(inbound_time, '%Y-%m') = :month
        ");
        $stmt->execute(['month' => $month]);
        $inbound_total = $stmt->fetch()['total'] ?? 0;

        // 出库总数
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM mrs_package_ledger
            WHERE DATE_FORMAT(outbound_time, '%Y-%m') = :month
              AND status = 'shipped'
        ");
        $stmt->execute(['month' => $month]);
        $outbound_total = $stmt->fetch()['total'] ?? 0;

        return [
            'month' => $month,
            'inbound_total' => $inbound_total,
            'outbound_total' => $outbound_total
        ];
    } catch (PDOException $e) {
        mrs_log('Failed to get monthly summary: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 获取包裹详情
 * @param PDO $pdo
 * @param int $package_id
 * @return array|null
 */
function mrs_get_package_by_id($pdo, $package_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mrs_package_ledger WHERE package_id = :package_id");
        $stmt->execute(['package_id' => $package_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        mrs_log('Failed to get package: ' . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * 搜索包裹
 * @param PDO $pdo
 * @param string $sku_name
 * @param string $batch_code
 * @param string $box_number
 * @return array
 */
function mrs_search_packages($pdo, $sku_name = '', $batch_code = '', $box_number = '') {
    try {
        $sql = "SELECT * FROM mrs_package_ledger WHERE 1=1";
        $params = [];

        if (!empty($sku_name)) {
            $sql .= " AND sku_name = :sku_name";
            $params['sku_name'] = $sku_name;
        }

        if (!empty($batch_code)) {
            $sql .= " AND batch_code LIKE :batch_code";
            $params['batch_code'] = '%' . $batch_code . '%';
        }

        if (!empty($box_number)) {
            $sql .= " AND box_number LIKE :box_number";
            $params['box_number'] = '%' . $box_number . '%';
        }

        $sql .= " ORDER BY inbound_time DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        mrs_log('Failed to search packages: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}
