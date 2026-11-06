<?php
require 'config/database.php';
$db = Database::getInstance();
$cols = [
    "table_line_height VARCHAR(10) DEFAULT '1.4'",
    "table_font_weight VARCHAR(20) DEFAULT 'normal'",
    "table_letter_spacing VARCHAR(10) DEFAULT 'normal'",
    "table_text_transform VARCHAR(20) DEFAULT 'none'",
    "table_text_decoration VARCHAR(30) DEFAULT 'none'"
];
foreach($cols as $col) {
    $name = explode(' ', $col)[0];
    try {
        $db->query("ALTER TABLE company_settings ADD COLUMN $col");
        echo "Added $name<br>";
    } catch(Exception $e) {
        echo "$name: " . $e->getMessage() . "<br>";
    }
}
?>
