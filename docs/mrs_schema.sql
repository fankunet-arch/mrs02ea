-- MRS 物料收发管理系统数据库脚本
-- 独立于 Express 业务表，仅复用 sys_users 作为登录表

CREATE TABLE IF NOT EXISTS `mrs_package_ledger` (
  `package_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_name` VARCHAR(120) NOT NULL,
  `batch_code` VARCHAR(60) NOT NULL,
  `box_number` VARCHAR(60) NOT NULL,
  `spec_info` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('in_stock','shipped','void') NOT NULL DEFAULT 'in_stock',
  `status_note` VARCHAR(255) NULL DEFAULT NULL,
  `inbound_time` DATETIME NOT NULL,
  `outbound_time` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT NULL DEFAULT NULL,
  `updated_by` BIGINT NULL DEFAULT NULL,
  PRIMARY KEY (`package_id`),
  UNIQUE KEY `uniq_sku_batch_box` (`sku_name`,`batch_code`,`box_number`),
  KEY `idx_status` (`status`),
  KEY `idx_inbound_time` (`inbound_time`),
  KEY `idx_outbound_time` (`outbound_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mrs_sku` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_name` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sku_name` (`sku_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 权限/账户使用既有 sys_users，无需重复创建
