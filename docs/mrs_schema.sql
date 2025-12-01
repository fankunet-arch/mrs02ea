-- MRS 系统数据库创建脚本 (MySQL)
-- 与 Express 共享 sys_users 用户表，本脚本仅创建 MRS 自有表。

CREATE TABLE IF NOT EXISTS `mrs_package_ledger` (
  `package_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_name` VARCHAR(255) NOT NULL,
  `batch_code` VARCHAR(64) NOT NULL,
  `box_number` VARCHAR(64) NOT NULL,
  `spec_info` VARCHAR(255) NULL,
  `status` ENUM('in_stock','shipped','void') NOT NULL DEFAULT 'in_stock',
  `inbound_time` DATETIME(6) NOT NULL,
  `outbound_time` DATETIME(6) NULL,
  `void_time` DATETIME(6) NULL,
  `void_reason` VARCHAR(255) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NULL,
  `created_by` VARCHAR(64) NULL,
  `outbound_by` VARCHAR(64) NULL,
  `void_by` VARCHAR(64) NULL,
  PRIMARY KEY (`package_id`),
  UNIQUE KEY `uk_sku_batch_box` (`sku_name`, `batch_code`, `box_number`),
  KEY `idx_status` (`status`),
  KEY `idx_inbound_time` (`inbound_time`),
  KEY `idx_outbound_time` (`outbound_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mrs_sku` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sku_name` (`sku_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
