<?php
/**
 * VIS API - Video Upload
 * 文件路径: app/vis/api/video_upload.php
 * 说明: 处理视频上传（上传流程：本地临时 -> R2 -> 数据库）
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 仅允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    vis_json_response(false, null, '仅支持POST请求');
}

try {
    // 检查是否有文件上传
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只上传了一部分',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传',
        ];

        $error = $_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE;
        $message = $errorMessages[$error] ?? '文件上传失败';

        vis_json_response(false, null, $message);
    }

    // 获取表单数据
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '其他');
    $platform = trim($_POST['platform'] ?? 'other');
    $createdBy = $_SESSION['user_login'] ?? 'system';

    // 验证标题
    if (empty($title)) {
        vis_json_response(false, null, '请输入视频标题');
    }

    // 获取上传文件信息
    $uploadedFile = $_FILES['video'];
    $tmpPath = $uploadedFile['tmp_name'];
    $originalFilename = $uploadedFile['name'];
    $fileSize = $uploadedFile['size'];
    $mimeType = $uploadedFile['type'];

    // 检查文件大小
    if ($fileSize > VIS_MAX_FILE_SIZE) {
        vis_json_response(false, null, '文件大小超过限制（最大' . round(VIS_MAX_FILE_SIZE / 1024 / 1024) . 'MB）');
    }

    // 获取文件扩展名
    $pathInfo = pathinfo($originalFilename);
    $extension = strtolower($pathInfo['extension'] ?? '');

    // 验证文件类型
    if (!vis_validate_file_type($mimeType, $extension)) {
        vis_json_response(false, null, '不支持的文件类型，仅支持 mp4 和 mov 格式');
    }

    // 生成R2存储路径
    $r2Key = vis_generate_r2_key($extension);

    // 上传到R2
    vis_log("开始上传视频到R2: {$r2Key}", 'INFO');
    $uploadResult = vis_upload_to_r2($tmpPath, $r2Key, $mimeType);

    if (!$uploadResult['success']) {
        vis_json_response(false, null, '上传到云存储失败: ' . $uploadResult['message']);
    }

    // 创建数据库记录
    $videoData = [
        'title' => $title,
        'platform' => $platform,
        'category' => $category,
        'r2_key' => $r2Key,
        'cover_url' => null, // TODO: 可以实现视频首帧截图
        'duration' => 0, // TODO: 可以通过ffmpeg获取时长
        'file_size' => $fileSize,
        'mime_type' => $mimeType,
        'original_filename' => $originalFilename,
        'created_by' => $createdBy,
    ];

    $createResult = vis_create_video($pdo, $videoData);

    if (!$createResult['success']) {
        // 如果数据库插入失败，尝试删除R2文件
        vis_delete_from_r2($r2Key);
        vis_json_response(false, null, '创建视频记录失败: ' . $createResult['message']);
    }

    // 删除临时文件
    @unlink($tmpPath);

    vis_json_response(true, [
        'id' => $createResult['id'],
        'title' => $title,
        'r2_key' => $r2Key,
    ], '视频上传成功');

} catch (Exception $e) {
    vis_log('视频上传异常: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
