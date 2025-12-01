<?php
/**
 * MRS Package Ledger System - Bootstrap
 */

if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

require_once __DIR__ . '/config_mrs/env_mrs.php';

try {
    $pdo = get_mrs_db_connection();
} catch (PDOException $e) {
    http_response_code(503);
    error_log('Critical: MRS Database connection failed - ' . $e->getMessage());
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>系统维护中</title></head><body><h1>系统维护中</h1><p>数据库连接失败，请稍后再试。</p></body></html>');
}

require_once MRS_LIB_PATH . '/mrs_lib.php';

mrs_start_secure_session();
