<?php
header('Content-Type: text/plain');
$pass = '.Kmzwa88saa';
$cmd = "echo '$pass' | sudo -S ls -la /mnt/webdata/htdocs/api/ 2>&1";
echo "Running: $cmd\n\n";
echo shell_exec($cmd);

$cmd2 = "echo '$pass' | sudo -S whoami 2>&1";
echo "\nRunning: $cmd2\n";
echo shell_exec($cmd2);
?>
