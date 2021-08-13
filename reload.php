<?php
$ip = $_POST['ip'];
$sn = $_POST['sn'];
shell_exec('bash DownloadScreenshot.sh ' . $ip . ' ' . $sn);
?>