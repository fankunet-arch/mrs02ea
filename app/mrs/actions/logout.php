<?php
mrs_destroy_user_session();
header('Location: /mrs/ap/index.php?action=login&status=logout');
exit;
