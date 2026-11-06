<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if(!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$user = new User();
$errors = [];
$msg = $_GET['msg'] ?? '';
$currentId = (int)$currentUser['id'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ---------------------------------------------------------------- create
    if($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';

        if($name === '')                                        $errors[] = 'Name is required.';
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))           $errors[] = 'A valid email address is required.';
        if(strlen($password) < 8)                                $errors[] = 'Password must be at least 8 characters.';
        if(!$errors && $user->emailExists($email))               $errors[] = 'That email address is already in use.';

        if(!$errors) {
            $user->create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
            header('Location: ' . BASE_URL . 'admin/users/index.php?msg=created');
            exit;
        }

    // ---------------------------------------------------------------- update
    } elseif($action === 'update') {
        $id      = (int)($_POST['id'] ?? 0);
        $target  = $user->getById($id);
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $role    = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';
        $password = (string)($_POST['password'] ?? '');

        // A role is never taken from the browser when editing yourself: the
        // control is disabled client-side, so the field is simply absent from
        // the POST. Forcing it here keeps name/email edits working.
        if($target && $id === $currentId) {
            $role = $target['role'];
        }

        if(!$target)                                             $errors[] = 'That user no longer exists.';
        if($name === '')                                         $errors[] = 'Name is required.';
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))            $errors[] = 'A valid email address is required.';
        if($password !== '' && strlen($password) < 8)             $errors[] = 'A new password must be at least 8 characters.';
        if(!$errors && $user->emailExists($email, $id))          $errors[] = 'That email address is already in use.';

        // Guard rail: never lock the system out of its own admin area.
        if(!$errors && $target && $target['role'] === 'admin' && $role !== 'admin' && $user->countAdmins($id) < 1) {
            $errors[] = 'This is the last administrator account. Promote someone else first.';
        }

        if(!$errors) {
            $user->update($id, ['name' => $name, 'email' => $email, 'role' => $role, 'password' => $password]);
            // Keep the session label in sync when editing yourself.
            if($id === $currentId) {
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
            }
            header('Location: ' . BASE_URL . 'admin/users/index.php?msg=updated');
            exit;
        }

    // ---------------------------------------------------------------- delete
    } elseif($action === 'delete') {
        $id     = (int)($_POST['id'] ?? 0);
        $target = $user->getById($id);

        if(!$target) {
            $errors[] = 'That user no longer exists.';
        } elseif($id === $currentId) {
            $errors[] = 'You cannot delete the account you are signed in with.';
        } elseif($target['role'] === 'admin' && $user->countAdmins($id) < 1) {
            $errors[] = 'This is the last administrator account and cannot be deleted.';
        } else {
            $user->delete($id);
            header('Location: ' . BASE_URL . 'admin/users/index.php?msg=deleted');
            exit;
        }
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
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Manage system users, roles and passwords</p>
            </div>
            <button onclick="openAdd()" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                <span class="material-symbols-outlined text-base">add_circle</span><span>Add User</span>
            </button>
        </div>

        <?php if($errors): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-red-600 mt-0.5">error</span>
            <div class="text-sm text-red-800 font-medium">
                <?php foreach($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if($msg): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 mt-0.5">check_circle</span>
            <div>
                <h3 class="text-sm font-bold text-emerald-800">
                    <?= $msg === 'created' ? 'User added successfully.' : ($msg === 'updated' ? 'User updated successfully.' : 'User deleted.') ?>
                </h3>
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
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100 font-medium">
                        <?php foreach($users as $u): $isSelf = ((int)$u['id'] === $currentId); ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-6 text-slate-900 font-medium">
                                <?= e($u['name']) ?>
                                <?php if($isSelf): ?><span class="ml-2 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wide border border-slate-200">You</span><?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-slate-600"><?= e($u['email']) ?></td>
                            <td class="py-4 px-6"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $u['role']=='admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td class="py-4 px-6 text-slate-500 font-medium"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <button type="button"
                                        class="js-edit inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-colors"
                                        data-id="<?= (int)$u['id'] ?>"
                                        data-name="<?= e($u['name']) ?>"
                                        data-email="<?= e($u['email']) ?>"
                                        data-role="<?= e($u['role']) ?>"
                                        data-self="<?= $isSelf ? '1' : '0' ?>">
                                    <span class="material-symbols-outlined text-sm">edit</span> Edit
                                </button>
                                <?php if(!$isSelf): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete <?= e($u['name']) ?>? This cannot be undone.');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-200 bg-white text-red-700 text-xs font-bold hover:bg-red-50 hover:border-red-300 transition-colors ml-2">
                                        <span class="material-symbols-outlined text-sm">delete</span> Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($users)): ?>
                        <tr><td colspan="5" class="py-12 text-center text-slate-500">No users found.</td></tr>
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
            <button type="button" onclick="closeAdd()" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="add">
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
                <input type="password" name="password" required minlength="8" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                <p class="text-[11px] text-slate-500 mt-1">At least 8 characters.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role <span class="text-red-500">*</span></label>
                <select name="role" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeAdd()" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-semibold shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all">Save User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
        <div class="p-5 border-b border-slate-200 bg-white flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-slate-900">Edit User</h2>
            <button type="button" onclick="closeEdit()" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id" value="">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="edit-name" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit-email" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password</label>
                <input type="password" name="password" id="edit-password" minlength="8" autocomplete="new-password" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all" placeholder="Leave blank to keep current password">
                <p class="text-[11px] text-slate-500 mt-1">Fill this in to reset the password. Minimum 8 characters.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role <span class="text-red-500">*</span></label>
                <select name="role" id="edit-role" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1" id="edit-role-hint"></p>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeEdit()" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-semibold shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdd() {
    const m = document.getElementById('addModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeAdd() {
    const m = document.getElementById('addModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
function closeEdit() {
    const m = document.getElementById('editModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
document.querySelectorAll('.js-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const d = btn.dataset;
        document.getElementById('edit-id').value = d.id;
        document.getElementById('edit-name').value = d.name;
        document.getElementById('edit-email').value = d.email;
        document.getElementById('edit-password').value = '';
        const roleSel = document.getElementById('edit-role');
        roleSel.value = d.role;
        // Self-demotion is blocked server-side; disable the control so it is not offered.
        roleSel.disabled = (d.self === '1');
        document.getElementById('edit-role-hint').textContent = (d.self === '1')
            ? 'You cannot change your own role.'
            : '';
        const m = document.getElementById('editModal');
        m.classList.remove('hidden'); m.classList.add('flex');
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
