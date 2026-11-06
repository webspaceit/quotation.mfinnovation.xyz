<?php
$c = file_get_contents('admin/users/index.php');
$lines = explode("\n", $c);
foreach($lines as $i => $line) {
    if(stripos($line, 'actions') !== false || stripos($line, 'js-edit') !== false || stripos($line, '<td') !== false) {
        if(stripos($line, 'js-edit') !== false) {
            echo "Line " . ($i+1) . ": " . trim($line) . "\n";
        }
    }
}
?>
