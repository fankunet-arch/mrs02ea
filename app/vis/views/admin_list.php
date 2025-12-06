<?php
/**
 * VIS View - Admin Video List
 * 文件路径: app/vis/views/admin_list.php
 * 说明: 后台视频列表管理页面
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 获取筛选参数
$category = $_GET['category'] ?? '';
$platform = $_GET['platform'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建筛选条件
$filters = [];
if (!empty($category)) {
    $filters['category'] = $category;
}
if (!empty($platform)) {
    $filters['platform'] = $platform;
}

// 获取视频列表和总数
$videos = vis_get_videos($pdo, $filters, $limit, $offset);
$totalVideos = vis_get_videos_count($pdo, $filters);
$totalPages = ceil($totalVideos / $limit);

// 获取分类列表
$categories = vis_get_categories($pdo);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频管理 - VIS后台</title>
    <link rel="stylesheet" href="/vis/ap/css/common.css">
    <link rel="stylesheet" href="/vis/ap/css/admin.css">
    <link rel="stylesheet" href="/vis/ap/css/modal.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- 头部 -->
        <header class="admin-header">
            <div class="container admin-header-content">
                <h1 class="admin-title">VIS 视频灵感库 - 后台管理</h1>
                <div class="admin-user">
                    <span>欢迎，<?php echo htmlspecialchars($_SESSION['user_display_name'] ?? $_SESSION['user_login'] ?? 'Admin'); ?></span>
                    <a href="/mrs/ap/index.php?action=logout" class="btn btn-secondary">退出</a>
                </div>
            </div>
        </header>

        <!-- 主内容 -->
        <main class="admin-main">
            <div class="container">
                <!-- 页面标题和操作 -->
                <div class="video-list-header">
                    <h2 class="video-list-title">视频列表</h2>
                    <a href="/vis/ap/index.php?action=admin_upload" class="btn btn-primary">+ 上传视频</a>
                </div>

                <!-- 筛选栏 -->
                <div class="admin-filters">
                    <form method="GET" action="/vis/ap/index.php">
                        <input type="hidden" name="action" value="admin_list">
                        <div class="admin-filter-row">
                            <div class="form-group">
                                <label class="form-label">分类</label>
                                <select name="category" class="form-select">
                                    <option value="">全部分类</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category_code']); ?>"
                                            <?php echo $category === $cat['category_code'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">平台</label>
                                <select name="platform" class="form-select">
                                    <option value="">全部平台</option>
                                    <option value="wechat" <?php echo $platform === 'wechat' ? 'selected' : ''; ?>>微信</option>
                                    <option value="xiaohongshu" <?php echo $platform === 'xiaohongshu' ? 'selected' : ''; ?>>小红书</option>
                                    <option value="douyin" <?php echo $platform === 'douyin' ? 'selected' : ''; ?>>抖音</option>
                                    <option value="other" <?php echo $platform === 'other' ? 'selected' : ''; ?>>其他</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">筛选</button>
                            <a href="/vis/ap/index.php?action=admin_list" class="btn btn-outline">重置</a>
                        </div>
                    </form>
                </div>

                <!-- 视频表格 -->
                <div class="video-table-wrapper">
                    <?php if (empty($videos)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📹</div>
                            <div class="empty-state-text">暂无视频</div>
                        </div>
                    <?php else: ?>
                        <table class="video-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>标题</th>
                                    <th>分类</th>
                                    <th>平台</th>
                                    <th>大小</th>
                                    <th>上传时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videos as $video): ?>
                                    <tr>
                                        <td><?php echo $video['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($video['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($video['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $platformNames = [
                                                'wechat' => '微信',
                                                'xiaohongshu' => '小红书',
                                                'douyin' => '抖音',
                                                'other' => '其他'
                                            ];
                                            echo $platformNames[$video['platform']] ?? $video['platform'];
                                            ?>
                                        </td>
                                        <td><?php echo round($video['file_size'] / 1024 / 1024, 2); ?> MB</td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($video['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-play" onclick="playVideo(<?php echo $video['id']; ?>)" title="播放">
                                                    ▶
                                                </button>
                                                <button class="btn-icon btn-edit" onclick="editVideo(<?php echo $video['id']; ?>)" title="编辑">
                                                    ✏
                                                </button>
                                                <button class="btn-icon btn-delete" onclick="deleteVideo(<?php echo $video['id']; ?>)" title="删除">
                                                    🗑
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <div class="admin-pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?action=admin_list&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page - 1; ?>" class="page-btn">上一页</a>
                                <?php endif; ?>

                                <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页（共 <?php echo $totalVideos; ?> 个视频）</span>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?action=admin_list&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page + 1; ?>" class="page-btn">下一页</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/vis/ap/js/modal.js"></script>
    <script>
        // 播放视频
        async function playVideo(id) {
            try {
                const response = await fetch(`/vis/ap/index.php?action=play_sign&id=${id}`);
                const result = await response.json();

                if (!result.success) {
                    showAlert(result.message, '错误', 'error');
                    return;
                }

                // 显示播放器模态框
                showModal({
                    title: result.data.title,
                    content: `
                        <video class="video-player" controls autoplay oncontextmenu="return false;">
                            <source src="${result.data.url}" type="video/mp4">
                            您的浏览器不支持视频播放。
                        </video>
                    `,
                    width: '800px',
                    footer: '<div class="modal-footer"><button class="modal-btn modal-btn-secondary" data-action="close">关闭</button></div>'
                });
            } catch (error) {
                showAlert('获取播放链接失败', '错误', 'error');
            }
        }

        // 编辑视频
        function editVideo(id) {
            // TODO: 实现编辑功能
            showAlert('编辑功能开发中', '提示', 'info');
        }

        // 删除视频
        async function deleteVideo(id) {
            const confirmed = await showConfirm(
                '确定要删除这个视频吗？删除后无法恢复。',
                '确认删除',
                { type: 'warning', confirmText: '删除', confirmClass: 'modal-btn-danger' }
            );

            if (!confirmed) return;

            try {
                const response = await fetch('/vis/ap/index.php?action=video_delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, '成功', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, '错误', 'error');
                }
            } catch (error) {
                showAlert('删除失败', '错误', 'error');
            }
        }
    </script>
</body>
</html>
