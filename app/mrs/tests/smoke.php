<?php
// 简易离线校验脚本：使用 SQLite 内存库模拟入库、出库、盘点流程

define('MRS_ENTRY', true);
require __DIR__ . '/../config/env_mrs.php';
require __DIR__ . '/../lib/mrs_lib.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE mrs_package_ledger (
    package_id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku_name TEXT,
    batch_code TEXT,
    box_number TEXT,
    spec_info TEXT,
    status TEXT,
    status_note TEXT,
    inbound_time TEXT,
    outbound_time TEXT,
    created_at TEXT,
    updated_at TEXT,
    created_by INTEGER,
    updated_by INTEGER
)');

$resultInbound = mrs_create_inbound_entries($pdo, '香蕉', 'A01', '1-3', '20斤', 1);
$resultDuplicate = mrs_create_inbound_entries($pdo, '香蕉', 'A01', '2-4', '20斤', 1);
$resultOutbound = mrs_mark_outbound($pdo, [1, 2], 1);
$inventory = mrs_get_inventory_summary($pdo);
$flow = mrs_get_monthly_flow($pdo, date('Y-m'));

print_r([
    'inbound' => $resultInbound,
    'duplicate_skip' => $resultDuplicate,
    'outbound' => $resultOutbound,
    'summary' => $inventory,
    'flow' => $flow,
]);
