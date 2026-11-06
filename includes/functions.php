<?php
function csrf_token() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_valid() {
    if(empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function verify_csrf() {
    if($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
        // 403, not 419: Apache rewrites non-standard status codes to 500.
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

function sanitize($data) {
    if(is_array($data)) {
        foreach($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function e($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount, $currency = '৳') {
    return $currency . ' ' . number_format($amount, 2);
}

function uploadFile($file, $dir = 'assets/uploads/') {
    if($file['error'] !== UPLOAD_ERR_OK) return false;
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed)) return false;
    if($file['size'] > 2 * 1024 * 1024) return false;
    $filename = uniqid() . '.' . $ext;
    $targetDir = __DIR__ . '/../' . $dir;
    if(!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    $target = $targetDir . $filename;
    if(move_uploaded_file($file['tmp_name'], $target)) {
        @chmod($target, 0644);
        return $filename;
    }
    return false;
}
?>
