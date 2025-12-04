# 更新日志

## [2025-12-04] - 出库去向管理功能

### 新增功能

#### 1. 出库去向管理
- ✅ 添加去向管理菜单和页面
- ✅ 支持添加、编辑、删除去向
- ✅ 支持多种去向类型（退回、仓库调仓、发往门店、其他）
- ✅ 去向可配置联系人、联系电话、地址等信息
- ✅ 去向使用统计（防止删除已使用的去向）

#### 2. 出库功能增强
- ✅ 出库时必须选择去向
- ✅ 支持填写去向备注（如退货单号、调拨单号等）
- ✅ 出库记录关联去向信息，便于追踪货物流向

#### 3. 现代化模态框
- ✅ 创建统一的模态框组件
- ✅ 替换所有 `alert()` 和 `confirm()` 为现代化模态框
- ✅ 支持自定义表单模态框
- ✅ 响应式设计，支持移动端
- ✅ 同时应用于MRS和EXPRESS系统

### 数据库变更

1. **新增表**
   - `mrs_destination_types` - 去向类型配置表
   - `mrs_destinations` - 去向管理表

2. **表结构修改**
   - `mrs_package_ledger` 表添加以下字段：
     - `destination_id` - 出库去向ID
     - `destination_note` - 去向备注

3. **新增视图**
   - `v_destination_stats` - 去向使用统计视图

### 技术改进

1. **用户体验**
   - 现代化的模态框设计
   - 更友好的交互提示
   - 统一的视觉风格

2. **代码质量**
   - 模块化的模态框组件
   - 标准化的API响应
   - 完善的错误处理

3. **扩展性**
   - 灵活的去向类型系统
   - 支持未来功能扩展（仓库调仓、发往门店）
   - 预留字段设计

### 文件变更

#### 新增文件
- `docs/migrations/add_destination_management.sql` - 数据库迁移
- `docs/migrations/README.md` - 迁移说明
- `app/mrs/views/destination_manage.php` - 去向管理页面
- `app/mrs/api/destination_save.php` - 去向管理API
- `dc_html/mrs/ap/css/modal.css` - 模态框样式
- `dc_html/mrs/ap/js/modal.js` - 模态框组件
- `dc_html/express/exp/css/modal.css` - EXPRESS系统模态框样式
- `dc_html/express/exp/js/modal.js` - EXPRESS系统模态框组件
- `CHANGELOG.md` - 更新日志

#### 修改文件
- `app/mrs/lib/mrs_lib.php` - 添加去向管理函数，更新出库函数
- `app/mrs/views/outbound.php` - 添加去向选择功能
- `app/mrs/views/inventory_detail.php` - 使用模态框
- `app/mrs/views/shared/sidebar.php` - 添加去向管理菜单
- `app/mrs/api/outbound_save.php` - 添加去向参数处理
- `app/express/views/batch_detail.php` - 使用模态框
- `dc_html/mrs/ap/index.php` - 添加路由

### 使用说明

1. **运行数据库迁移**
   ```bash
   mysql -u root -p mrs_system < docs/migrations/add_destination_management.sql
   ```

2. **配置去向**
   - 登录MRS后台
   - 进入"去向管理"菜单
   - 根据实际业务添加或修改去向

3. **出库操作**
   - 进入"出库核销"页面
   - 选择要出库的包裹
   - 必须选择出库去向（支持按类型分组）
   - 可选填写去向备注
   - 确认出库

### 扩展建议

系统已为未来扩展做好准备：

1. **仓库调仓功能**
   - 创建专门的调仓页面
   - 利用"仓库调仓"类型的去向
   - 生成调仓单据

2. **发往门店功能**
   - 创建发货页面
   - 利用"发往门店"类型的去向
   - 生成发货单据

3. **数据统计和报表**
   - 利用 `v_destination_stats` 视图
   - 按去向统计出库量
   - 分析货物流向
