<?php
/**
 * VIS View - Admin Upload
 * 文件路径: app/vis/views/admin_upload.php
 * 说明: 后台视频上传页面
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 获取分类列表
$categories = vis_get_categories($pdo);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传视频 - VIS后台</title>
    <link rel="stylesheet" href="/vis/ap/css/common.css">
    <link rel="stylesheet" href="/vis/ap/css/admin.css">
    <link rel="stylesheet" href="/vis/ap/css/modal.css">
</head>
<body>
    <div class="admin-wrapper">
        <header class="admin-header">
            <div class="container admin-header-content">
                <h1 class="admin-title">VIS 视频灵感库 - 上传视频</h1>
                <div class="admin-user">
                    <a href="/vis/ap/index.php?action=admin_list" class="btn btn-outline">返回列表</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="container">
                <div class="card upload-form">
                    <h2 class="card-header">上传新视频</h2>

                    <form id="uploadForm" enctype="multipart/form-data">
                        <!-- 文件上传区 -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">📹</div>
                            <div class="upload-text">点击选择或拖拽视频文件</div>
                            <div class="upload-hint">支持 MP4、MOV 格式，最大 100MB</div>
                            <input type="file" id="fileInput" name="video" accept="video/mp4,video/quicktime" class="file-input">
                        </div>

                        <!-- 文件信息显示 -->
                        <div id="fileSelected" class="file-selected" style="display:none;">
                            <div class="file-info">
                                <span class="file-name" id="fileName"></span>
                                <span class="file-size" id="fileSize"></span>
                                <button type="button" class="file-remove" onclick="removeFile()">×</button>
                            </div>
                        </div>

                        <!-- 视频信息 -->
                        <div class="form-group">
                            <label class="form-label">视频标题 *</label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="请输入视频标题">
                        </div>

                        <div class="form-group">
                            <label class="form-label">分类 *</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_code']); ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">来源平台</label>
                            <select name="platform" id="platform" class="form-select">
                                <option value="other">其他</option>
                                <option value="wechat">微信</option>
                                <option value="xiaohongshu">小红书</option>
                                <option value="douyin">抖音</option>
                            </select>
                        </div>

                        <!-- 上传按钮 -->
                        <div style="display: flex; gap: 12px;">
                            <button type="submit" class="btn btn-primary" id="submitBtn">上传视频</button>
                            <a href="/vis/ap/index.php?action=admin_list" class="btn btn-outline">取消</a>
                        </div>
                    </form>

                    <!-- 上传进度 -->
                    <div id="uploadProgress" class="upload-progress" style="display:none;">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" id="progressFill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text" id="progressText">上传中... 0%</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/vis/ap/js/modal.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        let selectedFile = null;

        // 点击上传区选择文件
        uploadArea.addEventListener('click', () => fileInput.click());

        // 文件选择
        fileInput.addEventListener('change', handleFileSelect);

        // 拖拽上传
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragging');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragging');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragging');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            // 验证文件类型
            if (!file.type.match('video/mp4') && !file.type.match('video/quicktime')) {
                showAlert('仅支持 MP4 和 MOV 格式的视频文件', '错误', 'error');
                fileInput.value = '';
                return;
            }

            // 验证文件大小（100MB）
            if (file.size > 100 * 1024 * 1024) {
                showAlert('文件大小超过限制（最大 100MB）', '错误', 'error');
                fileInput.value = '';
                return;
            }

            selectedFile = file;
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            fileSelected.style.display = 'block';
            uploadArea.style.display = 'none';
        }

        function removeFile() {
            selectedFile = null;
            fileInput.value = '';
            fileSelected.style.display = 'none';
            uploadArea.style.display = 'block';
        }

        // 表单提交
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!selectedFile) {
                showAlert('请选择要上传的视频文件', '提示', 'warning');
                return;
            }

            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category').value;
            const platform = document.getElementById('platform').value;

            if (!title) {
                showAlert('请输入视频标题', '提示', 'warning');
                return;
            }

            if (!category) {
                showAlert('请选择视频分类', '提示', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('video', selectedFile);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('platform', platform);

            // 显示进度条
            uploadProgress.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.textContent = '上传中...';

            try {
                const xhr = new XMLHttpRequest();

                // 进度监听
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = percent + '%';
                        progressText.textContent = `上传中... ${percent}%`;
                    }
                });

                xhr.addEventListener('load', async () => {
                    if (xhr.status === 200) {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            await showAlert('视频上传成功！', '成功', 'success');
                            window.location.href = '/vis/ap/index.php?action=admin_list';
                        } else {
                            showAlert(result.message || '上传失败', '错误', 'error');
                            resetUploadForm();
                        }
                    } else {
                        showAlert('上传失败，服务器错误', '错误', 'error');
                        resetUploadForm();
                    }
                });

                xhr.addEventListener('error', () => {
                    showAlert('上传失败，网络错误', '错误', 'error');
                    resetUploadForm();
                });

                xhr.open('POST', '/vis/ap/index.php?action=video_upload');
                xhr.send(formData);

            } catch (error) {
                showAlert('上传失败：' + error.message, '错误', 'error');
                resetUploadForm();
            }
        });

        function resetUploadForm() {
            uploadProgress.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.textContent = '上传中... 0%';
            submitBtn.disabled = false;
            submitBtn.textContent = '上传视频';
        }
    </script>
</body>
</html>
