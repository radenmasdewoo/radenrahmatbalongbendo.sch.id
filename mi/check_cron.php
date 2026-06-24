<?php
echo "--- PHP Info ---\n";
echo "Version: " . phpversion() . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "str_contains: " . (function_exists('str_contains') ? 'YES' : 'NO') . "\n";
echo "\n--- Crontab www-data ---\n";
echo shell_exec('crontab -l');
echo "\n--- /etc/crontab ---\n";
echo @file_get_contents('/etc/crontab');
echo "\n--- /etc/cron.d (list) ---\n";
$files = @scandir('/etc/cron.d');
if ($files) print_r($files);
echo "\n--- File Checks ---\n";
echo "/mnt/webdata/htdocs/sync_news.py: " . (file_exists('/mnt/webdata/htdocs/sync_news.py') ? 'EXISTS' : 'NOT FOUND') . "\n";
?>
