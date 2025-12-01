-- MRS 物料收发系统数据库初始化脚本
-- 独立于 Express 代码，复用同一数据库实例与 sys_users 用户表

CREATE TABLE IF NOT EXISTS mrs_package_ledger (
    package_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '唯一ID',
    sku_name VARCHAR(255) NOT NULL COMMENT '物料名称',
    batch_code VARCHAR(100) NOT NULL COMMENT '批次号',
    box_number VARCHAR(50) NOT NULL COMMENT '箱号 (如 0001)',
    spec_info VARCHAR(255) DEFAULT NULL COMMENT '规格备注',
    status ENUM('in_stock','shipped','void') NOT NULL DEFAULT 'in_stock' COMMENT '在库/已出/损耗',
    inbound_time DATETIME DEFAULT NULL COMMENT '入库时间',
    outbound_time DATETIME DEFAULT NULL COMMENT '出库时间',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    created_by VARCHAR(100) DEFAULT NULL COMMENT '创建人',
    updated_by VARCHAR(100) DEFAULT NULL COMMENT '最后操作人'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='包裹台账';

CREATE TABLE IF NOT EXISTS mrs_sku (
    sku_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku_name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物料常用清单';

CREATE INDEX idx_mrs_status ON mrs_package_ledger(status);
CREATE INDEX idx_mrs_inbound_time ON mrs_package_ledger(inbound_time);
CREATE INDEX idx_mrs_outbound_time ON mrs_package_ledger(outbound_time);
CREATE UNIQUE INDEX idx_mrs_sku_batch_box ON mrs_package_ledger(sku_name, batch_code, box_number);
