<?php
$c = file_get_contents('admin/settings/index.php');
$start = strpos($c, '</form>');
echo substr($c, max(0, $start - 200), 200);
?>
