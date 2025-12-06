# VIS 系统部署指南

## 一、系统概述

VIS (Video Inspiration System) 是一个独立的视频灵感管理平台，采用 PHP + MySQL + Cloudflare R2 架构。

### 核心特性

- ✅ 独立部署，与 MRS 系统物理隔离
- ✅ 复用 DC 系统 `sys_users` 表进行身份验证
- ✅ Cloudflare R2 对象存储（S3 兼容）
- ✅ 响应式设计（Mobile First）
- ✅ 自定义模态框（无系统 alert/confirm）
- ✅ 防盗链保护（临时签名 URL）

## 二、部署前准备

### 1. 环境要求

- **Web 服务器**: Nginx / Apache
- **PHP**: >= 8.0
- **MySQL**: >= 8.0
- **PHP 扩展**: PDO, PDO_MySQL, cURL, JSON, OpenSSL

### 2. 配置 Cloudflare R2

#### 2.1 创建 R2 存储桶

1. 登录 Cloudflare Dashboard
2. 进入 R2 管理页面
3. 创建新存储桶，命名为 `vis-videos`

#### 2.2 生成 API Token

1. 在 R2 页面点击 "Manage R2 API Tokens"
2. 创建新 Token，权限选择：
   - 对象读取 (Object Read)
   - 对象写入 (Object Write)
3. 记录以下信息：
   - `Account ID`
   - `Access Key ID`
   - `Secret Access Key`

#### 2.3 配置自定义域名（可选）

1. 在 R2 存储桶设置中添加自定义域名
2. 配置 DNS 记录指向 R2 端点
3. 启用 HTTPS

## 三、数据库部署

### 1. 执行 SQL 脚本

```bash
# 方法一：命令行执行
mysql -h mhdlmskp2kpxguj.mysql.db -u mhdlmskp2kpxguj -p mhdlmskp2kpxguj < docs/vis/schema.sql

# 方法二：phpMyAdmin
# 登录 phpMyAdmin，选择数据库，导入 docs/vis/schema.sql
```

### 2. 验证表创建

```sql
-- 检查表是否创建成功
SHOW TABLES LIKE 'vis_%';

-- 应该看到：
-- vis_videos
-- vis_categories

-- 检查分类数据
SELECT * FROM vis_categories;
```

## 四、配置文件设置

### 1. 编辑 R2 配置

编辑 `app/vis/config_vis/env_vis.php`：

```php
// 填写实际的 Cloudflare R2 信息
define('VIS_R2_ACCOUNT_ID', 'YOUR_CLOUDFLARE_ACCOUNT_ID');
define('VIS_R2_ACCESS_KEY_ID', 'YOUR_R2_ACCESS_KEY_ID');
define('VIS_R2_SECRET_ACCESS_KEY', 'YOUR_R2_SECRET_KEY');
define('VIS_R2_BUCKET_NAME', 'vis-videos');

// 如果配置了自定义域名，修改此处
define('VIS_R2_PUBLIC_URL', 'https://vis.dc.abcabc.net');
```

### 2. 配置上传限制（可选）

```php
// 修改最大上传大小（默认 100MB）
define('VIS_MAX_FILE_SIZE', 100 * 1024 * 1024);

// 签名 URL 有效期（默认 300 秒）
define('VIS_SIGNED_URL_EXPIRES', 300);
```

### 3. PHP 上传限制

编辑 `php.ini`：

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

重启 PHP-FPM：

```bash
sudo systemctl restart php8.2-fpm
```

## 五、Web 服务器配置

### Nginx 配置

编辑 Nginx 配置文件（例如 `/etc/nginx/sites-available/dc.abcabc.net`）：

```nginx
server {
    listen 80;
    server_name dc.abcabc.net;
    root /home/user/mrs02ea/dc_html;
    index index.php index.html;

    # VIS 前台展示（公开访问）
    location /vis/ {
        try_files $uri $uri/ /vis/ap/index.php?$query_string;
    }

    # VIS 后台管理（需要登录）
    location /vis/ap/ {
        try_files $uri $uri/ /vis/ap/index.php?$query_string;
    }

    # 阻止直接访问 app 目录
    location ~ ^/app/ {
        deny all;
        return 403;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    # 静态文件缓存
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }
}
```

重新加载 Nginx：

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 六、文件权限设置

```bash
# 设置目录权限
sudo chown -R www-data:www-data /home/user/mrs02ea/app/vis
sudo chown -R www-data:www-data /home/user/mrs02ea/dc_html/vis
sudo chmod -R 755 /home/user/mrs02ea/app/vis
sudo chmod -R 755 /home/user/mrs02ea/dc_html/vis

# 临时上传目录
sudo mkdir -p /tmp/vis_uploads
sudo chown www-data:www-data /tmp/vis_uploads
sudo chmod 755 /tmp/vis_uploads
```

## 七、访问测试

### 1. 前台展示页面

访问：`http://dc.abcabc.net/vis/ap/index.php?action=gallery`

或配置 URL 重写后：`http://dc.abcabc.net/vis/`

### 2. 后台管理页面

访问：`http://dc.abcabc.net/vis/ap/index.php?action=admin_list`

**注意**：需要先登录 DC 系统（使用 `/mrs/ap/index.php?action=login`）

## 八、功能测试清单

### 后台管理测试

- [ ] 登录验证（使用 sys_users 账户）
- [ ] 视频列表显示
- [ ] 分类筛选功能
- [ ] 平台筛选功能
- [ ] 视频上传（50MB 以内）
- [ ] 上传进度显示
- [ ] 视频编辑（标题、分类、平台）
- [ ] 视频删除（确认模态框）
- [ ] 视频播放（签名 URL）

### 前台展示测试

- [ ] 响应式布局
  - [ ] 手机端（单列）
  - [ ] 平板端（2-3 列）
  - [ ] 桌面端（3-4 列）
- [ ] 视频列表显示
- [ ] 分类筛选
- [ ] 平台筛选
- [ ] 视频播放（禁用右键菜单）
- [ ] 分页功能

### 模态框测试

- [ ] 无浏览器原生 alert/confirm
- [ ] 模态框无闪现
- [ ] 平滑动画效果
- [ ] ESC 键关闭
- [ ] 点击遮罩关闭

### 安全测试

- [ ] 无法直接访问 `/app/vis/` 目录
- [ ] R2 密钥未暴露在前端
- [ ] 播放链接包含签名参数
- [ ] 签名 URL 5 分钟后失效
- [ ] 文件类型验证（仅 mp4/mov）
- [ ] 文件大小验证

## 九、常见问题

### 1. 上传失败：413 Request Entity Too Large

**原因**：Nginx 上传大小限制

**解决**：在 Nginx 配置中添加：

```nginx
client_max_body_size 100M;
```

### 2. 上传失败：504 Gateway Timeout

**原因**：PHP 执行超时

**解决**：增加超时时间：

```nginx
fastcgi_read_timeout 300;
```

```php
// php.ini
max_execution_time = 300
```

### 3. R2 上传失败

**检查项**：
1. 验证 R2 凭证是否正确
2. 检查存储桶名称
3. 确认 PHP cURL 扩展已安装
4. 查看错误日志：`tail -f /var/log/php8.2-fpm.log`

### 4. 视频播放失败

**检查项**：
1. 签名 URL 是否正确生成
2. R2 存储桶是否可访问
3. 浏览器控制台是否有跨域错误
4. 视频文件格式是否支持

### 5. 模态框闪现

**原因**：CSS 加载延迟

**解决**：确保 `modal.css` 在页面加载前引入

## 十、维护建议

### 1. 日志监控

```bash
# PHP 错误日志
tail -f /var/log/php8.2-fpm.log

# Nginx 错误日志
tail -f /var/log/nginx/error.log

# VIS 日志（通过 vis_log 函数）
tail -f /var/log/php8.2-fpm.log | grep "VIS"
```

### 2. 数据库备份

```bash
# 备份 VIS 表
mysqldump -h mhdlmskp2kpxguj.mysql.db -u mhdlmskp2kpxguj -p \
  mhdlmskp2kpxguj vis_videos vis_categories > vis_backup_$(date +%Y%m%d).sql
```

### 3. R2 存储监控

定期检查 Cloudflare R2 Dashboard：
- 存储空间使用量
- 请求次数统计
- 流量统计

## 十一、升级计划

### 功能扩展建议

- [ ] 视频首帧截图（使用 FFmpeg）
- [ ] 视频时长自动获取
- [ ] 批量上传功能
- [ ] 视频标签系统
- [ ] 搜索功能
- [ ] 视频收藏功能
- [ ] 统计报表

### 性能优化

- [ ] 添加 Redis 缓存
- [ ] CDN 加速
- [ ] 图片懒加载
- [ ] 分页优化

## 十二、技术支持

如遇到问题，请参考：
1. `docs/vis/README.md` - 系统说明文档
2. `docs/vis/schema.sql` - 数据库表结构
3. MRS 系统开发文档（架构参考）

或联系系统管理员。
