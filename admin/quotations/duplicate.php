<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$quotation = new Quotation();
$id = intval($_GET['id']);
$newId = $quotation->duplicate($id);
if($newId) {
    header('Location: ' . BASE_URL . 'admin/quotations/edit.php?id=' . $newId);
} else {
    header('Location: ' . BASE_URL . 'admin/quotations/index.php');
}
exit;
?>
