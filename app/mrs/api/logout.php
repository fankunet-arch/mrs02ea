<?php
if (!defined('MRS_ENTRY')) {
    die('Access denied');
}

mrs_destroy_user_session();
header('Location: /mrs/ap/index.php?action=login');
exit;
