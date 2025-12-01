<?php
mrs_require_login();

$snapshot = mrs_get_inventory_snapshot($pdo);

mrs_render('inventory', [
    'snapshot' => $snapshot,
]);
