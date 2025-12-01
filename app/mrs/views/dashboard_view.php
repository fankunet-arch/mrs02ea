<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>MRS 后台</title>
    <link rel="stylesheet" href="/mrs/ap/css/backend.css">
</head>
<body>
<header class="topbar">
    <div>
        <strong>MRS 物料收发系统</strong>
        <span class="tag">独立运行</span>
        <span class="tag">与 Express 代码解耦</span>
    </div>
    <div>
        <span class="welcome">你好，<?php echo htmlspecialchars($_SESSION['mrs_user_display_name'] ?? $_SESSION['mrs_user_login'] ?? ''); ?></span>
        <a class="btn btn-link" href="/mrs/ap/index.php?action=logout">退出</a>
    </div>
</header>

<main class="container">
    <section class="panel">
        <div class="panel-header">
            <h2>月度流量 (<?php echo htmlspecialchars($current_month); ?>)</h2>
            <p>对照需求 3.1，核对本月入库与出库数量</p>
        </div>
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-label">本月入库</div>
                <div class="stat-value"><?php echo (int)$flow['inbound']; ?> 箱</div>
            </div>
            <div class="stat">
                <div class="stat-label">本月出库</div>
                <div class="stat-value"><?php echo (int)$flow['outbound']; ?> 箱</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>入库登记</h2>
            <p>支持输入批次与箱号范围，自动生成连续箱号，满足“极简录入”原则</p>
        </div>
        <form id="inbound-form" class="form-grid">
            <div class="form-group">
                <label for="sku_name">物料名称</label>
                <input type="text" id="sku_name" name="sku_name" placeholder="如：香蕉" required>
            </div>
            <div class="form-group">
                <label for="batch_code">批次号</label>
                <input type="text" id="batch_code" name="batch_code" placeholder="如：A01" required>
            </div>
            <div class="form-group">
                <label for="range_start">箱号起始</label>
                <input type="number" id="range_start" name="range_start" placeholder="1" required>
            </div>
            <div class="form-group">
                <label for="range_end">箱号结束</label>
                <input type="number" id="range_end" name="range_end" placeholder="10" required>
            </div>
            <div class="form-group">
                <label for="spec_info">单箱规格 (可选)</label>
                <input type="text" id="spec_info" name="spec_info" placeholder="20斤 / 500个">
            </div>
            <div class="form-group full">
                <button type="submit" class="btn btn-primary">批量创建在库记录</button>
                <span class="helper-text">输入 1-5 将生成 0001 ~ 0005 的在库记录</span>
            </div>
        </form>
        <div class="alert" id="inbound-result" style="display:none;"></div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>库存列表（在库）</h2>
            <p>对照场景二/三，支持勾选出库或标记损耗</p>
        </div>
        <div class="table-actions">
            <select id="status-filter">
                <option value="in_stock" selected>在库</option>
                <option value="shipped">已出库</option>
                <option value="void">作废</option>
                <option value="all">全部</option>
            </select>
            <input type="text" id="filter-sku" placeholder="按物料搜索">
            <input type="text" id="filter-batch" placeholder="按批次搜索">
            <button class="btn" id="refresh-list">刷新</button>
        </div>
        <table class="data-table" id="package-table">
            <thead>
                <tr>
                    <th>物料</th>
                    <th>批次</th>
                    <th>箱号</th>
                    <th>规格</th>
                    <th>状态</th>
                    <th>入库时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>实时库存快照</h2>
            <p>回答“我现在库里还有什么？”，按物料汇总并展示明细</p>
        </div>
        <div class="inventory-grid" id="inventory-summary"></div>
    </section>
</main>

<script>
    window.MRS_INITIAL_PACKAGES = <?php echo json_encode($recent_packages, JSON_UNESCAPED_UNICODE); ?>;
    window.MRS_INITIAL_INVENTORY = <?php echo json_encode($inventory, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/mrs/ap/js/mrs_dashboard.js"></script>
</body>
</html>
