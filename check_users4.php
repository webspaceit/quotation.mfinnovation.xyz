<?php
$c = file_get_contents('admin/users/index.php');
// Find the actions column
$pos = strpos($c, 'Actions');
echo substr($c, max(0, $pos - 50), 400);
?>
