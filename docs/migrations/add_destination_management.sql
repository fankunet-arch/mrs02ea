-- =============================================
-- 去向管理功能数据库迁移
-- 日期: 2025-12-04
-- 说明: 添加去向管理功能，支持退回、仓库调仓、发往门店等场景
-- =============================================

USE mrs_system;

-- =============================================
-- 1. 去向类型配置表
-- =============================================
CREATE TABLE IF NOT EXISTS mrs_destination_types (
    type_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '类型ID',
    type_code VARCHAR(20) NOT NULL COMMENT '类型代码 (return, warehouse, store)',
    type_name VARCHAR(50) NOT NULL COMMENT '类型名称 (退回、仓库调仓、发往门店)',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT '是否启用',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',

    PRIMARY KEY (type_id),
    UNIQUE KEY uk_type_code (type_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='去向类型配置表';

-- 插入初始类型
INSERT INTO mrs_destination_types (type_code, type_name, sort_order) VALUES
    ('return', '退回', 1),
    ('warehouse', '仓库调仓', 2),
    ('store', '发往门店', 3),
    ('other', '其他', 99)
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

-- =============================================
-- 2. 去向管理表
-- =============================================
CREATE TABLE IF NOT EXISTS mrs_destinations (
    destination_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '去向ID',
    type_code VARCHAR(20) NOT NULL COMMENT '去向类型代码',
    destination_name VARCHAR(100) NOT NULL COMMENT '去向名称',
    destination_code VARCHAR(50) DEFAULT NULL COMMENT '去向编码（可选）',
    contact_person VARCHAR(50) DEFAULT NULL COMMENT '联系人',
    contact_phone VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
    address TEXT DEFAULT NULL COMMENT '地址',
    remark TEXT DEFAULT NULL COMMENT '备注',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否有效',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_by VARCHAR(60) DEFAULT NULL COMMENT '创建人',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    PRIMARY KEY (destination_id),
    KEY idx_type_code (type_code),
    KEY idx_active (is_active),
    CONSTRAINT fk_destination_type FOREIGN KEY (type_code)
        REFERENCES mrs_destination_types(type_code)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='去向管理表';

-- 插入示例数据
INSERT INTO mrs_destinations (type_code, destination_name, destination_code, sort_order, created_by) VALUES
    ('return', '退回供应商', 'RETURN_001', 1, 'system'),
    ('warehouse', '北京仓库', 'WH_BJ', 1, 'system'),
    ('warehouse', '上海仓库', 'WH_SH', 2, 'system'),
    ('warehouse', '广州仓库', 'WH_GZ', 3, 'system'),
    ('store', '门店001', 'STORE_001', 1, 'system'),
    ('store', '门店002', 'STORE_002', 2, 'system')
ON DUPLICATE KEY UPDATE destination_name = VALUES(destination_name);

-- =============================================
-- 3. 修改包裹台账表，添加去向字段
-- =============================================
ALTER TABLE mrs_package_ledger
    ADD COLUMN destination_id INT UNSIGNED DEFAULT NULL COMMENT '出库去向ID' AFTER outbound_time,
    ADD COLUMN destination_note VARCHAR(255) DEFAULT NULL COMMENT '去向备注' AFTER destination_id,
    ADD KEY idx_destination (destination_id);

-- 添加外键约束（可选，如果希望保持数据完整性）
-- ALTER TABLE mrs_package_ledger
--     ADD CONSTRAINT fk_ledger_destination FOREIGN KEY (destination_id)
--     REFERENCES mrs_destinations(destination_id)
--     ON UPDATE CASCADE ON DELETE SET NULL;

-- =============================================
-- 4. 创建去向使用统计视图（便于报表查询）
-- =============================================
CREATE OR REPLACE VIEW v_destination_stats AS
SELECT
    d.destination_id,
    d.destination_name,
    dt.type_name,
    COUNT(l.ledger_id) as total_shipments,
    COUNT(DISTINCT DATE(l.outbound_time)) as days_used,
    MAX(l.outbound_time) as last_used_time
FROM mrs_destinations d
LEFT JOIN mrs_destination_types dt ON d.type_code = dt.type_code
LEFT JOIN mrs_package_ledger l ON d.destination_id = l.destination_id
    AND l.status = 'shipped'
WHERE d.is_active = 1
GROUP BY d.destination_id, d.destination_name, dt.type_name
ORDER BY total_shipments DESC;

-- =============================================
-- 迁移完成
-- =============================================
