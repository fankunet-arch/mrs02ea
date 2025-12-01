<?php
/**
 * API: Save Inbound
 * 文件路径: app/mrs/api/inbound_save.php
 */

if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mrs_json_response(false, null, '非法请求方式');
}

$input = mrs_get_json_input();
if (!$input) {
    $input = $_POST;
}

$sku_name = trim($input['sku_name'] ?? '');
$batch_code = trim($input['batch_code'] ?? '');
$box_range = trim($input['box_range'] ?? '');
$spec_info = trim($input['spec_info'] ?? '');

if (empty($sku_name) || empty($batch_code) || empty($box_range)) {
    mrs_json_response(false, null, '物料名称、批次号和箱号范围不能为空');
}

// 解析箱号范围
$box_numbers = parse_box_range($box_range);

if (empty($box_numbers)) {
    mrs_json_response(false, null, '箱号范围格式错误');
}

// 获取操作员
$operator = $_SESSION['user_login'] ?? 'system';

// 执行入库
$result = mrs_inbound_packages($pdo, $sku_name, $batch_code, $box_numbers, $spec_info, $operator);

if ($result['success']) {
    mrs_json_response(true, [
        'created' => $result['created'],
        'errors' => $result['errors']
    ], '入库成功');
} else {
    mrs_json_response(false, null, $result['message'] ?? '入库失败');
}

/**
 * 解析箱号范围
 * @param string $range 例如: "1-5" 或 "1,3,5"
 * @return array 例如: ['0001', '0002', '0003', '0004', '0005']
 */
function parse_box_range($range) {
    $box_numbers = [];

    // 处理范围: 1-5
    if (strpos($range, '-') !== false) {
        list($start, $end) = explode('-', $range, 2);
        $start = (int)trim($start);
        $end = (int)trim($end);

        if ($start > 0 && $end >= $start && ($end - $start) <= 1000) {
            for ($i = $start; $i <= $end; $i++) {
                $box_numbers[] = str_pad($i, 4, '0', STR_PAD_LEFT);
            }
        }
    }
    // 处理逗号分隔: 1,3,5
    elseif (strpos($range, ',') !== false) {
        $numbers = explode(',', $range);
        foreach ($numbers as $num) {
            $num = (int)trim($num);
            if ($num > 0) {
                $box_numbers[] = str_pad($num, 4, '0', STR_PAD_LEFT);
            }
        }
    }
    // 单个箱号: 1
    else {
        $num = (int)trim($range);
        if ($num > 0) {
            $box_numbers[] = str_pad($num, 4, '0', STR_PAD_LEFT);
        }
    }

    return array_unique($box_numbers);
}
