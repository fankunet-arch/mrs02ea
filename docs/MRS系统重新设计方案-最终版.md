# MRS 系统重新设计方案（最终版）

## 🎯 **设计原则**

### ✅ **系统独立运行**
- MRS 和 Express **代码完全独立**，不相互调用
- **不使用数据库外键**，避免系统耦合
- MRS 可以独立运行，不依赖 Express 数据库状态

### ✅ **业务逻辑连贯**
- 通过**字段冗余**建立逻辑关联
- 通过 **batch_name + tracking_number** 作为业务关联键
- 数据可追溯，但不强制依赖

---

## 🗄️ **新的 MRS 数据库表设计（松耦合）**

```sql
-- 新的 MRS 包裹台账表（松耦合设计）
DROP TABLE IF EXISTS `mrs_package_ledger`;
CREATE TABLE `mrs_package_ledger` (
  `ledger_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '台账ID (主键)',

  -- ⭐ 业务关联字段（通过冗余建立逻辑关联，无外键）
  `batch_name` VARCHAR(100) NOT NULL COMMENT '批次名称（来自 Express）',
  `tracking_number` VARCHAR(100) NOT NULL COMMENT '快递单号（来自 Express）',
  `content_note` TEXT COMMENT '内容备注（来自 Express 清点，如"香蕉"）',

  -- MRS 库存管理字段
  `box_number` VARCHAR(20) NOT NULL COMMENT '箱号（4位编号：0001, 0002...）',
  `warehouse_location` VARCHAR(50) DEFAULT NULL COMMENT '仓库位置（可选）',
  `spec_info` VARCHAR(100) DEFAULT NULL COMMENT '规格备注（如：20斤）',

  -- 状态管理
  `status` ENUM('in_stock', 'shipped', 'void') NOT NULL DEFAULT 'in_stock'
    COMMENT '状态：在库/已出/损耗',
  `inbound_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  `outbound_time` DATETIME DEFAULT NULL COMMENT '出库时间',
  `destination_id` INT UNSIGNED DEFAULT NULL COMMENT '出库去向ID',
  `destination_note` VARCHAR(255) DEFAULT NULL COMMENT '去向备注',
  `void_reason` VARCHAR(255) DEFAULT NULL COMMENT '损耗原因',

  -- 操作记录
  `created_by` VARCHAR(60) DEFAULT NULL COMMENT '创建人',
  `updated_by` VARCHAR(60) DEFAULT NULL COMMENT '更新人',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

  PRIMARY KEY (`ledger_id`),

  -- ⭐ 唯一约束（防止重复入库）
  UNIQUE KEY `uk_batch_tracking` (`batch_name`, `tracking_number`)
    COMMENT '同一批次的同一快递单号只能入库一次',
  UNIQUE KEY `uk_batch_box` (`batch_name`, `box_number`)
    COMMENT '同一批次内箱号唯一',

  -- 索引优化
  KEY `idx_status` (`status`),
  KEY `idx_content_note` (`content_note`(50)) COMMENT '按内容查询（物料）',
  KEY `idx_batch_name` (`batch_name`),
  KEY `idx_inbound_time` (`inbound_time`),
  KEY `idx_outbound_time` (`outbound_time`),
  KEY `idx_destination` (`destination_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='MRS 包裹台账表（松耦合设计，通过冗余关联 Express）';

-- 去向类型配置表
CREATE TABLE `mrs_destination_types` (
  `type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '类型ID',
  `type_code` VARCHAR(20) NOT NULL COMMENT '类型代码 (return, warehouse, store)',
  `type_name` VARCHAR(50) NOT NULL COMMENT '类型名称 (退回、仓库调仓、发往门店)',
  `is_enabled` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `sort_order` INT DEFAULT 0 COMMENT '排序',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',

  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_type_code` (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='去向类型配置表';

-- 去向管理表
CREATE TABLE `mrs_destinations` (
  `destination_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '去向ID',
  `type_code` VARCHAR(20) NOT NULL COMMENT '去向类型代码',
  `destination_name` VARCHAR(100) NOT NULL COMMENT '去向名称',
  `destination_code` VARCHAR(50) DEFAULT NULL COMMENT '去向编码（可选）',
  `contact_person` VARCHAR(50) DEFAULT NULL COMMENT '联系人',
  `contact_phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `address` TEXT DEFAULT NULL COMMENT '地址',
  `remark` TEXT DEFAULT NULL COMMENT '备注',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否有效',
  `sort_order` INT DEFAULT 0 COMMENT '排序',
  `created_by` VARCHAR(60) DEFAULT NULL COMMENT '创建人',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

  PRIMARY KEY (`destination_id`),
  KEY `idx_type_code` (`type_code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='去向管理表';

-- 删除不再需要的 mrs_sku 表（物料信息来自 content_note）
DROP TABLE IF EXISTS `mrs_sku`;
```

---

## 🔄 **完整业务流程**

### **1. Express 阶段（收点包裹）**

```
操作员在 Express 系统中：
1. 创建批次：batch_name = "2024-12-01-水果"
2. 导入快递单号：111111, 222222, 333333
3. 清点包裹，填写内容：
   - 111111 → content_note = "香蕉"
   - 222222 → content_note = "香蕉"
   - 333333 → content_note = "苹果"
4. 包裹状态：counted（已清点）

Express 数据库状态：
┌────────────┬──────────┬─────────────────┬─────────────┬──────────────┐
│ package_id │ batch_id │ tracking_number │ content_note│ package_status│
├────────────┼──────────┼─────────────────┼─────────────┼──────────────┤
│ 1          │ 1        │ 111111          │ 香蕉        │ counted      │
│ 2          │ 1        │ 222222          │ 香蕉        │ counted      │
│ 3          │ 1        │ 333333          │ 苹果        │ counted      │
└────────────┴──────────┴─────────────────┴─────────────┴──────────────┘
```

### **2. MRS 阶段（入库管理）**

**用户操作**：
1. 打开 MRS 系统 "入库录入" 页面
2. 选择 Express 批次："2024-12-01-水果"
3. 系统查询 Express 数据库，显示已清点的包裹列表
4. 用户勾选要入库的包裹
5. 系统自动分配 box_number，完成入库

**MRS 查询 Express 数据**（松耦合）：
```php
// MRS 查询 Express 已清点的包裹（只读操作）
SELECT
  b.batch_name,
  p.tracking_number,
  p.content_note,
  p.package_status
FROM express_package p
INNER JOIN express_batch b ON p.batch_id = b.batch_id
WHERE b.batch_name = '2024-12-01-水果'
  AND p.package_status IN ('counted', 'adjusted')
  AND p.content_note IS NOT NULL
ORDER BY p.tracking_number;

// 过滤掉已入库的（通过 batch_name + tracking_number 检查）
AND NOT EXISTS (
  SELECT 1 FROM mrs_package_ledger m
  WHERE m.batch_name = b.batch_name
    AND m.tracking_number = p.tracking_number
)
```

**MRS 入库操作**：
```sql
INSERT INTO mrs_package_ledger
  (batch_name, tracking_number, content_note, box_number, spec_info,
   status, inbound_time, created_by)
VALUES
  ('2024-12-01-水果', '111111', '香蕉', '0001', '20斤', 'in_stock', NOW(), 'admin'),
  ('2024-12-01-水果', '222222', '香蕉', '0002', '20斤', 'in_stock', NOW(), 'admin');
```

**MRS 数据库状态**：
```
┌───────────┬─────────────────┬─────────────────┬──────────────┬────────────┬────────┐
│ ledger_id │ batch_name      │ tracking_number │ content_note │ box_number │ status │
├───────────┼─────────────────┼─────────────────┼──────────────┼────────────┼────────┤
│ 1         │ 2024-12-01-水果 │ 111111          │ 香蕉         │ 0001       │ in_stock│
│ 2         │ 2024-12-01-水果 │ 222222          │ 香蕉         │ 0002       │ in_stock│
└───────────┴─────────────────┴─────────────────┴──────────────┴────────────┴────────┘
```

### **3. MRS 库存查询**

**按物料统计**：
```sql
SELECT
  content_note AS sku_name,
  COUNT(*) AS total_boxes
FROM mrs_package_ledger
WHERE status = 'in_stock'
GROUP BY content_note
ORDER BY content_note;

结果：
香蕉：2 箱
```

**库存明细（FIFO）**：
```sql
SELECT
  ledger_id,
  batch_name,
  box_number,
  tracking_number,
  content_note AS sku_name,
  spec_info,
  inbound_time,
  DATEDIFF(NOW(), inbound_time) AS days_in_stock
FROM mrs_package_ledger
WHERE status = 'in_stock'
  AND content_note = '香蕉'
ORDER BY inbound_time ASC;  -- FIFO 先进先出
```

### **4. MRS 出库操作**

**出库流程**：
1. 选择要出库的包裹
2. 选择出库去向（退回、仓库调仓、发往门店等）
3. 可选填写去向备注（如退货单号、调拨单号）
4. 确认出库

```sql
-- 出库操作（包含去向信息）
UPDATE mrs_package_ledger
SET status = 'shipped',
    outbound_time = NOW(),
    destination_id = 1,  -- 去向ID（如：北京仓库）
    destination_note = '调拨单号：DB20251204001',  -- 去向备注
    updated_by = 'admin'
WHERE ledger_id IN (1, 2);
```

**去向管理**：
```sql
-- 查询所有有效去向
SELECT
  d.destination_id,
  d.destination_name,
  dt.type_name,
  d.destination_code,
  d.contact_person,
  d.contact_phone
FROM mrs_destinations d
LEFT JOIN mrs_destination_types dt ON d.type_code = dt.type_code
WHERE d.is_active = 1
ORDER BY dt.sort_order, d.sort_order;

-- 统计各去向的出库量
SELECT
  d.destination_name,
  dt.type_name,
  COUNT(l.ledger_id) as total_shipments
FROM mrs_destinations d
LEFT JOIN mrs_destination_types dt ON d.type_code = dt.type_code
LEFT JOIN mrs_package_ledger l ON d.destination_id = l.destination_id
  AND l.status = 'shipped'
GROUP BY d.destination_id
ORDER BY total_shipments DESC;
```

---

## 🔗 **系统关系图**

```
┌──────────────────────────────────────────────────────────────┐
│  Express 系统（独立数据库）                                    │
│  ┌──────────────┐      ┌────────────────┐                    │
│  │ express_batch│ ───→ │express_package │                    │
│  │ - batch_name │      │ - tracking_no  │                    │
│  └──────────────┘      │ - content_note │                    │
│                        └────────────────┘                    │
└──────────────────────────────────────────────────────────────┘
                            │
                            │ (松耦合：只读查询)
                            │
                            ↓
┌──────────────────────────────────────────────────────────────┐
│  MRS 系统（独立数据库）                                        │
│  ┌────────────────────────────────┐                          │
│  │  mrs_package_ledger            │                          │
│  │  - batch_name (冗余)           │                          │
│  │  - tracking_number (冗余)      │                          │
│  │  - content_note (冗余)         │                          │
│  │  - box_number (MRS 分配)       │ ┌──────────────────────┐ │
│  │  - status (in_stock/shipped)   │ │ mrs_destinations     │ │
│  │  - destination_id ─────────────┼─┤ - destination_name   │ │
│  └────────────────────────────────┘ │ - type_code          │ │
│                                     │ - contact_person     │ │
│  ┌──────────────────────┐           └──────────────────────┘ │
│  │ mrs_destination_types│                    ↑                │
│  │ - type_code          │────────────────────┘                │
│  │ - type_name          │                                     │
│  └──────────────────────┘                                     │
└──────────────────────────────────────────────────────────────┘
```

---

## ✅ **优势**

### 1. **系统独立**
- ✅ 无外键依赖，MRS 可以独立运行
- ✅ Express 宕机不影响 MRS 查询已入库数据
- ✅ 代码完全解耦，互不调用

### 2. **业务连贯**
- ✅ 通过 batch_name + tracking_number 建立逻辑关联
- ✅ content_note 冗余存储，保留物料信息
- ✅ 数据可追溯到 Express 原始包裹

### 3. **防重复**
- ✅ UNIQUE KEY `uk_batch_tracking` 防止重复入库
- ✅ UNIQUE KEY `uk_batch_box` 防止箱号冲突

---

## 📝 **关键字段说明**

| 字段 | 来源 | 说明 |
|------|------|------|
| `batch_name` | Express | 批次名称（冗余），用于逻辑关联 |
| `tracking_number` | Express | 快递单号（冗余），唯一标识包裹 |
| `content_note` | Express | 内容备注（冗余），即"物料名称" |
| `box_number` | MRS | 4位编号，MRS 系统分配 |
| `status` | MRS | 库存状态，MRS 系统管理 |
| `destination_id` | MRS | 出库去向ID，关联 mrs_destinations |
| `destination_note` | MRS | 去向备注，如退货单号、调拨单号 |

---

## 🚀 **实施步骤**

1. ✅ 删除旧的 `mrs_package_ledger` 表
2. ✅ 创建新的 `mrs_package_ledger` 表（无外键）
3. ✅ 删除 `mrs_sku` 表（不再需要）
4. ✅ 重写 MRS 入库逻辑（从 Express 查询 + 冗余存储）
5. ✅ 修改 MRS 库存查询（按 content_note 分组）
6. ✅ 添加去向管理功能（支持退回、仓库调仓、发往门店）
7. ✅ 出库流程增强（必须选择去向）
8. ✅ 替换系统弹出框为现代化模态框
9. ✅ 测试完整流程

---

## 🎨 **用户体验改进**

### 现代化模态框
- 统一的模态框组件（替代传统 alert/confirm）
- 支持自定义表单输入
- 响应式设计，移动端友好
- 优雅的动画效果
- 同时应用于 MRS 和 EXPRESS 系统

### 功能特性
- 去向管理：支持添加、编辑、删除去向
- 出库追踪：记录每次出库的去向和备注
- 统计分析：查看各去向的出库量
- 扩展性强：预留仓库调仓、发往门店功能接口

---

**设计原则**: 松耦合，业务一致，用户友好
**最终确认日期**: 2025-12-04
