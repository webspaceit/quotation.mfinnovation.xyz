<?php
$c = file_get_contents('admin/users/index.php');
$lines = explode("\n", $c);
foreach($lines as $i => $line) {
    if(stripos($line, 'edit') !== false || stripos($line, 'delete') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
?>
