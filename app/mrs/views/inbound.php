<?php
/**
 * Inbound Page
 * 文件路径: app/mrs/views/inbound.php
 */

if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

// 获取所有物料供选择
$skus = mrs_get_all_skus($pdo);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入库录入 - MRS 系统</title>
    <link rel="stylesheet" href="/mrs/ap/css/backend.css">
</head>
<body>
    <?php include MRS_VIEW_PATH . '/shared/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>入库录入</h1>
            <div class="header-actions">
                <a href="/mrs/ap/index.php?action=inventory_list" class="btn btn-secondary">返回库存</a>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="info-box">
                <strong>操作说明:</strong> 根据包裹标签上的信息录入。支持批量录入箱号范围,例如输入"1-5"将自动创建 0001 至 0005 共5个包裹记录。
            </div>

            <form id="inboundForm" class="form-horizontal">
                <div class="form-group">
                    <label for="sku_name">物料名称 <span class="required">*</span></label>
                    <select id="sku_name" name="sku_name" class="form-control" required>
                        <option value="">-- 请选择物料 --</option>
                        <?php foreach ($skus as $sku): ?>
                            <option value="<?= htmlspecialchars($sku['sku_name']) ?>">
                                <?= htmlspecialchars($sku['sku_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text">或 <a href="/mrs/ap/index.php?action=sku_manage">添加新物料</a></small>
                </div>

                <div class="form-group">
                    <label for="batch_code">批次号 <span class="required">*</span></label>
                    <input type="text" id="batch_code" name="batch_code" class="form-control"
                           placeholder="例如: A01, B05" required>
                    <small class="form-text">输入标签上的批次号</small>
                </div>

                <div class="form-group">
                    <label for="box_range">箱号范围 <span class="required">*</span></label>
                    <input type="text" id="box_range" name="box_range" class="form-control"
                           placeholder="例如: 1-5 或 1,3,5" required>
                    <small class="form-text">支持范围(1-5)或逗号分隔(1,3,5)</small>
                </div>

                <div class="form-group">
                    <label for="spec_info">单箱规格</label>
                    <input type="text" id="spec_info" name="spec_info" class="form-control"
                           placeholder="例如: 20斤, 500个">
                    <small class="form-text">可选,仅作参考记录</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">确认入库</button>
                    <button type="reset" class="btn btn-secondary">重置</button>
                </div>
            </form>

            <div id="resultMessage"></div>
        </div>
    </div>

    <script>
    document.getElementById('inboundForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            sku_name: formData.get('sku_name'),
            batch_code: formData.get('batch_code'),
            box_range: formData.get('box_range'),
            spec_info: formData.get('spec_info')
        };

        fetch('/mrs/ap/index.php?action=inbound_save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            const messageDiv = document.getElementById('resultMessage');

            if (result.success) {
                let msg = `<div class="message success">入库成功! 创建了 ${result.created} 个包裹记录。`;
                if (result.errors && result.errors.length > 0) {
                    msg += `<br>错误: ${result.errors.join(', ')}`;
                }
                msg += '</div>';
                messageDiv.innerHTML = msg;

                // 清空表单
                document.getElementById('inboundForm').reset();

                // 3秒后跳转到库存页面
                setTimeout(() => {
                    window.location.href = '/mrs/ap/index.php?action=inventory_list';
                }, 2000);
            } else {
                messageDiv.innerHTML = `<div class="message error">入库失败: ${result.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('resultMessage').innerHTML =
                `<div class="message error">网络错误: ${error}</div>`;
        });
    });
    </script>
</body>
</html>
