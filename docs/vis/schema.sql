-- ============================================
-- VIS (Video Inspiration System) 数据库表结构
-- 创建时间: 2025-12-06
-- 说明: 视频灵感库系统数据表
-- ============================================

-- 1. 视频数据表
CREATE TABLE IF NOT EXISTS `vis_videos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID',
    `title` VARCHAR(255) NOT NULL COMMENT '视频标题',
    `platform` ENUM('wechat', 'xiaohongshu', 'douyin', 'other') NOT NULL DEFAULT 'other' COMMENT '来源平台',
    `category` VARCHAR(50) NOT NULL DEFAULT '其他' COMMENT '分类（备料/制作/打包/营销等）',
    `r2_key` VARCHAR(512) NOT NULL COMMENT 'R2存储路径（例如：vis/202512/uuid.mp4）',
    `cover_url` VARCHAR(512) DEFAULT NULL COMMENT '封面图URL（视频首帧或默认图）',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '视频时长（秒）',
    `file_size` BIGINT UNSIGNED DEFAULT 0 COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT 'video/mp4' COMMENT '文件MIME类型',
    `original_filename` VARCHAR(255) DEFAULT NULL COMMENT '原始文件名',
    `status` ENUM('active', 'deleted') NOT NULL DEFAULT 'active' COMMENT '状态（active=正常, deleted=已删除）',
    `created_by` VARCHAR(50) DEFAULT NULL COMMENT '上传者用户名',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '创建时间',
    `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) COMMENT '更新时间',
    `deleted_at` DATETIME(6) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_platform` (`platform`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIS视频数据表';

-- 2. 视频分类表（可选，用于动态管理分类）
CREATE TABLE IF NOT EXISTS `vis_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `category_name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `category_code` VARCHAR(50) NOT NULL COMMENT '分类代码',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '分类描述',
    `sort_order` INT UNSIGNED DEFAULT 0 COMMENT '排序顺序',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用（1=启用, 0=禁用）',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category_code` (`category_code`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIS视频分类表';

-- 插入默认分类数据
INSERT INTO `vis_categories` (`category_name`, `category_code`, `description`, `sort_order`) VALUES
('备料', 'prepare', '原料准备相关视频', 1),
('制作', 'production', '制作过程相关视频', 2),
('打包', 'packing', '打包流程相关视频', 3),
('营销', 'marketing', '营销推广相关视频', 4),
('其他', 'other', '其他类型视频', 99);
