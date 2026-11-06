<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if(!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$user = new User();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if(isset($_POST['add'])) {
        $user->create($_POST);
        header('Location: ' . BASE_URL . 'admin/users/index.php?added=1');
        exit;
    }
}

$users = $user->getAll();
$pageTitle = 'Users';
require_once '../../includes/header.php';
?>
<main class="w-full pt-16 bg-slate-50 min-h-screen">
    <div class="p-8 flex flex-col gap-6 max-w-[1680px] mx-auto w-full">
        <!-- Top Actionable Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                        <span class="tracking-wide">User Management</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Users</h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Manage system users</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden');document.getElementById('addModal').classList.add('flex')" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                <span class="material-symbols-outlined text-base">add_circle</span><span>Add User</span>
            </button>
        </div>

        <?php if(isset($_GET['added'])): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 mt-0.5">check_circle</span>
            <div>
                <h3 class="text-sm font-bold text-emerald-800">User added successfully!</h3>
            </div>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-200 bg-white">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">people</span> System Users
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6">Name</th>
                            <th class="py-4 px-6">Email</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Created</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100 font-medium">
                        <?php foreach($users as $u): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-6 text-slate-900 font-medium"><?= e($u['name']) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= e($u['email']) ?></td>
                            <td class="py-4 px-6"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $u['role']=='admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td class="py-4 px-6 text-slate-500 font-medium"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($users)): ?>
                        <tr><td colspan="4" class="py-12 text-center text-slate-500">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
        <div class="p-5 border-b border-slate-200 bg-white flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-slate-900">Add New User</h2>
            <button onclick="document.getElementById('addModal').classList.add('hidden');document.getElementById('addModal').classList.remove('flex')" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php csrf_field(); ?>
            <input type="hidden" name="add" value="1">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role <span class="text-red-500">*</span></label>
                <select name="role" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden');document.getElementById('addModal').classList.remove('flex')" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-semibold shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all">Save User</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
