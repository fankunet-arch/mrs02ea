# 虚拟主机数据库迁移说明

## OVH/cPanel虚拟主机执行步骤

由于虚拟主机环境的限制，您不能使用 `USE database_name` 语句。请按以下步骤操作：

### 方法1：使用phpMyAdmin（推荐）

1. **登录phpMyAdmin**
   - 访问您的OVH控制面板
   - 进入phpMyAdmin

2. **选择数据库**
   - 在左侧列表中点击选择您的数据库（例如：`mhdlmskp2kpxguj`）

3. **执行迁移SQL**
   - 点击顶部的 "SQL" 标签页
   - 复制粘贴 `add_destination_management_shared_hosting.sql` 文件的内容
   - 点击 "执行" 按钮

4. **验证结果**
   - 检查是否成功创建了以下表：
     - `mrs_destination_types`
     - `mrs_destinations`
   - 检查 `mrs_package_ledger` 表是否添加了新字段：
     - `destination_id`
     - `destination_note`

### 方法2：使用MySQL命令行（如果有SSH访问权限）

```bash
# 替换为您的实际数据库名称
mysql -u mhdlmskp2kpxguj -p mhdlmskp2kpxguj < add_destination_management_shared_hosting.sql
```

### 常见问题

**Q: 执行ALTER TABLE时出错？**
- A: 如果 `mrs_package_ledger` 表已经有这些字段，可以忽略此错误，或者先检查表结构

**Q: 无法创建外键约束？**
- A: 部分虚拟主机禁用外键。您可以注释掉外键相关的SQL，系统仍可正常工作

**Q: 创建视图失败？**
- A: 部分虚拟主机不允许创建视图。可以跳过视图创建，不影响核心功能

### 检查迁移是否成功

执行以下SQL查询来验证：

```sql
-- 检查表是否创建
SHOW TABLES LIKE 'mrs_destination%';

-- 检查去向类型数据
SELECT * FROM mrs_destination_types;

-- 检查示例去向数据
SELECT * FROM mrs_destinations;

-- 检查package_ledger表结构
DESCRIBE mrs_package_ledger;
```

### 回滚（如需要）

如果需要撤销更改：

```sql
-- 删除新增的字段
ALTER TABLE mrs_package_ledger
    DROP COLUMN destination_id,
    DROP COLUMN destination_note;

-- 删除视图
DROP VIEW IF EXISTS v_destination_stats;

-- 删除表
DROP TABLE IF EXISTS mrs_destinations;
DROP TABLE IF EXISTS mrs_destination_types;
```

## 注意事项

1. **备份数据**：执行迁移前建议先备份数据库
2. **字段冲突**：如果某些字段已存在，可能需要手动调整SQL
3. **权限限制**：虚拟主机可能有一些MySQL功能限制，遇到错误时可以跳过对应的SQL语句

## 获取帮助

如果遇到问题，请提供：
- 完整的错误消息
- 您的数据库名称（隐藏敏感信息）
- phpMyAdmin的MySQL版本信息
