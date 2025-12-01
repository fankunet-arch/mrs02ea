<?php
mrs_destroy_user_session();
header('Location: /mrs/index.php?action=login');
exit;
