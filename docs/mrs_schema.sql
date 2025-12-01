-- MRS 物料收发管理系统 - 数据库初始化脚本
-- 说明：遵循 docs/mrs需求说明.md 中的“包裹台账”设计

CREATE TABLE IF NOT EXISTS mrs_package_ledger (
    package_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku_name        VARCHAR(120) NOT NULL COMMENT '货：物料名称',
    batch_code      VARCHAR(60) NOT NULL COMMENT '批：批次号',
    box_number      VARCHAR(32) NOT NULL COMMENT '号：箱号（外部赋码）',
    spec_info       VARCHAR(120) NULL COMMENT '规格备注，仅展示用',
    status          ENUM('in_stock','shipped','void') NOT NULL DEFAULT 'in_stock' COMMENT '库存状态',
    inbound_time    DATETIME(6) NOT NULL COMMENT '入库时间',
    outbound_time   DATETIME(6) NULL COMMENT '出库时间',
    void_reason     VARCHAR(255) NULL COMMENT '作废/损耗原因',
    created_at      DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at      DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE KEY idx_sku_batch_box (sku_name, batch_code, box_number),
    KEY idx_status (status),
    KEY idx_inbound_time (inbound_time),
    KEY idx_outbound_time (outbound_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MRS 包裹台账表';

CREATE TABLE IF NOT EXISTS mrs_sku (
    sku_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku_name    VARCHAR(120) NOT NULL UNIQUE COMMENT '物料名称',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MRS 常用物料表（用于输入联想）';
