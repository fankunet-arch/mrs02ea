-- MRS 系统数据库初始化脚本
-- 路径：docs/mrs_schema.sql
-- 说明：独立的 MRS 表结构，登录继续复用 sys_users（与 Express 共用）。

CREATE TABLE IF NOT EXISTS mrs_sku (
    sku_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku_name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (sku_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mrs_package_ledger (
    package_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku_name VARCHAR(255) NOT NULL,
    batch_code VARCHAR(100) NOT NULL,
    box_number VARCHAR(50) NOT NULL,
    spec_info VARCHAR(255) NULL,
    status ENUM('in_stock', 'shipped', 'void') NOT NULL DEFAULT 'in_stock',
    inbound_time DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    outbound_time DATETIME(6) NULL,
    void_reason VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NULL,
    created_by VARCHAR(100) NULL,
    updated_by VARCHAR(100) NULL,
    PRIMARY KEY (package_id),
    UNIQUE KEY idx_sku_batch_box (sku_name, batch_code, box_number),
    KEY idx_status (status),
    KEY idx_inbound_time (inbound_time),
    KEY idx_outbound_time (outbound_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 提醒：用户登录继续使用现有的 sys_users 表，无需重复创建。
