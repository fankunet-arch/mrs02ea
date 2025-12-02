# MRS 系统重新设计方案

## 🎯 **核心问题**

### 当前问题：
❌ **Express 和 MRS 包裹数据完全分离，无法衔接**

```
Express 系统：tracking_number (快递单号) → content_note (香蕉)
     ❌ 断层
MRS 系统：   sku_name (香蕉) + batch_code (A01) + box_number (0001)
```

### 正确的业务流程：

```
┌─────────────────────────────────────────────────────────────┐
│ Express 系统（收点包裹）                                      │
├─────────────────────────────────────────────────────────────┤
│ 1. 创建批次：batch_name = "2024-12-01-水果"                  │
│ 2. 导入快递单号：111111, 222222, 333333...                   │
│ 3. 清点包裹：填写 content_note = "香蕉"                      │
│ 4. 包裹状态：pending → verified → counted                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
                    （数据传递）
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ MRS 系统（库存管理）                                          │
├─────────────────────────────────────────────────────────────┤
│ 1. 读取 Express 已清点的包裹（content_note 非空）             │
│ 2. 分配 box_number（4位编号：0001, 0002, 0003...）           │
│ 3. 入库：status = in_stock                                  │
│ 4. 出库：status = shipped                                   │
│ 5. 库存查询：按 content_note 分组统计                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗄️ **新的 MRS 数据库表设计**

### **方案：MRS 包裹台账关联 Express 包裹**

```sql
-- 新的 MRS 包裹台账表
DROP TABLE IF EXISTS `mrs_package_ledger`;
CREATE TABLE `mrs_package_ledger` (
  `ledger_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '台账ID (主键)',

  -- ⭐ 关联 Express 包裹（核心字段）
  `express_package_id` INT UNSIGNED NOT NULL COMMENT 'Express 包裹ID',
  `express_batch_id` INT UNSIGNED NOT NULL COMMENT 'Express 批次ID',

  -- 冗余字段（来自 Express，便于查询）
  `tracking_number` VARCHAR(100) NOT NULL COMMENT '快递单号（冗余）',
  `content_note` TEXT COMMENT '内容备注（冗余，如"香蕉"）',
  `batch_name` VARCHAR(100) COMMENT '批次名称（冗余）',

  -- MRS 库存管理字段
  `box_number` VARCHAR(20) NOT NULL COMMENT '箱号（4位编号：0001, 0002...）',
  `warehouse_location` VARCHAR(50) DEFAULT NULL COMMENT '仓库位置（可选）',
  `spec_info` VARCHAR(100) DEFAULT NULL COMMENT '规格备注（如：20斤）',

  -- 状态管理
  `status` ENUM('in_stock', 'shipped', 'void') NOT NULL DEFAULT 'in_stock'
    COMMENT '状态：在库/已出/损耗',
  `inbound_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  `outbound_time` DATETIME DEFAULT NULL COMMENT '出库时间',
  `void_reason` VARCHAR(255) DEFAULT NULL COMMENT '损耗原因',

  -- 操作记录
  `created_by` VARCHAR(60) DEFAULT NULL COMMENT '创建人',
  `updated_by` VARCHAR(60) DEFAULT NULL COMMENT '更新人',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`ledger_id`),
  UNIQUE KEY `uk_express_package` (`express_package_id`) COMMENT '一个 Express 包裹只能入库一次',
  UNIQUE KEY `uk_batch_box` (`express_batch_id`, `box_number`) COMMENT '批次内箱号唯一',
  KEY `idx_status` (`status`),
  KEY `idx_content_note` (`content_note`(50)) COMMENT '按内容查询',
  KEY `idx_inbound_time` (`inbound_time`),
  KEY `idx_outbound_time` (`outbound_time`),

  -- 外键约束（可选，根据实际情况决定）
  CONSTRAINT `fk_mrs_express_package`
    FOREIGN KEY (`express_package_id`)
    REFERENCES `express_package` (`package_id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_mrs_express_batch`
    FOREIGN KEY (`express_batch_id`)
    REFERENCES `express_batch` (`batch_id`)
    ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='MRS 包裹台账表（关联 Express 包裹）';
```

---

## 🔄 **业务流程详解**

### **1. Express 系统操作**

```php
// Express 创建批次
batch_id = 1
batch_name = "2024-12-01-水果"

// Express 导入快递单号
INSERT INTO express_package (batch_id, tracking_number)
VALUES
  (1, '111111'),
  (1, '222222'),
  (1, '333333');

// Express 清点包裹（填写内容）
UPDATE express_package
SET content_note = '香蕉',
    package_status = 'counted',
    counted_at = NOW()
WHERE package_id IN (1, 2, 3);
```

### **2. MRS 入库操作（新流程）**

**用户操作**：
1. 在 MRS 系统选择批次："2024-12-01-水果"
2. 系统显示该批次下已清点但未入库的包裹
3. 用户勾选要入库的包裹，系统自动分配 box_number

**代码实现**：
```php
// 读取 Express 已清点但未入库的包裹
SELECT p.*, b.batch_name
FROM express_package p
INNER JOIN express_batch b ON p.batch_id = b.batch_id
WHERE p.batch_id = 1
  AND p.package_status IN ('counted', 'adjusted')
  AND p.content_note IS NOT NULL
  AND p.package_id NOT IN (
    SELECT express_package_id FROM mrs_package_ledger
  )
ORDER BY p.package_id;

// 批量入库（分配箱号）
INSERT INTO mrs_package_ledger
  (express_package_id, express_batch_id, tracking_number, content_note,
   batch_name, box_number, spec_info, status, inbound_time, created_by)
VALUES
  (1, 1, '111111', '香蕉', '2024-12-01-水果', '0001', '20斤', 'in_stock', NOW(), 'admin'),
  (2, 1, '222222', '香蕉', '2024-12-01-水果', '0002', '20斤', 'in_stock', NOW(), 'admin'),
  (3, 1, '333333', '香蕉', '2024-12-01-水果', '0003', '20斤', 'in_stock', NOW(), 'admin');
```

### **3. MRS 库存查询（新逻辑）**

**按内容分组统计**：
```sql
SELECT
  content_note AS sku_name,
  COUNT(*) AS total_boxes,
  COUNT(DISTINCT express_batch_id) AS batch_count
FROM mrs_package_ledger
WHERE status = 'in_stock'
GROUP BY content_note
ORDER BY content_note;
```

**明细查询**：
```sql
SELECT
  ledger_id,
  tracking_number,
  content_note AS sku_name,
  batch_name,
  box_number,
  spec_info,
  inbound_time,
  DATEDIFF(NOW(), inbound_time) AS days_in_stock
FROM mrs_package_ledger
WHERE status = 'in_stock'
  AND content_note = '香蕉'
ORDER BY inbound_time ASC;  -- FIFO
```

### **4. MRS 出库操作（不变）**

```sql
UPDATE mrs_package_ledger
SET status = 'shipped',
    outbound_time = NOW(),
    updated_by = 'admin'
WHERE ledger_id IN (1, 2, 3);
```

---

## 📊 **数据对比**

### **旧方案（有问题）**：
```
MRS 独立创建包裹：
  sku_name = "香蕉"
  batch_code = "A01"  ❌ 与 Express 无关
  box_number = "0001"
```

### **新方案（正确）**：
```
MRS 基于 Express 包裹：
  express_package_id = 1  ✅ 关联 Express
  content_note = "香蕉"   ✅ 来自 Express 清点
  batch_name = "2024-12-01-水果"  ✅ 来自 Express 批次
  box_number = "0001"     ✅ MRS 分配
```

---

## ✅ **优势**

1. **数据一致性**：MRS 包裹来源于 Express，确保数据准确
2. **可追溯性**：通过 express_package_id 可以追溯到原始快递单号
3. **避免重复**：UNIQUE KEY `uk_express_package` 防止同一包裹重复入库
4. **灵活查询**：冗余字段（tracking_number, content_note）提升查询效率

---

## 🚀 **实施步骤**

1. ✅ 备份当前数据库
2. ⏳ 删除旧的 `mrs_package_ledger` 和 `mrs_sku` 表
3. ⏳ 创建新的 `mrs_package_ledger` 表
4. ⏳ 重写 MRS 核心业务逻辑
5. ⏳ 修改 MRS 界面和 API
6. ⏳ 测试完整业务流程

---

**设计完成日期**: 2025-12-01
**设计者**: Claude (AI Assistant)
