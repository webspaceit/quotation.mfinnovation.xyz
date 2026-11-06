<?php
$c = file_get_contents('admin/settings/index.php');
echo "submit count: " . substr_count($c, 'type="submit"') . "\n";
echo "settingsForm: " . (strpos($c, 'settingsForm') !== false ? 'YES' : 'NO') . "\n";
?>
