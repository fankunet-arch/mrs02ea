# VIS (Video Inspiration System) 视频灵感库系统

## 系统概述

VIS是一个独立的视频灵感管理平台，用于结构化存储、分类管理和在线预览视频素材。系统采用PHP + MySQL + Cloudflare R2存储架构。

## 部署架构

VIS系统采用与MRS相同的"动静分离"架构：

```
/home/user/mrs02ea/
├── app/vis/                    # 后端核心（不可直接访问）
│   ├── api/                    # API接口
│   ├── config_vis/             # 配置文件
│   ├── lib/                    # 核心库
│   ├── views/                  # 视图模板
│   └── bootstrap.php           # 引导文件
│
├── dc_html/vis/                # 前端入口（Web访问根目录）
│   └── ap/
│       ├── css/                # 静态样式
│       ├── js/                 # 静态脚本
│       └── index.php           # 唯一入口
│
└── docs/vis/                   # 文档和SQL脚本
    ├── schema.sql              # 数据库表结构
    └── README.md               # 本文档
```

## 数据库部署

### 1. 创建数据表

执行SQL脚本：

```bash
mysql -h mhdlmskp2kpxguj.mysql.db -u mhdlmskp2kpxguj -p mhdlmskp2kpxguj < docs/vis/schema.sql
```

### 2. 表结构说明

- **vis_videos**: 视频数据主表，存储视频元信息和R2路径
- **vis_categories**: 视频分类表，支持动态管理分类

## Cloudflare R2 配置

### 1. 获取R2凭证

登录Cloudflare Dashboard → R2 → 创建API Token，获取：

- `Account ID`
- `Access Key ID`
- `Secret Access Key`
- `Bucket Name`

### 2. 配置文件设置

在 `app/vis/config_vis/env_vis.php` 中配置R2参数（见下方部署步骤）。

## Web服务器配置

### Nginx配置示例

```nginx
server {
    listen 80;
    server_name dc.abcabc.net;
    root /home/user/mrs02ea/dc_html;
    index index.php index.html;

    # VIS前台展示页面（公开访问）
    location /vis/ {
        try_files $uri $uri/ /vis/ap/index.php?$query_string;
    }

    # VIS后台管理（需要登录）
    location /vis/ap/ {
        try_files $uri $uri/ /vis/ap/index.php?$query_string;
    }

    # 阻止直接访问app目录
    location ~ ^/app/ {
        deny all;
    }

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 功能特性

### 后台管理端

- ✅ 复用DC系统`sys_users`表进行身份验证
- ✅ 视频列表管理（标题、分类、来源、时长、大小）
- ✅ 按分类和来源筛选
- ✅ 视频上传：本地 → OVH临时 → R2 → 数据库
- ✅ 编辑/删除（同步删除R2文件）
- ✅ 自定义模态框（无系统alert/confirm）

### 前台展示端

- ✅ 响应式布局（Mobile First）
  - 手机：单列瀑布流
  - 平板：2-3列栅格
  - 桌面：3-4列栅格
- ✅ 视频播放器（HTML5 Video）
- ✅ 防盗链：临时签名URL（300秒有效期）
- ✅ 禁用右键菜单

## 安全特性

1. **目录隔离**: `app/vis/`目录不可直接访问
2. **入口验证**: 所有后端文件需检查`VIS_ENTRY`常量
3. **会话共享**: 复用DC系统的`sys_users`表和会话机制
4. **防盗链**: R2签名URL，300秒自动过期
5. **密钥保护**: R2凭证仅存储在服务端配置文件

## 开发规范

### 1. 模态框使用

**禁止使用**浏览器原生对话框：

```javascript
// ❌ 禁止
alert('操作成功');
confirm('确认删除？');

// ✅ 使用自定义模态框
showAlert('操作成功', '提示', 'success');
showConfirm('确认删除？', '警告', {type: 'warning'});
```

### 2. 防闪现机制

模态框DOM必须预先存在，通过CSS控制显示/隐藏：

```javascript
// 使用requestAnimationFrame确保动画平滑
requestAnimationFrame(() => {
    requestAnimationFrame(() => {
        overlay.classList.add('active');
    });
});
```

### 3. 响应式断点

```css
/* 手机 */
@media (max-width: 767px) {
    .video-grid { grid-template-columns: 1fr; }
}

/* 平板 */
@media (min-width: 768px) and (max-width: 1023px) {
    .video-grid { grid-template-columns: repeat(2, 1fr); }
}

/* 桌面 */
@media (min-width: 1024px) {
    .video-grid { grid-template-columns: repeat(4, 1fr); }
}
```

## API接口说明

### 后台管理API

| 接口路径 | 方法 | 功能 | 参数 |
|---------|------|------|------|
| `/vis/ap/index.php?action=video_list` | GET | 获取视频列表 | `category`, `platform`, `page` |
| `/vis/ap/index.php?action=video_upload` | POST | 上传视频 | `title`, `category`, `platform`, `file` |
| `/vis/ap/index.php?action=video_save` | POST | 保存视频元信息 | `id`, `title`, `category` |
| `/vis/ap/index.php?action=video_delete` | POST | 删除视频 | `id` |
| `/vis/ap/index.php?action=video_sign` | GET | 获取签名播放URL | `id` |

### 前台展示API

| 接口路径 | 方法 | 功能 | 参数 |
|---------|------|------|------|
| `/vis/ap/index.php?action=gallery` | GET | 展示视频列表 | `category`, `platform` |
| `/vis/ap/index.php?action=play_sign` | GET | 获取播放签名 | `id` |

## 验收清单

### 功能验收

- [ ] 上传50MB视频成功，数据库和R2均有记录
- [ ] 点击封面可立即播放，无卡顿
- [ ] 删除视频后，数据库记录和R2文件均被删除
- [ ] 分类筛选功能正常

### UI/UX验收

- [ ] 无浏览器原生alert/confirm对话框
- [ ] 模态框无闪现
- [ ] 手机端单列布局，无横向滚动条
- [ ] 按钮大小适合手指点击

### 安全验收

- [ ] 无法直接访问`app/vis/`目录
- [ ] R2密钥未暴露在前端代码中
- [ ] 播放链接包含签名参数，5分钟后失效

## 技术栈

- **后端**: PHP 8.x
- **数据库**: MySQL 8.x
- **存储**: Cloudflare R2 (S3兼容API)
- **前端**: 原生JavaScript + CSS Grid
- **视频播放**: HTML5 Video API

## 联系方式

如有问题，请参考MRS系统开发文档或联系系统管理员。
