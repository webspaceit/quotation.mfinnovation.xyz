<?php
require_once __DIR__ . '/../config/database.php';
$user = new User();
if(!$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
$currentUser = $user->getCurrentUser();
?>
