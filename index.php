<?php
require_once 'config/database.php';
$user = new User();
if($user->isLoggedIn()) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
