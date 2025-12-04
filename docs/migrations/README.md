# 数据库迁移说明

## 添加出库去向管理功能

### 执行迁移

运行以下命令来添加去向管理功能的数据库表和字段：

```bash
mysql -u root -p mrs_system < docs/migrations/add_destination_management.sql
```

或者使用phpMyAdmin等数据库管理工具，执行 `add_destination_management.sql` 文件中的SQL语句。

### 迁移内容

1. 创建 `mrs_destination_types` 表 - 去向类型配置
2. 创建 `mrs_destinations` 表 - 去向管理
3. 修改 `mrs_package_ledger` 表 - 添加去向字段
4. 创建 `v_destination_stats` 视图 - 去向使用统计

### 初始数据

迁移会自动创建以下初始数据：

**去向类型：**
- 退回
- 仓库调仓
- 发往门店
- 其他

**示例去向：**
- 退回供应商
- 北京仓库 (WH_BJ)
- 上海仓库 (WH_SH)
- 广州仓库 (WH_GZ)
- 门店001 (STORE_001)
- 门店002 (STORE_002)

### 新功能

迁移后，系统将支持：

1. **去向管理** - 在MRS后台菜单中添加"去向管理"功能
2. **出库去向选择** - 出库时必须选择去向
3. **去向统计** - 可通过视图查看各去向的使用统计

### 扩展性

系统设计支持未来扩展：
- 仓库调仓功能
- 发往门店功能
- 自定义去向类型
