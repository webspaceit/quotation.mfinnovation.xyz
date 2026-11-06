<?php
require_once 'config/database.php';
$db = Database::getInstance();
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$db->query("UPDATE users SET password = ? WHERE email = ?", [$hash, 'admin@qms.com']);
echo "Password updated. Hash: $hash<br><a href='login.php'>Login</a>";
?>
