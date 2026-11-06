<?php
$c = file_get_contents('admin/users/index.php');
echo "Has edit: " . (strpos($c, 'edit') !== false ? 'YES' : 'NO') . "\n";
echo "Has update: " . (strpos($c, 'update') !== false ? 'YES' : 'NO') . "\n";
echo "Has delete: " . (strpos($c, 'delete') !== false ? 'YES' : 'NO') . "\n";
?>
