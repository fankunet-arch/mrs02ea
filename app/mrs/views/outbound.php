<?php
/**
 * Outbound Page
 * 文件路径: app/mrs/views/outbound.php
 */

if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

// 获取库存汇总供选择
$inventory = mrs_get_inventory_summary($pdo);

// 如果选择了物料,加载库存明细
$selected_sku = $_GET['sku'] ?? '';
$packages = [];
if (!empty($selected_sku)) {
    $packages = mrs_get_inventory_detail($pdo, $selected_sku, 'fifo');
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出库核销 - MRS 系统</title>
    <link rel="stylesheet" href="/mrs/ap/css/backend.css">
    <style>
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        tr.selected {
            background-color: #dbeafe !important;
        }
    </style>
</head>
<body>
    <?php include MRS_VIEW_PATH . '/shared/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>出库核销</h1>
            <div class="header-actions">
                <a href="/mrs/ap/index.php?action=inventory_list" class="btn btn-secondary">返回库存</a>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="info-box">
                <strong>操作说明:</strong> 选择物料后,勾选要出库的包裹。系统按先进先出(FIFO)排序,建议优先出库库存天数较长的包裹。
            </div>

            <!-- 步骤1: 选择物料 -->
            <div class="form-group">
                <label for="sku_select">步骤1: 选择物料</label>
                <select id="sku_select" class="form-control" onchange="loadPackages(this.value)">
                    <option value="">-- 请选择要出库的物料 --</option>
                    <?php foreach ($inventory as $item): ?>
                        <option value="<?= htmlspecialchars($item['sku_name']) ?>"
                                <?= $selected_sku === $item['sku_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['sku_name']) ?> (在库: <?= $item['total_boxes'] ?> 箱)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($packages)): ?>
                <!-- 步骤2: 选择包裹 -->
                <h3 style="margin-top: 30px; margin-bottom: 15px;">步骤2: 选择要出库的包裹</h3>

                <div style="margin-bottom: 15px;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="selectAll()">全选</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="selectNone()">取消全选</button>
                    <span style="margin-left: 20px; color: #666;">
                        已选择: <strong id="selectedCount">0</strong> 箱
                    </span>
                </div>

                <form id="outboundForm">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="checkAll" onchange="toggleAll(this)">
                                </th>
                                <th>批次名称</th>
                                <th>快递单号</th>
                                <th>箱号</th>
                                <th>规格</th>
                                <th>入库时间</th>
                                <th>库存天数</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $pkg): ?>
                                <tr onclick="toggleRow(this)">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="ledger_ids[]"
                                               value="<?= $pkg['ledger_id'] ?>"
                                               onchange="updateCount()">
                                    </td>
                                    <td><?= htmlspecialchars($pkg['batch_name']) ?></td>
                                    <td><?= htmlspecialchars($pkg['tracking_number']) ?></td>
                                    <td><?= htmlspecialchars($pkg['box_number']) ?></td>
                                    <td><?= htmlspecialchars($pkg['spec_info']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($pkg['inbound_time'])) ?></td>
                                    <td><?= $pkg['days_in_stock'] ?> 天</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="form-actions">
                        <button type="button" class="btn btn-success" onclick="submitOutbound()">
                            确认出库
                        </button>
                    </div>
                </form>

                <div id="resultMessage"></div>
            <?php elseif (!empty($selected_sku)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <div class="empty-state-text">该物料暂无库存</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function loadPackages(sku) {
        if (sku) {
            window.location.href = '/mrs/ap/index.php?action=outbound&sku=' + encodeURIComponent(sku);
        } else {
            window.location.href = '/mrs/ap/index.php?action=outbound';
        }
    }

    function toggleRow(row) {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (event.target.tagName !== 'INPUT') {
            checkbox.checked = !checkbox.checked;
        }
        row.classList.toggle('selected', checkbox.checked);
        updateCount();
    }

    function toggleAll(checkAll) {
        const checkboxes = document.querySelectorAll('input[name="ledger_ids[]"]');
        checkboxes.forEach(cb => {
            cb.checked = checkAll.checked;
            cb.closest('tr').classList.toggle('selected', checkAll.checked);
        });
        updateCount();
    }

    function selectAll() {
        document.getElementById('checkAll').checked = true;
        toggleAll(document.getElementById('checkAll'));
    }

    function selectNone() {
        document.getElementById('checkAll').checked = false;
        toggleAll(document.getElementById('checkAll'));
    }

    function updateCount() {
        const count = document.querySelectorAll('input[name="ledger_ids[]"]:checked').length;
        document.getElementById('selectedCount').textContent = count;
    }

    function submitOutbound() {
        const selected = Array.from(document.querySelectorAll('input[name="ledger_ids[]"]:checked'))
            .map(cb => cb.value);

        if (selected.length === 0) {
            alert('请至少选择一个包裹');
            return;
        }

        if (!confirm(`确认出库 ${selected.length} 个包裹?`)) {
            return;
        }

        fetch('/mrs/ap/index.php?action=outbound_save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ledger_ids: selected
            })
        })
        .then(response => response.json())
        .then(result => {
            const messageDiv = document.getElementById('resultMessage');

            if (result.success) {
                messageDiv.innerHTML = `<div class="message success">${result.message}</div>`;

                setTimeout(() => {
                    window.location.href = '/mrs/ap/index.php?action=inventory_list';
                }, 1500);
            } else {
                messageDiv.innerHTML = `<div class="message error">出库失败: ${result.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('resultMessage').innerHTML =
                `<div class="message error">网络错误: ${error}</div>`;
        });
    }
    </script>
</body>
</html>
