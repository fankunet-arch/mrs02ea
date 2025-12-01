<?php
if (!defined('MRS_ENTRY')) { die('Access denied'); }
require MRS_VIEW_PATH . '/partials/header.php';
?>
<h1>入库录入</h1>
<p class="muted">按照“货-批-号”三要素快速建账，可输入范围如 <code>1-5</code> 或 <code>0001-0005</code> 自动生成多条记录。</p>
<form class="form" method="post" action="/mrs/ap/index.php?action=inbound_save">
    <div class="form-row">
        <label>物料名称</label>
        <input type="text" name="sku_name" placeholder="例如：香蕉" required>
    </div>
    <div class="form-row">
        <label>批次号</label>
        <input type="text" name="batch_code" placeholder="例如：A01" required>
    </div>
    <div class="form-row">
        <label>箱号范围</label>
        <input type="text" name="box_range" placeholder="示例：1-5 或 0001,0002" required>
        <small>支持逗号分隔的多个范围；将自动补齐前导零。</small>
    </div>
    <div class="form-row">
        <label>单箱规格/备注</label>
        <input type="text" name="spec_info" placeholder="可填 20斤 / 12件" />
    </div>
    <div class="form-actions">
        <button type="submit">生成台账</button>
    </div>
</form>
<section class="helper">
    <h2>操作提示</h2>
    <ul>
        <li>系统默认状态为 <strong>在库</strong>，并记录当前时间为入库时间。</li>
        <li>重复录入的箱号会自动跳过并在页面提示。</li>
        <li>入库后即可在“出库核销”中按 FIFO 选择包裹。</li>
    </ul>
</section>
<?php require MRS_VIEW_PATH . '/partials/footer.php'; ?>
