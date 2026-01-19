<?php
require_once 'includes/config.php';
$db = getDB();
echo "Connection OK!<br>";
echo "Current time: " . date('Y-m-d H:i:s');
?>