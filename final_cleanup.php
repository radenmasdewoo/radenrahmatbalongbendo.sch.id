<?php
@unlink('/mnt/webdata/htdocs/mi/check_cron.php');
@unlink('/mnt/webdata/htdocs/cleanup_files.php');
@unlink('/mnt/webdata/htdocs/fix_db_user_v2.php');
@unlink('/mnt/webdata/htdocs/debug_env.php');
echo "Cleanup done.";
?>
