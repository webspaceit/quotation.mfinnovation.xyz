<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$user = new User();
if($user->isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if($user->login($_POST['email'], $_POST['password'])) {
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
$company = new Company();
$comp = $company->getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e($comp['company_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if(!empty($comp['favicon'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/uploads/<?= e($comp['favicon']) ?>">
    <?php endif; ?>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="bg-green-600 py-6 px-4 text-center">
                <h2 class="text-2xl font-bold text-white"><?= e($comp['company_name']) ?></h2>
                <p class="text-green-100 mt-1">Quotation Management System</p>
            </div>
            <div class="px-6 py-8">
                <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= e($error) ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <?php csrf_field(); ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><i class="fas fa-envelope mr-1"></i> Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-green-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><i class="fas fa-lock mr-1"></i> Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-green-500">
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 font-semibold">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
